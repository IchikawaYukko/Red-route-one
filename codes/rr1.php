<?php
// run this file 10 minutely by cron. the scheduled job will do.
//
// example: */10 * * * * php rr1.php
// or
// example: */10 * * * * docker exec -i rr1-test scl enable rh-php71 'php /rr1.php'

require_once("settings.php");
require_once('Scheduler.php');

$sc = new Scheduler('jobs.json');
if(isset($argv[1]) && isset($argv[2])) {
	if(DEBUG) {
		$sc->force_run($argv[1], $argv[2]);
	}
} else {
	$sc->run();
}