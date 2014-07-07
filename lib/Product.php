<?php

namespace MykiLeaks;

Class Product
{
	public $time;
	public $adult;
	public $zone;

	public function __construct($time, $adult, $zone)
	{
		$this->time = $time;
		$this->adult = $adult;
		$this->zone = $zone;
	}

	public function getCost()
	{
		return FareTable::getProductFare($this->time, $this->adult, $this->zone);
	}

	public function asArray()
	{
		return [ 
			"time"=>$this->time, 
			"adult"=>$this->adult, 
			"zone"=>$this->zone, 
			"cost"=>$this->getCost()
		];
	}
}
