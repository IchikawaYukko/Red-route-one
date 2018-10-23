<?php
require_once 'RevelMail.php';
require_once 'iZettleProMail.php';

class Scheduler {
	private $jobs;

	public function __construct(string $filename) {
		$json = file_get_contents($filename);
		$obj = json_decode($json);

		$this->jobs = $obj->jobs;
	}

	public function run() {
		date_default_timezone_set(TIME_ZONE);
		$hours = date('H');	$minutes = date('i'); $dayofweek = date('D');
	
		if(DEBUG) {
			$this->scheduler_write_log('scheduler entry');
		}
		foreach($this->jobs as $job) {
			if(	preg_match($job->dayofweek, $dayofweek)
				&& preg_match($job->hour, $hours)
				&& preg_match($job->minute, $minutes)) {

				$this->scheduler_write_log("{$job->job_class} {$job->job_arg}");

				$instance = new $job->job_class;
				return $instance->do_job($job->job_arg);
			}
		}
	}

	public function force_run($classname, $arg) {
		$this->scheduler_write_log("FORCE RUN $classname $arg");

		$instance = new $classname;
		return $instance->do_job($arg);
	}

	private function scheduler_write_log(string $log_message) : void {
		file_put_contents('scheduler.log', date('c ').$log_message."\n", FILE_APPEND);
	}
}