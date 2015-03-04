<?php
namespace MykiLeaks;

interface DataLayer 
{
    function logSubmission($md5, bool $adult, $audit, \DateTime $submitted);
}
