<?php

namespace MykiLeaks;

Class Product
{
	public $time;
	public $adult;
	public $zone;

	public function __construct(\DateTimeImmutable $time, $adult, $zone)
	{
		$this->time = $time;
		$this->adult = $adult;
		$this->zone = $zone;
	}

	public static function nextFullHour($time)
    {
        $next = $time->add(new \DateInterval("PT1H"));
        $next = $next->setTime($next->format("H"), 0);
        return $next;
    }

	public function isActiveAt(\DateTimeImmutable $time)
	{
		return $time < $this->expires();
	}

	public function expires()
	{
		$hour = $this->time->format("H");


		// Products with a start time of > 6pm are valid until the last train. 4am is good enough.
		if ($hour >= 18)
			return $this->time->modify("04:00 tomorrow");
		if ($hour < 4)
			return $this->time->modify("04:00 today");
		
		// The "next full hour" rule was removed on Aug 10 2014
		// http://www.ptua.org.au/2009/11/18/myki-qa/
		if ($this->time > new \DateTimeImmutable('2014-08-10')) {
			return $this->time->add(new \DateInterval("PT2H"));
		}
		else {
			$nextFull = self::nextFullHour($this->time);
			return $nextFull->add(new \DateInterval("PT2H"));
		}
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
