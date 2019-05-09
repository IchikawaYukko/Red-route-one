<?php
require_once 'Job.php';
require_once 'Revel.php';
require_once 'RR1_Mail.php';
require_once 'RR1_Settings.php';

class RevelMail implements Job {
	private $settings, $mail_settings;

	public function __construct() {
		$this->settings = new RR1_Settings('settings.json');

		$this->credentials = $this->settings->get_settings('auth_credentials', 'Revel'); 
	}

	public function do_job(string $timeslot, string $recipient_group) {
		$this->mail_settings = $this->settings->get_settings('mail_settings', $recipient_group);
		$attach_file = $this->download($timeslot);
		$this->send($attach_file, $timeslot);
	}

	private function download(string $timeslot) : array {
		$revel = new Revel($this->credentials->username, $this->credentials->password, $this->credentials->venue_name);

		$range = $revel->get_range_by_timeslot($timeslot);
		$filesuffix = $revel->get_filename_suffix_by_timeslot($timeslot);

		$file = [];
		if(
			$timeslot == 'lunch'    ||
			$timeslot == 'tea'      ||
			$timeslot == 'dinner'   ||
			$timeslot == 'wholeday' ||
			$timeslot == 'monthly'
		) {
			$file[] = [
				'filename'  =>  "SalesSummary{$filesuffix}.pdf",
				'data'      =>  $revel->get_sales_summary($range['range_from'], $range['range_to']),
			];
		}

		if($timeslot != 'weekly') {
			if(!$revel->product_mix_is_empty($range['range_from'], $range['range_to'])) {
				$file[] = [
					'filename'  =>  "ProductMix{$filesuffix}.pdf",
					'data'      =>  $revel->get_product_mix($range['range_from'], $range['range_to']),
				];
			}
		}

		if($timeslot == 'weekly') {
			$file[] = [
				'filename'  =>  "Total_SalesSummary{$filesuffix}.pdf",
				'data'      =>  $revel->get_sales_summary($range['range_from'], $range['range_to']),
			];
			$file[] = [
				'filename'  =>  "Bar_SalesSummary{$filesuffix}.pdf",
				'data'      =>  $revel->get_sales_summary($range['range_from'], $range['range_to'], 'bar'),
			];
			$file[] = [
				'filename'  =>  "Sushi_SalesSummary{$filesuffix}.pdf",
				'data'      =>  $revel->get_sales_summary($range['range_from'], $range['range_to'], 'sushi'),
			];
			$file[] = [
				'filename'  =>  "Main_SalesSummary{$filesuffix}.pdf",
				'data'      =>  $revel->get_sales_summary($range['range_from'], $range['range_to'], 'main'),
			];
		}

		return $file;
	}

	private function send(array $file, string $timeslot) {
		global $body_footer;
	
		$to = [];
		foreach($this->mail_settings->to as $address) {
			$to[] = new EmailAddress($address);
		}

		$from = new EmailAddress($this->mail_settings->from);
		$reply_to = new EmailAddress($this->mail_settings->reply_to);

		switch($timeslot) {
			case 'lunch':
				$subject	= 'Lunch time';
				$message	= "Today's $timeslot time";
				break;
			case 'dinner':
				$subject	= 'Dinner time';
				$message	= "Today's $timeslot time";
				break;
			case 'weekly':
				$subject	= 'Weekly Sales Summary';
				$message	= 'Week'.date('W')." Sales Summary\n";
				break;
			case 'wholeday':
				$subject	= 'Wholeday';
				$message	= "Today's $timeslot";
				break;
			case 'monthly':
				$subject	= 'Monthly';
				$message	= "$timeslot";
				break;
		}
	
		if($timeslot != 'weekly') {
			if(!$this->hasProductMix($file)) {
				$subject .= ' Sales Summary';
				$message .= " Sales Summary.\nNo orders and Product Mix in $timeslot time.\n";
			} else {
				$subject .= ' Sales Summary & Product Mix';
				$message .= " Sales Summary and Product mix.\n";
			}
		}

		$mail = new RR1Mail();

		foreach($file as $f) {
			$mail->attach_file($f['filename'], $f['data']);
		}
		$message .= $body_footer;

		$mail->set_address($to, $from, $reply_to);
		$mail->set_body($message, $subject);
		$mail->send();
	}
	
	private function hasProductMix(array $file) : bool {
		return count($file) != 1;
	}
}