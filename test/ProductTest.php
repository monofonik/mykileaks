<?php
namespace MykiLeaks\Test;

use MykiLeaks\Product;

class ProductTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataTestIsActive
     */
    function testIsActive($productTime, $time, $expected)
    {
        $product = new Product(new \DateTimeImmutable($productTime), !!'adult', '1');
        $this->assertEquals($expected, $product->isActiveAt(new \DateTimeImmutable($time)));
    }

    function dataTestIsActive()
    {
        return [
            // between 2-3 hours with "next full hour" rule
            ["2010-03-01 08:10:00", "2010-03-01 10:59:00", true],

            // // greater than 3 hours with the "next full hour" rule
            ["2010-03-01 08:10:00", "2010-03-01 11:30:00", false],

            // between 2-3 hours without "next full hour" rule
            ["2015-01-02 08:50:00", "2015-01-02 11:30:00", false],

            // > 2 hours after 6pm
            ["2015-01-02 18:00:00", "2015-01-02 23:00:00", true],

            // pre 4am considered previous day
            ["2015-01-03 03:30:00", "2015-01-02 19:00:00", true],

        ];
    }

    /**
     * @dataProvider dataTestNextFullHour
     */
    function testNextFullHour($time, $expected)
    {
        $next = \MykiLeaks\Product::nextFullHour(new \DateTimeImmutable($time));
        $this->assertEquals(new \DateTimeImmutable($expected), $next);
    }

    function dataTestNextFullHour()
    {
        return [
            ["2015-01-02 00:03:00", "2015-01-02 01:00:00"],
            ["2015-01-02 00:00:00", "2015-01-02 01:00:00"],
            ["2015-01-02 00:59:22", "2015-01-02 01:00:00"],
            ["2015-01-02 12:00:00", "2015-01-02 13:00:00"],
            ["2015-01-02 23:50:59", "2015-01-03 00:00:00"],
            ["2015-12-31 23:10:01", "2016-01-01 00:00:00"],
        ];
    }
}
