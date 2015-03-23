<?php
namespace MykiLeaks\Test;

use MykiLeaks\Auditor;
use MykiLeaks\Event;
use MykiLeaks\Product;

class Acceptance extends \PHPUnit_Framework_TestCase
{
    private $auditor;

    public function setUp()
    {
        $this->auditor = new Auditor();
    }

    /**
     * @dataProvider dataTestCalculatedProducts
     */
    function compareProducts($date, $expectedProducts, $tripData)
    {
        $events = Event::eventsFromStatement($tripData);
        $allResults = $this->auditor->audit($events)['results'];
        $this->assertArrayHasKey($date, $allResults);

        $products = $allResults[$date]['products'];
        $this->assertEquals(count($expectedProducts), count($products));

        foreach ($expectedProducts as $idx => $exp) {
            $actual = $products[$idx];
            $this->assertEquals($exp['time'], $actual['time']);
            $this->assertEquals($exp['adult'], $actual['adult']);
            $this->assertEquals($exp['zone'], $actual['zone']);
        }
    }

    function testSingleTripZone1()
    {
        $this->compareProducts(
            "20140725", 
            [
                [
                    "time" => new \DateTimeImmutable("2014-07-25 07:01:00"),
                    "adult" => true,
                    "zone" => Event::ZONE_1,
                ]
            ],
            <<< EOF
24/07/2014 07:00:00   Top up     Train   1  Thornbury Station       $20.00   -      $20.00
25/07/2014 07:01:00   Touch on   Train   1  Thornbury Station       -        -      -
25/07/2014 08:00:00   Touch off  Train   1  Southern Cross Station  -        $3.58  $16.42
26/07/2014 09:00:00   Touch on   Train   1  Southern Cross Station  -        -      -
EOF
        );
    }

    function testMultipleTripsZone1Within2Hrs()
    {
        $this->compareProducts(
            "20140725", 
            [
                [
                    "time" => new \DateTimeImmutable("2014-07-25 07:01:00"),
                    "adult" => true,
                    "zone" => Event::ZONE_1,
                ]
            ],
            <<< EOF
24/07/2014 07:00:00   Top up     Train   1  Thornbury Station       $20.00   -      $20.00
25/07/2014 07:01:00   Touch on   Train   1  Thornbury Station       -        -      -
25/07/2014 08:00:00   Touch off  Train   1  Southern Cross Station  -        $3.58  $16.42
25/07/2014 08:10:00   Touch on   Train   1  Southern Cross Station  -        -      -
25/07/2014 08:59:00   Touch off  Train   1  Richmond Station        -        -      -
26/07/2014 09:00:00   Touch on   Train   1  Richmond Station        -        -      -
EOF
        );
    }

    function testMultipleTripsZone1Over2HrsAfter6pm()
    {
        $this->compareProducts(
            "20140725", 
            [
                [
                    "time" => new \DateTimeImmutable("2014-07-25 18:01:00"),
                    "adult" => true,
                    "zone" => Event::ZONE_1,
                ]
            ],
            <<< EOF
24/07/2014 07:00:00   Top up     Train   1  Thornbury Station       $20.00   -      $20.00
25/07/2014 18:01:00   Touch on   Train   1  Thornbury Station       -        -      -
25/07/2014 19:00:00   Touch off  Train   1  Southern Cross Station  -        $3.58  $16.42
25/07/2014 19:10:00   Touch on   Train   1  Southern Cross Station  -        -      -
25/07/2014 21:00:00   Touch off  Train   1  Richmond Station        -        -      -
26/07/2014 09:00:00   Touch on   Train   1  Richmond Station        -        -      -
EOF
        );
    }

    function testSingleTripZone1And2()
    {
         $this->compareProducts(
            "20140725", 
            [
                [
                    "time" => new \DateTimeImmutable("2014-07-25 07:01:00"),
                    "adult" => true,
                    "zone" => Event::ZONE_1,
                ], 
                [
                    "time" => new \DateTimeImmutable("2014-07-25 07:01:00"),
                    "adult" => true,
                    "zone" => Event::ZONE_2,
                ],
            ],
            <<< EOF
24/07/2014 07:00:00   Top up     Train   1  Thornbury Station       $20.00   -      $20.00
25/07/2014 07:01:00   Touch on   Train   1  Thornbury Station       -        -      -
25/07/2014 08:00:00   Touch off  Train   2  South Morang Station    -        $6.06  $13.94
26/07/2014 09:00:00   Touch on   Train   1  Richmond Station        -        -      -
EOF
        );
    }

    function testSingleTripZone2()
    {
        $this->compareProducts(
            "20140725", 
            [
                [
                    "time" => new \DateTimeImmutable("2014-07-25 07:01:00"),
                    "adult" => true,
                    "zone" => Event::ZONE_2,
                ]
            ],
            <<< EOF
24/07/2014 07:00:00   Top up     Train   2  South Morang Station    $20.00   -      $20.00
25/07/2014 07:01:00   Touch on   Train   2  South Morang Station    -        -      -
25/07/2014 08:00:00   Touch off  Train   2  Keon Park Station       -        $2.48  $17.52
26/07/2014 09:00:00   Touch on   Train   2  Keon Park               -        -      -
EOF
        );
    }

    function testSingleTripZone2FromOverlap()
    {
        $this->compareProducts(
            "20140725", 
            [
                [
                    "time" => new \DateTimeImmutable("2014-07-25 07:01:00"),
                    "adult" => true,
                    "zone" => Event::ZONE_2,
                ]
            ],
            <<< EOF
24/07/2014 07:00:00   Top up     Train   1/2  Preston Station       $20.00   -      $20.00
25/07/2014 07:01:00   Touch on   Train   1/2  Preston Station       -        -      -
25/07/2014 08:00:00   Touch off  Train   2    Keon Park Station     -        $2.48  $17.52
26/07/2014 09:00:00   Touch on   Train   2    Keon Park             -        -      -
EOF
        );
    }

    function testSingleTripZone2ToOverlap()
    {
        $this->compareProducts(
            "20140725", 
            [
                [
                    "time" => new \DateTimeImmutable("2014-07-25 07:01:00"),
                    "adult" => true,
                    "zone" => Event::ZONE_2,
                ]
            ],
            <<< EOF
24/07/2014 07:00:00   Top up     Train   2    Keon Park Station     $20.00   -      $20.00
25/07/2014 07:01:00   Touch on   Train   2    Keon Park Station     -        -      -
25/07/2014 08:00:00   Touch off  Train   1/2  Preston Station       -        $2.48  $17.52
26/07/2014 09:00:00   Touch on   Train   1/2  Preston Station       -        -      -
EOF
        );
    }

    function testSingleTripZone2CrossingZone1()
    {
        $this->compareProducts(
            "20140725", 
            [
                [
                    "time" => new \DateTimeImmutable("2014-07-25 07:01:00"),
                    "adult" => true,
                    "zone" => Event::ZONE_2,
                ],
                [
                    "time" => new \DateTimeImmutable("2014-07-25 07:01:00"),
                    "adult" => true,
                    "zone" => Event::ZONE_1,
                ]
            ],
            <<< EOF
24/07/2014 07:00:00   Top up     Train   2    Hurstbridge Station   $20.00   -      $20.00
25/07/2014 07:01:00   Touch on   Train   2    Hurstbridge Station   -        -      -
25/07/2014 08:00:00   Touch off  Train   2    Epping Station        -        $6.06  $13.94
26/07/2014 09:00:00   Touch on   Train   2    Epping Station        -        -      -
EOF
        );
    }
}
