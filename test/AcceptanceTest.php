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
    function testCalculatedProducts($file, $date, $expectedProducts)
    {
        $data = file_get_contents(__DIR__."/data/$file.txt");
        $events = Event::eventsFromStatement($data);
        $results = $this->auditor->audit($events)['results'];
        $products = $results[$date]['products'];

        $this->assertEquals(count($expectedProducts), count($products));
        foreach ($expectedProducts as $idx => $exp) {
            $this->assertEquals($exp->asArray(), $products[$idx]);
        }
    }

    function dataTestCalculatedProducts()
    {
        return [
            // file, overcharged, [product]

            ["a", "20140724", [
                new Product(new \DateTimeImmutable("24-07-2014 22:28:53"), !!"adult", 1)
            ]],

            
        ];
    }
}
