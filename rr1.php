<?php
require_once("settings.php");
require_once("RR1_Mail.php");
require_once("Revel.php");

main($argv[1]);

function main(string $timeslot) {
    $attach_file = download($timeslot);
    send($attach_file, $timeslot);
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

    if($timeslot != 'wholeday') {
        $subject	= "$timeslot time Sales Summary, Product Mix";
        $message = "Today's $timeslot time Sales Summary and Product mix.";
    } else {
        $subject	= "$timeslot time Product Mix";
        $message = "Today's $timeslot time Product mix.";
    }
    $message .= $body_footer;

    $mail = new RR1Mail();
    $mail->sendmail($addr, $subject, $message, $file);
}
?>