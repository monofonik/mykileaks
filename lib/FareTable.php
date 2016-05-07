<?php
namespace MykiLeaks;

class FareTable
{
    private static $fares = [
        'adult' => [
            'zone1'     => [2.94, 3.02, 3.28, 3.50, 3.58, 3.76, 3.90],
            'zone2'     => [2.02, 2.08, 2.26, 2.42, 2.48, 2.60, 2.70],
            'zone1+2'   => [4.96, 5.10, 5.54, 5.92, 6.06, 3.76, 3.90],
        ],
        'concession' => [
            'zone1'     => [1.47, 1.51, 1.64, 1.75, 1.79, 1.88, 1.95],
            'zone2'     => [1.01, 1.04, 1.13, 1.21, 1.24, 1.30, 1.35],
            'zone1+2'   => [2.48, 2.55, 2.77, 2.96, 3.03, 1.88, 1.95],
        ],
        'weekend'       => [3.00, 3.00, 3.30, 3.50, 6.00, 6.00, 6.00],
    ];

    private static function getFareIndex($date)
    {
        $fareIndex = 0;
        if ($date >= new \DateTimeImmutable('2011-03-12'))
            $fareIndex = 1;
        if ($date >= new \DateTimeImmutable('2012-01-01'))
            $fareIndex = 2;
        if ($date >= new \DateTimeImmutable('2013-01-01'))
            $fareIndex = 3;
        if ($date >= new \DateTimeImmutable('2014-01-01'))
            $fareIndex = 4;
        if ($date >= new \DateTimeImmutable('2015-01-01'))
            $fareIndex = 5;
        if ($date >= new \DateTimeImmutable('2016-01-01'))
            $fareIndex = 6;

        return $fareIndex;
    }

	public static function getProductFare(
        $date,
        $adult,
        $zones
    )
	{
		$fareType = $adult ? 'adult' : 'concession';
        
        $fareZone = 'zone1+2';
        if (!($zones & Event::ZONE_1))
            $fareZone = 'zone2';
        else if (!($zones & Event::ZONE_2))
            $fareZone = 'zone1';

        $fareIndex = self::getFareIndex($date);
    
        $fare = self::$fares
            [$fareType]
            [$fareZone]
            [$fareIndex];

        return $fare;
	}

    public static function getDiscount($date, $adult) 
    {
        $fareIndex = self::getFareIndex($date);

         // Check for weekend / public holiday and use lowest possible fare
        $dow = date("w", $date->getTimestamp());

        $fare = [];
        if ($dow == 0 || $dow == 6) {
            $fare['discount'] = 'weekend';
            $fare['cost'] = self::$fares['weekend'][$fareIndex];
        } 
        else if (in_array($date->format("Ymd"), self::$publicHolidays)) {
            $fare['discount'] = 'publicHoliday';
            $fare['cost'] = self::$fares['weekend'][$fareIndex];
        }

        return $fare;
    }

    protected static function fareIs($date, $fare, $type)
    {
        if ($type != "adult" && $type != "concession")
            throw new \InvalidArgumentException("Fare type must be adult or concession");

        $fareIndex = self::getFareIndex($date);
        foreach (self::$fares[$type] as $f) {
            if ($f[$fareIndex] == $fare)
                return true;
        }
        return false;
    }

    public static function isAdultFare($date, $fare)
    {
        return self::fareIs($date, $fare, "adult");
    }

    public static function isConcessionFare($date, $fare)
    {
        return self::fareIs($date, $fare, "concession");
    }

    private static $publicHolidays = [
        '20100101',
        '20100126',
        '20100308',
        '20100402',
        '20100403',
        '20100405',
        '20100406',
        '20100426',
        '20100614',
        '20101102',
        '20101227',
        '20101228',

        '20110103',
        '20110126',
        '20110314',
        '20110422',
        '20110423',
        '20110425',
        '20110426',
        '20110613',
        '20111101',
        '20111227',
        '20111226',

        '20120102',
        '20120126',
        '20120312',
        '20120406',
        '20120407',
        '20120409',
        '20120425',
        '20120611',
        '20121106', 
        '20121225', 
        '20121226', 

        '20130101', 
        '20130126', 
        '20130128', 
        '20130311', 
        '20130329', 
        '20130330', 
        '20130401', 
        '20130425', 
        '20130610', 
        '20131105', 
        '20131225', 
        '20131226', 

        '20140101', 
        '20140126', 
        '20140127', 
        '20140310', 
        '20140418', 
        '20140419', 
        '20140421', 
        '20140425', 
        '20140609', 
        '20141104', 
        '20141225', 
        '20141226',

        '20150101',
        '20150126',
        '20150309',
        '20150403',
        '20150404',
        '20150405',
        '20150406',
        '20150425',
        '20150608',
        '20151103',
        '20151225',

        '20160101',
        '20160126',
        '20160214',
        '20160314',
        '20160325',
        '20160326',
        '20160327',
        '20160328',
        '20160425',
        '20160508',
        '20160613',
        '20160904',
        '20160930',
        '20161101',
        '20161225',
        '20161226',
        '20161227'

    ];

}
