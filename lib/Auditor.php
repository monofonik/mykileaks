<?php

namespace MykiLeaks;

class Auditor {
    
    public $adult = true;

    public function audit($events, $adult=true)
    {
        $this->adult = $adult;

        $changed = true;
        while ($changed) { $changed = $this->fixOrder($events); }
        $this->markDuplicates($events);

        $days = $this->groupEvents($events);
        
        $summary = [
            "totalOvercharged"=>0,
            "totalReimbursement"=>0,
            "assessable"=>0,
            "nonAssessable"=>0,
        ];

        $results = [];

        foreach ($days as $date => $group) {

            $errors = $group->getErrors();
            $services = $group->getServices();
            $zones = $group->getZones();
            $products = $group->getProducts();
            $expectedFare = $group->getExpectedFare();
            $discrepancy = round($expectedFare['cost'] * -1 - $group->travel, 2);

            // Use product array notation
            $products = array_map(function($e) { return $e->asArray(); }, $products);
                
            // Convert event timestamps to MongoDates
            foreach ($group->events as &$e) {
                $e->timestamp = new \MongoDate($e->timestamp->getTimestamp());
            }

            // Update the totals
            if ($errors)
                $summary['nonAssessable']++;
            else {
                $summary['assessable']++;

                if ($discrepancy > 0)
                    $summary['totalOvercharged'] += $discrepancy;
            }

            $summary['totalReimbursement'] += $group->totalReimbursements();
           
            $results[$date] = [
                '_id' => md5(json_encode($group->events)),
                'date' => $group->date,
                'assessable' => !(boolean)$errors,
                'errors' => $group->getErrors(),

                'adult' => $this->adult,
                'hasTravel' => $group->hasTravel(),
                'services' => [
                    'train' => (boolean)($services & Event::SERVICE_TRAIN),
                    'tram' => (boolean)($services & Event::SERVICE_TRAM),
                    'bus' => (boolean)($services & Event::SERVICE_BUS),
                ],
                'zones' => [
                    'z1' => (boolean)($zones & Event::ZONE_1),
                    'z2' => (boolean)($zones & Event::ZONE_2),
                ],

                'products' => $products,
                'expectedFare' => $expectedFare,

                'openingBal' => $group->openingBal,
                'closingBal' => $group->closingBal,

                'travel' => $group->travel,
                'nonTravel' => $group->nonTravel,
                'reimbursements' => $group->reimbursements,

                // 'actualFare' => $group->travel,
                'discrepancy' => $discrepancy,
                'balanceErrors' => $group->balanceErrors,

                'events' => $group->events,
            ];
        }
        return [
            // 'id'=>$
            'summary'=>$summary, 
            'results'=>$results,
        ];
    }

    /**
     * Corrects the order of the event list. Occasionally when a passenger touches on after not 
     * touching of the previous day, the touch off (defaut fare) incorrectly appears after the 
     * touch on. Returns true if order was changed
     */
    protected function fixOrder(&$events)
    {
        $changed = false;

        for ($i = 0; $i < count($events) - 1; $i++)
        {
            $cur = $events[$i];
            $next = $events[$i+1];

            $dif = $next->timestamp->getTimestamp() - $cur->timestamp->getTimestamp();

            // Change the order if within 3secs and ensure touch off is first / touch on is last
            if ($dif <= 3 && (($cur->type & Event::TYPE_TOUCHON && $next->type ^ Event::TYPE_TOUCHON) ||
                ($next->type & Event::TYPE_TOUCHOFF && $cur->type ^ Event::TYPE_TOUCHOFF))) {
                
                $events[$i] = $next;
                $events[$i+1] = $cur;
                $cur->reordered = $next->reordered = true;
                $changed = true;
            }
        }

        return $changed;
    }

    protected function markDuplicates(&$events)
    {
        for ($i=0; $i < count($events) - 1; $i++) {
            if ($events[$i]->raw == $events[$i+1]->raw) {
                $events[$i+1]->duplicate = true;
            }
        }
    }

    /**
     * Groups events according to days. Considers next day touch-offs and after midnight travel
     */
    protected function groupEvents($events)
    {
        $days = [];
        
        $closingBal = null;
        $curDate = null;
        $curGroup = null;

        $touchedOff = true;

        foreach ($events as $e) {

            // The travel date rolls over at 3am for the purposes of ticketing.
            $int = new \DateInterval("PT4H");
            $date = clone $e->timestamp;
            $date = $date->sub($int)->format("Ymd");

            $touchOn = $e->type & Event::TYPE_TOUCHON;
            $touchOff = $e->type & Event::TYPE_TOUCHOFF;

            // Should we create a new event group
            if ((!$curDate || $curDate != $date) && ($touchOn || ($touchedOff && !$e->defaultFare))) {
                
                // Get closing balance from previous group
                if ($days)
                    $closingBal = end($days)->closingBal;

                if ($curGroup && $curDate)
                    $days[$curDate] = new EventGroup(
                        \DateTime::createFromFormat("Ymd", $curDate), 
                        $this->adult,
                        $curGroup, 
                        $closingBal);
   
                $curDate = $date;
                $curGroup = [];
                $touchedOff = true;
            }

            // Update touched off status
            if ($touchOn)
                $touchedOff = false;
            else if ($touchOff)
                $touchedOff = true;

            $curGroup[] = $e;
        }

        return $days;
    }
}
