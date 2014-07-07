<?php

namespace MykiLeaks;

class DataLayer 
{

    protected $db;

    const QUOTED_TOUCHONS_PER_WEEK = 5500000;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function purgeAll()
    {
        $this->db->result->remove();
        $this->db->submission->remove();
    }

    public function logSubmission(
        $filename,
        $md5, 
        $adult,
        $audit,
        $submitted=null)
    {

        if (!$submitted)
            $submitted = new \DateTime();

        $doc = [
            "_id" => $md5,
            "filename" => $filename,
            "submitted" => new \MongoDate($submitted->getTimestamp()),
            "adult" => $adult,
            "summary" => $audit['summary'],
            "ip" => $_SERVER['REMOTE_ADDR'], // TODO: pass from controller
        ];

        $coll = $this->db->submission;

        $inserted = false;
        try {
            $coll->insert($doc, ["safe"=>true]);
            $inserted = true;
        } catch (\MongoCursorException $ex) { }

        if ($inserted)
            $this->logResults($md5, $audit['results']);

        return $inserted;
    }

    public function createTempLink($audit)
    {
        $md5 = md5(serialize($audit));
        $coll = $this->db->tempAudit;
        $coll->insert([
            "_id" => $md5,
            "expires" => new \MongoDate(time() + 60 * 10), // TODO: config param
            "audit" => $this->packDates($audit),
        ]);
        return $md5;
    }

    public function getTempAudit($id)
    {
        $doc = $this->unpackDates(iterator_to_array(
            $this->db->tempAudit->find([
                "_id" => $id
            ])
        ));
        if (count($doc) == 1)
            return $doc[$id]['audit'];
        else
            return false;
    }

    public function getFrontPageStats()
    {
        $months = 12;
        $reportstart = mktime(0, 0, 0, date("m")-$months, date("d"), date("Y"));

        $moneyPass = $this->getChartData("monthlyMoneyPass", false, $reportstart);
        $totalMoney = 0;
        $totalPass = 0;
        foreach ($moneyPass as $m) {
            $totalMoney += $m['value']['mykiMoneyTouchOns'];
            $totalPass += $m['value']['mykiPassTouchOns'];    
        }

        $moneyToPass = $totalMoney + $totalPass == 0 ? 0 : $totalMoney / ($totalMoney + $totalPass);

        $totals = [
            "touchOns" => 0,
            "overcharges" => 0,
            "overchargesTotal" => 0,
            "reimbursementsTotal" => 0
        ];

        $data = $this->getChartData("monthlySummary", false, $reportstart);
        foreach ($data as $d) {
            $totals["touchOns"] += $d['value']['touchOns'];
            $totals["overcharges"] += $d['value']['overcharges'];
            $totals["overchargesTotal"] += $d['value']['overchargesTotal'];
            $totals["reimbursementsTotal"] += $d['value']['reimbursementsTotal'];
        }

        $touchOnsPerWeek = self::QUOTED_TOUCHONS_PER_WEEK * $moneyToPass;
        $overchargesPerDay = $totals['overcharges'] / $totals['touchOns'] * $touchOnsPerWeek / 7;
        $overchargesTotalPerWeek = $totals['overchargesTotal'] / $totals['touchOns'] * $touchOnsPerWeek;
        $reimbursementsTotalPerWeek = $totals['reimbursementsTotal'] / $totals['touchOns'] * $touchOnsPerWeek;

        $result = [
            "months" => $months,
            "ocpd" => $overchargesPerDay,
            "octpw" => $overchargesTotalPerWeek,
            "rtpw" => $reimbursementsTotalPerWeek,
            "moneyToPass" => $moneyToPass
        ];

        return $result;
    }

    public function getChartData($name, $totalOnly = false, $startTime = null)
    {
        if (!$startTime)
            $startTime = strtotime("2011-07-01 00:00:00");

        // From start time to before current month
        $startTime = new \MongoDate($startTime);
        $endTime = new \MongoDate(mktime(0, 0, 0, date("m"), 1, date("Y")));

        $filter = ["_id" => [
            '$gte' => $startTime,
            '$lt' => $endTime,
        ]];

        if ($totalOnly)
            $filter = ['_id' => 'total'];

        return $this->unpackDates(iterator_to_array(
            $this->db->$name->find($filter)->sort(["_id" => 1])
        ));

    }

    protected function logResults($submissionId, $results)
    {
        $coll = $this->db->result;

        foreach ($results as $date => $r) {
            $r['submissionId'] = $submissionId;
            try {
                $coll->insert($this->packDates($r), ["safe"=>true]);
            } catch (\MongoCursorException $ex) { }
        }
    }

    protected function packDates($object)
    {
        if (is_array($object))
            return array_map([$this, "packDates"], $object);

        else if ($object instanceof \DateTime)
            return new \MongoDate($object->getTimestamp());

        else
            return $object;
    }

    protected function unpackDates($object)
    {
        if (is_array($object))
            return array_map([$this, "unpackDates"], $object);

        else if ($object instanceof \MongoDate)
            return (new \DateTime())->setTimestamp($object->sec);
        
        else
            return $object;
    }

}
