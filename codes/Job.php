<?php
interface Job {
	public function do_job(string $arg, string $recipient_group);
}