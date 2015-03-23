<?php

namespace MykiLeaks;

class Event
{
    /**
     * Event type, service and zone are used in bitwise comparisons during
     * fare calculations.
     **/
    const TYPE_UNKNOWN = 0;
    const TYPE_TOUCHOFF = 1;
    const TYPE_TOUCHON = 2;
    const TYPE_FARE_PRODUCT_SALE = 4;
    const TYPE_TOPUP = 8;

    const TYPE_REIMBURSEMENT_CALLCENTER = 16;
    const TYPE_REIMBURSEMENT_WEBSITE = 32;
    const TYPE_REIMBURSEMENT_FORM = 64;
    const TYPE_REIMBURSEMENT_OTHER = 128;

    const TYPE_CARD_PURCHASE = 256;

    const TYPE_TRAVEL = 7; // touch on | touch off | fare prod sale
    const TYPE_REIMBURSEMENT = 240; // any reimbursement

    const SERVICE_NONE = 0;
    const SERVICE_TRAIN = 1;
    const SERVICE_TRAM = 2;
    const SERVICE_BUS = 4;

    const ZONE_NONE = 0;
    const ZONE_1 = 1;
    const ZONE_2 = 2;
    const ZONE_1AND2 = 3;
    const ZONE_1OR2 = 4;
    
    const ZONE_UNSUPPORTED = 32;

    public $seq;
	public $timestamp;
    public $raw;
    public $type = 0;
    public $service = 0;
    public $zone = 0;
    public $description;
    public $credit;
    public $debit;
    public $balance;

    public $reordered = false;
    public $duplicate = false;
    public $defaultFare;

    /** Generated from individual fields - PDF conversion fucks with input formatting **/
    public $pretty = "";

    public function __construct($event, $seq=null)
    {
        $this->seq = $seq;
        $this->raw = $event['raw'];
        $this->timestamp = $event['timestamp'];

        if (preg_match("/^Touch off/", $event['type']))
            $this->type = self::TYPE_TOUCHOFF;
        else if (preg_match("/^Touch on/", $event['type']))
            $this->type = self::TYPE_TOUCHON;
        else if (preg_match("/^Fare Product Sale/", $event['type']))
            $this->type = self::TYPE_FARE_PRODUCT_SALE;
        else if (preg_match("/^Top up/", $event['type']))
            $this->type = self::TYPE_TOPUP;

        else if (preg_match("/^Reimbursement/", $event['type'])) {
            if (preg_match("/^Call Center/", $event['service']))
                $this->type = self::TYPE_REIMBURSEMENT_CALLCENTER;
            else if (preg_match("/^Website/", $event['service']))
                $this->type = self::TYPE_REIMBURSEMENT_WEBSITE;
            else if (preg_match("/^Form/", $event['service']))
                $this->type = self::TYPE_REIMBURSEMENT_FORM;
            else
                $this->type = self::TYPE_REIMBURSEMENT_OTHER;

        }

        else if (preg_match("/^Card Purchase/", $event['type']))
            $this->type = self::TYPE_CARD_PURCHASE;
        else
            $this->type = self::TYPE_UNKNOWN;

        if (preg_match("/^Train/", $event['service']))
            $this->service = self::SERVICE_TRAIN;   
        if (preg_match("/^Tram/", $event['service']))
            $this->service = self::SERVICE_TRAM; 
        if (preg_match("/^Bus/", $event['service']))
            $this->service = self::SERVICE_BUS; 

        if (preg_match('/^-$/', $event['zone']))
            $this->zone = self::ZONE_NONE;
        else if (preg_match('/^[1|2]$/', $event['zone']))
            $this->zone = (int)$event['zone'];
        elseif (preg_match("/^City$/", $event['zone']))
            $this->zone = self::ZONE_1;
        elseif (preg_match("/^1\/2$/", $event['zone']))
            $this->zone = self::ZONE_1OR2;
        else {
            $this->zone = self::ZONE_UNSUPPORTED;
        }

        $this->description = $event['description'];
        $this->credit = $event['credit'];
        $this->debit = $event['debit'];
        $this->balance = $event['balance'];

        $this->defaultFare = 
            (boolean)preg_match("/Default Fare/i", $event['type']);

        $pCredit = $event['credit'] ? number_format($event['credit'], 2) : $event['credit'];        
        $pDebit = $event['debit'] ? number_format($event['debit'], 2) : $event['debit'];
        $pBalance = $event['balance'] ? number_format($event['balance'], 2) : $event['balance'];

        $this->pretty = 
            str_pad(date_format($event['timestamp'], "Y-m-d H:i:s"), 22) .
            str_pad($event['type'], 30) .
            str_pad($event['service'], 12) .
            str_pad($event['description'], 28) .
            str_pad($event['zone'], 8, " ", STR_PAD_LEFT) .
            str_pad($pDebit ? number_format($pDebit, 2) : "", 9, " ", STR_PAD_LEFT) .
            str_pad($pCredit ? number_format($pCredit, 2) : "", 9, " ", STR_PAD_LEFT) .
            str_pad($pBalance ? number_format($pBalance, 2) : "", 9, " ", STR_PAD_LEFT);
    }

    static function eventsFromStatement($data)
    {
          // Hack to fix empty description on some reimbursements
        $pattern = "/Reimbursement[[:blank:]]*-[[:blank:]]*Reimbursement/";
        $data = preg_replace($pattern, "Reimbursement               -       -     Reimbursement", $data);

        // Regex for event line
        $pattern = '/^((\d\d\/){2}\d{4}[[:blank:]]+(\d\d:){2}\d\d)[[:blank:]]{2,}(.*?)[[:blank:]]{2,}(.*?)[[:blank:]]{2,}(.*?)[[:blank:]]{2,}(.*?)[[:blank:]]{2,}(.*?)[[:blank:]]{2,}(.*?)[[:blank:]]{2,}(.*?)$/m';
        $events = [];
        
        // Parse text and create Events
        if (preg_match_all($pattern, $data, $matches)) {

            $prevRaw = null;

            $n = count($matches[0]);
            for ($i = 0; $i < $n; $i++) {

                // Check for a dupe
                $raw = $matches[0][$i];
                if ($raw == $prevRaw)
                    continue;

                $date =  \DateTimeImmutable::createFromFormat(
                    'd/m/Y H:i:s', 
                    $matches[1][$i],
                    new \DateTimeZone("Australia/Melbourne")
                );

                if (!$date)
                    throw new InvalidArgumentException('Unable to parse date from statement event.');

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
            return $events;
        }
    }
}
