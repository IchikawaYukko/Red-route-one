<?php
require_once 'Job.php';
require_once 'Revel.php';
require_once 'RR1_Mail.php';

class RevelMail implements Job {
	public function __construct() {

	}
	public function do_job(string $timeslot) {
		$attach_file = $this->download($timeslot);
		$this->send($attach_file, $timeslot);	
	}

	private function download(string $timeslot) : array {
		$revel = new Revel(REVEL_USERNAME, REVEL_PASSWORD, VENUE_NAME);
	
		$range = $revel->get_range_by_timeslot($timeslot);
		$filesuffix = $revel->get_filename_suffix_by_timeslot($timeslot);
	
		$file = array();
		if(
			$timeslot == 'lunch' ||
			$timeslot == 'tea' ||
			$timeslot == 'dinner' ||
			$timeslot == 'wholeday'
		) {
			$file[] = array(
				'filename'  =>  "SalesSummary{$filesuffix}.pdf",
				'data'      =>  $revel->get_sales_summary($range['range_from'], $range['range_to']),
			);
		}
	
		if($timeslot != 'weekly') {
			if(!$revel->product_mix_is_empty($range['range_from'], $range['range_to'])) {
				$file[] = array(
					'filename'  =>  "ProductMix{$filesuffix}.pdf",
					'data'      =>  $revel->get_product_mix($range['range_from'], $range['range_to']),
				);
			}
		}
	
		if($timeslot == 'weekly') {
			$file[] = array(
				'filename'  =>  "Total_SalesSummary{$filesuffix}.pdf",
				'data'      =>  $revel->get_sales_summary($range['range_from'], $range['range_to']),
			);
			$file[] = array(
				'filename'  =>  "Bar_SalesSummary{$filesuffix}.pdf",
				'data'      =>  $revel->get_sales_summary($range['range_from'], $range['range_to'], 'bar'),
			);
			$file[] = array(
				'filename'  =>  "Sushi_SalesSummary{$filesuffix}.pdf",
				'data'      =>  $revel->get_sales_summary($range['range_from'], $range['range_to'], 'sushi'),
			);
			$file[] = array(
				'filename'  =>  "Main_SalesSummary{$filesuffix}.pdf",
				'data'      =>  $revel->get_sales_summary($range['range_from'], $range['range_to'], 'main'),
			);
		}
	
		return $file;
	}

	private function send(array $file, string $timeslot) {
		global $body_footer;
	
		$addr = array(
		  'to'        =>  TO_ADDRESS,
		  'from'      =>  FROM_ADDRESS,
		  'reply_to'  =>  REPLY_TO_ADDRESS,
		);
	
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
	
		$message .= $body_footer;
	
		$mail = new RR1Mail();
		$mail->sendmail($addr, $subject, $message, $file);
	}
	
	private function hasProductMix(array $file) : bool {
		return count($file) != 1;
	}
}