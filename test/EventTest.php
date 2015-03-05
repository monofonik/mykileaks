<?php
namespace MykiLeaksTest;

use MykiLeaks\Event as Event;

class EventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataTestEventTypeParsedCorrectly
     */
    function testEventTypeParsedCorrectly($event, $expectedType)
    {
        $ev = new Event(array_merge($this->emptyEvent(), $event));
        $this->assertEquals($expectedType, $ev->type);
    }

    function dataTestEventTypeParsedCorrectly()
    {
        return [
            [["type"=>"Touch off"], Event::TYPE_TOUCHOFF],
            [["type"=>"Touch on"], Event::TYPE_TOUCHON],
            [["type"=>"Fare Product Sale"], Event::TYPE_FARE_PRODUCT_SALE],
        ];
    }

    private function emptyEvent()
    {
        return [
            "raw"=>"",
            "timestamp"=>new \DateTime(),//"2000-01-01 00:00:00",
            "type"=>"",
            "service"=>"",
            "zone"=>"",
            "description"=>"",
            "credit"=>"",
            "debit"=>"",
            "balance"=>"",
        ];
    }
}
