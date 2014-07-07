<?php

namespace MykiLeaks;

class EventGroup
{

	public $date;
	public $adult;

	public $openingBal;
	public $closingBal;

	public $nonTravel = 0.0;
	public $travel = 0.0;
	public $balanceErrors = 0.0;
	public $reimbursements = 0.0;

	public $events = [];

	public $hasUnknownProducts = false;
	public $hasMissingTouchOn = false;
	public $hasUnsupportedZones = false;

	private $products = null;

	public function __construct($date, $adult, $events, $openingBal)
	{
		if (!$date)
			throw new \Exception("No date provided to EventGroup constructor");

		$this->date = $date;
		$this->adult = $adult;

		$this->date->setTime(0, 0, 0);

		$this->events = $events;

		// No opening balance can be resolved with a card purchase
		if ($openingBal)
			$this->openingBal = $openingBal;
		else if (!$openingBal && $this->events && $this->events[0]->type & Event::TYPE_CARD_PURCHASE) {
			$this->openingBal = 0.0;
		}

		foreach ($this->events as $e) {

			if ($e->duplicate)
				continue;

			$this->hasUnknownProducts = $this->hasUnknownProducts || $e->type & Event::TYPE_UNKNOWN;
			$this->hasUnsupportedZones = $this->hasUnsupportedZones || $e->zone & Event::ZONE_UNSUPPORTED;

			// If an event is a next day touch off, mark it as a default fare
			if ($e->type & Event::TYPE_TOUCHOFF && 
				($e->timestamp->getTimestamp() - 60*60*4) - $this->date->getTimestamp() > 24*60*60)
				$e->defaultFare = true;

			// Update travel total
            if ($e->type & Event::TYPE_TRAVEL) {
                if ($e->credit)
                    $this->travel += $e->credit;
                if ($e->debit)
                    $this->travel -= $e->debit;    
            } 
        	// Non-travel total
            else { 
                if ($e->credit)   
	                $this->nonTravel += $e->credit;
                if ($e->debit)
                    $this->nonTravel -= $e->debit;
            }

            // Check for reimbursement
            if ($e->type & Event::TYPE_REIMBURSEMENT) {
            	if ($e->credit)
            		$this->reimbursements += $e->credit;
            	if ($e->debit)
            		$this->reimbursements -= $e->debit;

            } 

            if ($e->balance)
                $this->closingBal = $e->balance;
		}

		// If no events, ensure closing balance is set
		if ($this->openingBal && !$this->closingBal)
			$this->closingBal = $this->openingBal;

		// Determine any balance errors
		$this->balanceErrors = round(
			$this->closingBal - 
			($this->travel + $this->nonTravel) - 
			$this->openingBal
			, 2);

	}

	public function getErrors()
	{
		$errors = [];
		if ($this->openingBal === null)
			$errors[] = "openingBal";
		if ($this->closingBal === null)
			$errors[] = "closingBal";
		if ($this->balanceErrors)
			$errors[] = "balanceError";
		if ($this->hasUnknownProducts)
			$errors[] = "unknownProduct";
		if ($this->hasMissingTouchOn)
			$errors[] = "missingTouchOn";
		if ($this->hasUnsupportedZones)
			$errors[] = "unsupportedZones";
		if ($this->hasFareProductSale())
			$errors[] = "fareProductSale";
		if ($this->hasTravel() && !$this->getZones())
			$errors[] = "unknownZones";
		if (!$this->inSequence())
			$errors[] = "sequenceError";

		return $errors;
	}

	public function totalReimbursements()
	{
		return array_sum(
			array_map(function($e) {
				return $e->credit;
			}, array_filter($this->events, function($e) {
				return $e->type & Event::TYPE_REIMBURSEMENT;
			}
		)));
	}

	public function hasTravel()
	{
		foreach ($this->events as $e) {
			if ($e->type & Event::TYPE_TRAVEL)
				return true;
		}
		return false;
	}

	public function hasFareProductSale()
	{
		foreach ($this->events as $e) {
			if ($e->type & Event::TYPE_FARE_PRODUCT_SALE)
				return true;
		}
		return false;
	}

	public function inSequence()
	{
		$on = false;
		foreach ($this->events as $e) {
			if ($e->type & Event::TYPE_TOUCHON)
			{
				if ($on)
					return false;
				else
					$on = true;
			}
			else if ($e->type & Event::TYPE_TOUCHOFF) {
				if (!$on)
					return false;
				else
					$on = false;
			}
		}
		return true;
	}

