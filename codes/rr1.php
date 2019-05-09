<?php
// run this file 10 minutely by cron. the scheduled job will do.
//
// example: */10 * * * * php rr1.php
// or
// example: */10 * * * * docker exec -i rr1-test scl enable rh-php71 'php /rr1.php'
//
// Also can be force to run the job like this.
// php rr1.php <JobClassName> <JobArgs> <JobRecipient>

require_once("settings.php");
require_once('Scheduler.php');

$sc = new Scheduler('jobs.json');
if(isset($argv[1]) && isset($argv[2]) && isset($argv[3])) {
	$sc->force_run($argv[1], $argv[2], $argv[3]);
} else {
	$sc->run();
}