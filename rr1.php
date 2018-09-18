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
	if($hours == '18' && preg_match('/^0[1-9]/', $minutes)) {
		download_n_send('lunch');
		return;
	}
	// Tea (18:10)
	if($hours == '18' && preg_match('/^1[1-9]/', $minutes)) {
		download_n_send('tea');
		return;
	}
	// Dinner (6:00)
	if($hours == '06' && preg_match('/^0[1-9]/', $minutes)) {
		download_n_send('dinner');
		return;
	}
	// Wholeday (6:10)
	if($hours == '06' && preg_match('/^1[1-9]/', $minutes)) {
		download_n_send('wholeday');
		return;
	}
	// Weekly (Sun 6:20)
	if($dayofweek == 'Sun' && $hours == '06' && preg_match('/^2[1-9]/', $minutes)) {
		download_n_send('weekly');
		return;
	}
	if(DEBUG) {
		//echo 'no schedule job on this time.';
	}
}


function download(string $timeslot) {
    $revel = new Revel(REVEL_USERNAME, REVEL_PASSWORD, VENUE_NAME);

    $range = $revel->get_range_by_timeslot($timeslot);
    $filesuffix = $revel->get_filename_suffix_by_timeslot($timeslot);

    $file = array();
    if(
        $timeslot == 'lunch' ||
        $timeslot == 'tea' ||
        $timeslot == 'dinner'
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
				'data'      =>  $revel->get_product_mix_csv($range['range_from'], $range['range_to']),
			);
        }
    }

    if($timeslot == 'weekly') {
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

	if($timeslot == 'weekly') {

        $subject	= "Weekly Sales Summary";
        $message = "Weekly Sales Summary.";
	}
	if(	$timeslot == 'lunch' ||
		$timeslot == 'tea' ||
		$timeslot == 'dinner' ) {
		switch($timeslot) {
			case 'lunch':
				$timeslot_s = 'Lunch';
				break;
			case 'tea':
				$timeslot_s = 'Tea';
				break;
			case 'dinner':
				$timeslot_s = 'Dinner';
				break;
		}
		$subject	= "$timeslot_s time Sales Summary";
		$message = "Today's $timeslot time Sales Summary";

		if(count($file) != 1) {
			$message .= "and Product mix.";
			$subject .= '& Product Mix';
		} else {
			$message .= ". No orders in $timeslot time. No Product Mix in this time.";
		}
    } else {
        $subject	= "$timeslot time Product Mix";
        $message = "Today's $timeslot time Product mix.";
    }
    $message .= $body_footer;

    $mail = new RR1Mail();
    $mail->sendmail($addr, $subject, $message, $file);
}
?>