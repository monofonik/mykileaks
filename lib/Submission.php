<?php

namespace MykiLeaks;

class Submission
{

	public $auditor;
    public $dataLayer;

    public $md5;
	public $data;

	public function __construct($auditor, $dataLayer=null)
	{
        $this->auditor = $auditor;
		$this->dataLayer = $dataLayer;
	}
	
	public function submit($data, $filename=null, $filetime=null)
	{
        $this->data = $data;

        // Hack to fix empty description on some reimbursements
        $pattern = "/Reimbursement[[:blank:]]*-[[:blank:]]*Reimbursement/";
        $data = preg_replace($pattern, "Reimbursement               -       -     Reimbursement", $data);

        // Regex for event line
        $pattern = '/^((\d\d\/){2}\d{4}[[:blank:]]+(\d\d:){2}\d\d)[[:blank:]]{2,}(.*?)[[:blank:]]{2,}(.*?)[[:blank:]]{2,}(.*?)[[:blank:]]{2,}(.*?)[[:blank:]]{2,}(.*?)[[:blank:]]{2,}(.*?)[[:blank:]]{2,}(.*?)$/m';

        $md5 = null;
        
        // Parse text and create Events
        if (preg_match_all($pattern, $data, $matches)) {

            $events = array();
            $prevRaw = null;
            $md5 = md5(json_encode($matches));

            $n = count($matches[0]);
            for ($i = 0; $i < $n; $i++) {

            	// Check for a dupe
            	$raw = $matches[0][$i];
            	if ($raw == $prevRaw)
            		continue;

                $date =  \DateTime::createFromFormat(
                    'd/m/Y H:i:s', 
                    $matches[1][$i]
                    //new \DateTimeZone("Australia/Melbourne")
                );

                if (!$date)
                    throw new SubmissionException('Unable to parse date from statement event.');

                $event = array(
                    'sequence'=>$i,
                    'raw'=>$raw,
                    'timestamp'=> $date,
                    'type'=>$matches[4][$i],
                    'service'=>$matches[5][$i],
                    'zone'=>$matches[6][$i],
                    'description'=>$matches[7][$i],
                    'credit'=> $matches[8][$i] == '-' ? null : floatval(ltrim($matches[8][$i], '$')),
                    'debit'=> $matches[9][$i] == '-' ? null : floatval(ltrim($matches[9][$i], '$')),
                    'balance'=> $matches[10][$i] == '-' ? null : floatval(ltrim($matches[10][$i], '$')),
                );

	            $events[] = new Event($event, $i);
	            $prevRaw = $raw;
            }

            // Determine if we're dealing with a concession statement
            $adult = $this->isAdult($events);

            // Audit the events and record results
            $audit = $this->auditor->audit($events, $adult);

            $inserted = $this->dataLayer->logSubmission(
                $filename,
                $md5, 
                $adult, 
                $audit,
                $filetime
            ); 

            return ["audit" => $audit, "inserted" => $inserted];

        } else
            throw new SubmissionException('No trip data found');
	}

    /**
     * Attempts to determine if the submitted file contains data for an adult or concession card
     * using the fare table. If in doubt assumes concession
     */
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

class SubmissionException extends \Exception
{

}
