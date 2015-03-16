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
    function testCalculatedProducts($date, $expectedProducts, $tripData)
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

    function dataTestCalculatedProducts()
    {
        return [
            [
                // single product / single trip
                "20140725",
                [[
                    "time" => new \DateTimeImmutable("2014-07-25 07:01:00"),
                    "adult" => true,
                    "zone" => 1,
                ]],
                <<< EOF
24/07/2014 07:00:00   Top up     Train   1  Thornbury Station       $20.00   -      $20.00
25/07/2014 07:01:00   Touch on   Train   1  Thornbury Station       -        -      -
25/07/2014 08:00:00   Touch off  Train   1  Southern Cross Station  -        $3.58  $16.42
26/07/2014 09:00:00   Touch on   Train   1  Southern Cross Station  -        -      -
EOF
            ],

            [
                // single product / multiple trips
                "20140725",
                [[
                    "time" => new \DateTimeImmutable("2014-07-25 07:01:00"),
                    "adult" => true,
                    "zone" => 1,
                ]],
                <<< EOF
24/07/2014 07:00:00   Top up     Train   1  Thornbury Station       $20.00   -      $20.00
25/07/2014 07:01:00   Touch on   Train   1  Thornbury Station       -        -      -
25/07/2014 08:00:00   Touch off  Train   1  Southern Cross Station  -        $3.58  $16.42
25/07/2014 08:10:00   Touch on   Train   1  Southern Cross Station  -        -      -
25/07/2014 08:59:00   Touch off  Train   1  Richmond Station        -        -      -
26/07/2014 09:00:00   Touch on   Train   1  Richmond Station        -        -      -
EOF
            ],

        ];
    }
}