	/**
	 * Returns the products that SHOULD have been charged for a day
	 */
	public function getProducts()
	{

		if ($this->products === null) {

			$products = [];
			$lastTouchOn = null;

			foreach ($this->events as $e) {

				if ($e->duplicate)
					continue;
		
				if ($e->type & Event::TYPE_TOUCHON || ($e->type & Event::TYPE_TOUCHOFF && !$lastTouchOn)) {

					// If this is a touch off, we're missing a touch on somewhere
					if ($e->type & Event::TYPE_TOUCHOFF)
						$this->hasMissingTouchOn = true; 

					// Check if a new product is required
					$p = $this->productRequired($products, $e->timestamp, $e->zone);
					if ($p)
						array_push($products, $p);

					$lastTouchOn = $e;
				}

				else if ($e->type & Event::TYPE_TOUCHOFF) {

					$zone = $e->zone;

					// Zone is overridden for default fares on train / bus
					if ($e->defaultFare)
						$zone = ($lastTouchOn->service & Event::SERVICE_TRAM) ? Event::ZONE_1 : Event::ZONE_1AND2;

					// Detect travel through zone 1 on trains
					else if ($lastTouchOn && $lastTouchOn->zone ^ Event::ZONE_1 && $zone ^ Event::ZONE_1 && 
						$lastTouchOn->service & Event::SERVICE_TRAIN && $e->service & Event::SERVICE_TRAIN) {
						$zone = ZoneTwo::areConnected($lastTouchOn->description, $e->description) ? Event::ZONE_2 : Event::ZONE_1AND2;
					}

					$t = $lastTouchOn ? $lastTouchOn->timestamp : $e->timestamp;
					$p = $this->productRequired($products, $t, $zone); 
					if ($p)
						array_push($products, $p);
					
					$lastTouchOn = null;
				}
			}

			// Convert any remaining zone 1 / 2 to zone 2 only
			foreach ($products as $p) {
				if ($p->zone & Event::ZONE_1OR2)
					$p->zone = Event::ZONE_2;
			}

			$this->products = $products;	
		}

		return $this->products;
	}

	public function getServices()
	{
		$services = 0;
		foreach ($this->events as $e) {
			$services |= $e->service;
		}
		return $services;
	}

	public function getZones()
	{
		$zones = 0;
		foreach ($this->getProducts() as $p)
			$zones |= $p->zone;
		return $zones;
	}

	protected function getProductFare()
	{
		return array_sum(array_map(
			function($e) { 
				return $e->getCost(); 
			}, $this->getProducts()
		));
	}

	public function getExpectedFare()
	{
		$productTotal = $this->getProductFare();
		$discount = FareTable::getDiscount($this->date, $this->adult);

		if ($discount && $productTotal > $discount['cost'])
			return $discount;
		else
			return ['type'=>'product', 'cost'=>$productTotal];
	}

	private function isActive($product, $time)
	{
		$t = $time->getTimestamp();
		$pt = $this->nextFullHour($product->time)->getTimestamp();
		return (($t - $pt < 60 * 60 * 2) ||	(($pt - 60 * 60 * 4) % 86400 > 60 * 60 * 14)); 
	}

	private function getActiveProducts($products, $time)
	{
		$active = [];

		foreach ($products as $p) {
			if ($this->isActive($p, $time))
				$active[] = $p;
		}

		return $active;
	}

	private function getActiveZones($products, $time) 
	{

		$prod = $this->getActiveProducts($products, $time);
		$active = 0;
		foreach ($prod as $p) {
			$active |= $p->zone;
		}

		// Zones for which 2 products have already been purchased today
		$zones = [0, 0];
		foreach ($products as $p) {
			if ($p->zone & Event::ZONE_1)
				$zones[0]++;
			else if ($p->zone & Event::ZONE_2)
				$zones[1]++;
		}

		if ($zones[0] >= 2)
			$active |= Event::ZONE_1;
		if ($zones[1] >= 2)
			$active |= Event::ZONE_2;

		return $active;
	}

	private function productRequired(&$products, $time, $zone)
	{
		$active = $this->getActiveZones($products, $time);

		// No product required
		if (($zone & $active) == $zone || ($active && ($zone == Event::ZONE_1OR2)))
			return null;

		// Check if a z1/2 upgrade will suffice
		if ($this->attemptUpgrade($products, $time, $zone))
			return null;

		// If it's a default fare (1+2), check which zones are actually needed
		if ($zone == Event::ZONE_1AND2) {
			$req = $zone ^ $active;
			if ($req)
				return new Product($time, $this->adult, $req);
		}

		// New product required for this zone
		return new Product($time, $this->adult, $zone);
	}

	// Attempts to upgrade any active z1/2 product to a fixed zone product
	private function attemptUpgrade(&$products, $time, $zone)
	{
		foreach ($products as &$p) {
			if ($p->zone & Event::ZONE_1OR2 && $this->isActive($p, $time)) {
				$p->zone = $zone;
				return true;
			}
		}
		return false;
	}

	private function nextFullHour($time)
	{
		$plusOne = $time->format("U") + 3600;
		return \DateTime::createFromFormat("U", floor($plusOne / 3600) * 3600);
	}
}
