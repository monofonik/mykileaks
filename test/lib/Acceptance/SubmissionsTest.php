<?php
namespace MykiLeaks\Test;

use MykiLeaks\Event;
use MykiLeaks\Test\Acceptance;

class SubmissionsTest extends Acceptance
{
    function testThreeProductsMultipleZones()
    {
        $this->compareProducts(
            "20140915", 
            [
                [
                    "time" => new \DateTimeImmutable("2014-09-15 12:48:27"),
                    "adult" => true,
                    "zone" => Event::ZONE_1,
                ],
                [
                    "time" => new \DateTimeImmutable("2014-09-15 12:48:27"),
                    "adult" => true,
                    "zone" => Event::ZONE_2,
                ],
                [
                    "time" => new \DateTimeImmutable("2014-09-15 15:03:05"),
                    "adult" => true,
                    "zone" => Event::ZONE_2,
                ]
            ],
            <<< EOF
14/09/2014 11:30:48   Touch off                 Train  2    Lilydale Station      -   $2.42  $45.12
14/09/2014 17:06:12   Touch on                  Train  2    Lilydale Station      -   -      -     
14/09/2014 19:16:35   Touch off (Default Fare)  Tram   1    Tram                  -   -      -     
14/09/2014 19:16:35   Touch on                  Tram   1    Tram                  -   -      -     
14/09/2014 19:16:48   Touch off                 Tram   1    Tram                  -   -      -     

15/09/2014 12:48:27   Touch on                  Tram   1    Tram                  -   -      -     
15/09/2014 13:54:15   Touch off (Default Fare)  Train  1    Northcote Station     -   $3.58  $41.54
15/09/2014 13:54:15   Touch on                  Train  1    Northcote Station     -   -      -     
15/09/2014 14:56:50   Touch off                 Train  1    Highett Station       -   $2.48  $39.06
15/09/2014 15:03:05   Touch on                  Bus    1/2  Bus                   -   -      -     
15/09/2014 15:04:30   Touch off                 Bus    2    Highett,Route 828in   -   $2.48  $36.58

16/09/2014 11:50:49   Touch on                  Train  1    Thornbury Station     -   -      -     
EOF
        );
    }
}
