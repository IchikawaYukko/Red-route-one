<?php
require_once 'Job.php';
require_once 'iZettlePro.php';
require_once 'iZettleProAggregate.php';

class iZettleProMail implements Job {
	public function do_job(string $arg) {
		global $body_footer;

        date_default_timezone_set(TIME_ZONE);
        $yesterday = date('m/d/Y', strtotime('-1 day'));

		$izettle = new iZettlePro(I_ZETTLE_USERNAME, I_ZETTLE_PASSWORD);
		$aggr = new iZettleProAggregate();

		$aggr->import_fulltransaction_csv($izettle->get_report('full-transaction', $yesterday, $yesterday));
		$html = $aggr->get_product_mix_html();

		$addr = array(
			'to'        =>  TO_ADDRESS,
			'from'      =>  FROM_ADDRESS,
			'reply_to'  =>  REPLY_TO_ADDRESS,
		);

		$subject = 'Product Mix';
		$message = "Today's wholeday Product mix.\n";
		$message .= $html;
		$message .= "<pre>$body_footer</pre>";

		$mail = new RR1Mail();
		$mail->set_option('text/html');
		$mail->sendmail($addr, $subject, $message, []);
	}
}