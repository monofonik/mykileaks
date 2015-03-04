<?php
namespace MykiLeaks;

require __DIR__."/../vendor/autoload.php";

date_default_timezone_set("Australia/Melbourne");

$usage = <<< EOF
Usage: mykileaks.php <statement>

EOF;

if (count($argv) != 2 || !file_exists($argv[1]))
    die($usage);

$statement = $argv[1];  
$pipes = [
    "pdftotext -layout -nopgbrk \"{$statement}\" -",
    "tail -r",
    "grep -G '^[0-9]\{2\}/[0-9]\{2\}/[0-9]\{4\} [0-9]\{2\}:[0-9]\{2\}:[0-9]\{2\}'",
];
$cmd = implode(" | ", $pipes);
$data = shell_exec($cmd);
$submission = new Submission(new Auditor());
echo json_encode($submission->submit($data));
