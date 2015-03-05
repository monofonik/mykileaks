<?php
namespace MykiLeaks;

interface DataLayer 
{
    function logSubmission($md5, $adult, $audit, \DateTime $submitted);
}
