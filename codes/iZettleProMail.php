<?php
require_once 'Job.php';
require_once 'iZettlePro.php';
require_once 'iZettleProAggregate.php';
require_once 'RR1_Mail.php';
require_once 'RR1_Settings.php';
require_once 'EmailAddress.php';

require_once 'mail_footer_template.php';

//require_once 'dummy.php';

class iZettleProMail implements Job {
	private $settings, $mail_settings, $credential;
	private $izettle, $aggr;

	public function __construct() {
		$this->settings = new RR1_Settings('settings.json');

		$this->credentials = $this->settings->get_settings('auth_credentials', 'iZettlePro'); 
	}

	public function do_job(string $timeslot, string $recipient_group) {
		$this->mail_settings = $this->settings->get_settings('mail_settings', $recipient_group);
		date_default_timezone_set(TIME_ZONE);
		
		$this->izettle = new iZettlePro($this->credentials->username, $this->credentials->password);
		$this->aggr = new iZettleProAggregate();

		$func = 'download_'.$timeslot;
		$message = $this->$func();

		$this->send($message);
	}

	private function download_daily() {
        $yesterday = date('m/d/Y', strtotime('-1 day'));

		//$aggr->import_fulltransaction_csv(dummy()); // for debug

		$this->aggr->import_fulltransaction_csv($this->izettle->get_report('full-transaction', $yesterday, $yesterday));
		return "Today's wholeday Product mix.".PHP_EOL;
	}

	private function download_weekly() {
        $yesterday = date('m/d/Y', strtotime('-1 day'));
        $lastweek = date('m/d/Y', strtotime('-7 day'));

		//$aggr->import_fulltransaction_csv(dummy()); // for debug

		$this->aggr->import_fulltransaction_csv($this->izettle->get_report('full-transaction', $lastweek, $yesterday));
		return "Weekly Product mix.".PHP_EOL;
	}

	private function download_monthly() {
		$_1st_of_last = date('m/d/Y', strtotime('first day of last month'));
        $_1st_of_this = date('m/d/Y', strtotime('first day of this month'));

		$this->aggr->import_fulltransaction_csv($this->izettle->get_report('full-transaction', $_1st_of_last, $_1st_of_this));
		return date('F', strtotime('first day of last month'))." Monthly Product mix.".PHP_EOL;
	}

	private function send($message) {
		$to = [];
		foreach($this->mail_settings->to as $address) {
			$to[] = new EmailAddress($address);
		}

		$from = new EmailAddress($this->mail_settings->from);
		$reply_to = new EmailAddress($this->mail_settings->reply_to);

		$html = $this->aggr->get_product_mix_html();
		$html .= '<br />';
		$html .= $this->aggr->get_sales_summary_html();

		$message .= $html;
		$message .= '<pre>';
		$message .= body_footer($reply_to->format('addr_apec'), $from->format('display_name'));
		$message .= '</pre>';

		$mail = new RR1Mail();
		$mail->set_address($to, $from, $reply_to);
		$mail->set_body($message, 'Product Mix & Sales Summary');
		$mail->set_option('text/html', 'UTF-8');
		$mail->send();
	}
}