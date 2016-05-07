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
}
