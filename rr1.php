<?php
// run this file 10 minutely by cron. the scheduled job will do.
//
// example: */10 * * * * php rr1.php
// or
// example: */10 * * * * docker exec -i rr1-test scl enable rh-php71 'php /rr1.php'

require_once("settings.php");
require_once("RR1_Mail.php");
require_once("Revel.php");

if(isset($argv[1])) {
	if(DEBUG) {
		download_n_send($argv[1]);
	}
} else {
	scheduler();
}

function download_n_send(string $timeslot) {
    $attach_file = download($timeslot);
    send($attach_file, $timeslot);
}

function scheduler () {
	date_default_timezone_set(TIME_ZONE);
	$hours = date('H');	$minutes = date('i'); $dayofweek = date('D');

	// Lunch (18:00)
	if($hours == '18' && preg_match('/^0[0-9]/', $minutes)) {
		download_n_send('lunch');
		scheduler_write_log('lunch message sent.');
		return;
	}
	// Tea (18:10)
/*
	if($hours == '18' && preg_match('/^1[0-9]/', $minutes)) {
		download_n_send('tea');
		scheduler_write_log('tea message sent.');
		return;
	}
*/
	// Dinner (3:30)
	if($hours == '03' && preg_match('/^3[0-9]/', $minutes)) {
		download_n_send('dinner');
		scheduler_write_log('dinner message sent.');
		return;
	}
	// Wholeday (4:00)
	if($hours == '04' && preg_match('/^0[0-9]/', $minutes)) {
		download_n_send('wholeday');
		scheduler_write_log('wholeday message sent.');
		return;
	}
	// Weekly (Mon 4:10)
	if($dayofweek == 'Mon' && $hours == '04' && preg_match('/^1[0-9]/', $minutes)) {
		download_n_send('weekly');
		scheduler_write_log('weekly message sent.');
		return;
	}
	//scheduler_write_log("Vars: $dayofweek $hours $minutes");
	scheduler_write_log('no schedule job on this time.');
}

function scheduler_write_log(string $log_message) {
	if(DEBUG) {
		file_put_contents('scheduler.log', date('H:i T ').$log_message."\n", FILE_APPEND);
	}
}

function download(string $timeslot) : array {
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

function send(array $file, string $timeslot) {
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
		if(!hasProductMix($file)) {
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

function hasProductMix(array $file) : bool {
	return count($file) != 1;
}
?>