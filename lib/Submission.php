<?php

namespace MykiLeaks;

class Submission
{

	public $auditor;
    public $dataLayer;
    public $md5;

	public function __construct($auditor, $dataLayer=null)
	{
        $this->auditor = $auditor;
		$this->dataLayer = $dataLayer;
	}
	
	public function submit($data, $submissionTime=null)
	{
        if (!$submissionTime)
            $submissionTime = new \DateTime("now", new \DateTimeZone("Australia/Melbourne"));

        if (!$submissionTime instanceof \DateTime)
            throw new \InvalidArgumentException();

        $this->md5 = md5($data);
        $events = Event::eventsFromStatement($data);

        if ($events) {       
            $adult = $this->isAdult($events);
            $audit = $this->auditor->audit($events, $adult);

            $inserted = false;
            if ($this->dataLayer) {
                $inserted = $this->dataLayer->logSubmission(
                    $this->md5, 
                    $adult, 
                    $audit,
                    $submissionTime
                );
            }
            return ["audit" => $audit, "inserted" => $inserted];
        } 
        else
            throw new InvalidArgumentException('No trip data found');
	}

    /**
     * Sneaky function to intelligently guess if a submission is adult
     * or concession. Defaults to adult when a substantiated guess can't 
     * be made.
     **/
    public function isAdult($events) {
        $nAdult = 0;
        $nConc = 0;

        foreach ($events as $date => $e) {
            if ($e->debit && FareTable::isConcessionFare($e->timestamp, $e->debit))
                $nConc++;
            else if ($e->debit && FareTable::isAdultFare($e->timestamp, $e->debit))
                $nAdult++;
        }
        return $nAdult >= $nConc;
    }
}

