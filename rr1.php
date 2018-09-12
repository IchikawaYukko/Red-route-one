<?php
require_once("settings.php");
require_once("RR1_Mail.php");

define('BASE_URL', 'https://'.VENUE_NAME.'.revelup.com/');
define('AUTH_URL', BASE_URL.'login/?next=/dashboard/');
define('COOKIE_FILE', 'token.cookie');

$conn = curl_init();
$token = get_csrfmiddlewaretoken(BASE_URL);
get_auth_cookie(AUTH_URL, $token);

$timeslot = $argv[1];
//sendmail($argv[1]);

$addr = array(
  'to'        =>  TO_ADDRESS,
  'from'      =>  FROM_ADDRESS,
  'reply_to'  =>  REPLY_TO_ADDRESS,
);

$today = date('d_m_Y');
$range = get_range_by_timeslot($timeslot);

$file = array();
$file[] = array(
  'filename'  =>  "SalesSummary$today.pdf",  // TODO set correct name
  'data'      =>  get_sales_summary($range['range_from'], $range['range_to']),
);

$file[] = array(
  'filename'  =>  "ProductMix$today.pdf",
  'data'      =>  get_product_mix($range['range_from'], $range['range_to']),
);

$subject	= "$timeslot time Sales Summary, Product Mix";
$message = "Today's $timeslot time summary and Product mix.";
$message .= $body_footer;

$mail = new RR1Mail();
$mail->sendmail($addr, $subject, $message, $file);

curl_close($conn);

function get_range_by_timeslot($timeslot) {
  date_default_timezone_set(TIME_ZONE);
  $yesterday = date('m/d/Y', strtotime('-1 day'));
  $today = date('m/d/Y');
  //$today = '09/10/2018';

  switch ($timeslot) {
    case 'lunch':
      $time_begin = '03:00:01'; $time_end = '15:00:00';
      $range_from = urlencode($today).'+'.urlencode($time_begin);
      break;
    case 'tea':
      $time_begin = '15:00:01'; $time_end = '17:30:00';
      $range_from = urlencode($today).'+'.urlencode($time_begin);
      break;
    case 'dinner':
      $time_begin = '17:30:01'; $time_end = '03:00:00';
      $range_from = urlencode($yesterday).'+'.urlencode($time_begin);
      break;
  }
  $range_to   = urlencode($today).'+'.urlencode($time_end);
  return array('range_from' => $range_from, 'range_to' => $range_to);
}

function get_csrfmiddlewaretoken($url) {
  global $conn;

  curl_setopt_array($conn, array(
    CURLOPT_URL		=> $url,
    CURLOPT_COOKIEJAR	=> COOKIE_FILE,
    CURLOPT_COOKIEFILE	=> COOKIE_FILE,
    CURLOPT_FAILONERROR	=> true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_AUTOREFERER => true,
    /*CURLOPT_VERBOSE => true,*/
    CURLOPT_RETURNTRANSFER => true
  ));

  $response = curl_exec($conn) or die('fail on get_csrfmiddlewaretoken()');
  /*var_dump($response);*/
  $start = strpos($response, "csrfmiddlewaretoken' value='") + strlen("csrfmiddlewaretoken' value='");
  $end = strpos($response, "'", $start);

  return substr($response, $start, $end - $start);
}

function get_auth_cookie($url, $token) {
  global $conn;

  $postfields = http_build_query([
    'username' => REVEL_USERNAME,
    'password' => REVEL_PASSWORD,
    'csrfmiddlewaretoken' => $token
  ]);
  curl_setopt_array($conn, array(
    CURLOPT_URL         => $url,
    CURLOPT_COOKIEJAR   => COOKIE_FILE,
    CURLOPT_COOKIEFILE  => COOKIE_FILE,
    CURLOPT_POST	=> false,
    CURLOPT_POSTFIELDS  => $postfields,
    CURLOPT_FAILONERROR => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_REFERER => $url,
    CURLOPT_AUTOREFERER => true,
    CURLOPT_RETURNTRANSFER => true
));

  curl_exec($conn);
}

function get_sales_summary($range_from, $range_to) {
  // download target PDF
  global $conn, $base_url;

  $url = BASE_URL."reports/sales_summary/pdf/?dining_option=&employee=&online_app=&online_app_type=&online_app_platform=&show_unpaid=1&show_irregular=1&range_from=$range_from&range_to=$range_to";

  curl_setopt_array($conn, array(
    CURLOPT_URL         => $url,
    CURLOPT_COOKIEJAR   => COOKIE_FILE,
    CURLOPT_COOKIEFILE  => COOKIE_FILE,
    CURLOPT_FAILONERROR => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_REFERER => $url,
    CURLOPT_AUTOREFERER => true,
    /*CURLOPT_VERBOSE => true,*/
    CURLOPT_RETURNTRANSFER => true
));

  /*
  $result = curl_exec($conn);
  echo $result;
  return $result;
  */
  return curl_exec($conn);
}

/*
function get_product_mix_by_external_curl($range_from, $range_to) {
  global $cookiefile, $venue_name, $base_url;

  ob_start() or die('error on ob_start');
  passthru("curl -sb $cookiefile -H 'Expect:' -d 'dining_option=' -d 'range_from=$range_from' -d 'range_to=$range_to' -d 'sort_by=n_items' -d 'sort_reverse=' -d 'combo_expand=' -d 'employee=' -d 'online_app=' -d 'online_app_type=' -d 'online_app_platform=' -d 'dining_option=' -d 'show_unpaid=' -d 'show_irregular=' -d 'sort_view=2' -d 'show_class=1' -d 'quantity_settings=0' -d 'no-filter=0' -d 'day_of_week=' ".$base_url."reports/product_mix/pdf/");
  $output = ob_get_flush();
//  $ob_end_clean();
  return $output;
}
*/

function get_product_mix($range_from, $range_to) {
  global $conn;

  $url = BASE_URL.'reports/product_mix/pdf/';
  /*$report_option = array(
    'dining_option' => '',
    'range_from'    => urldecode($range_from),   // UNDONE urldecode?
    'range_to'      => urldecode($range_to),
    'sort_by'       => 'n_items',
    'sort_reverse'  => '',
    'combo_expand'  => '',
    'employee'      => '',
    'online_app'    => '',
    'online_app_type' => '',
    'online_app_platform' => '',
    'show_unpaid'   => '',
    'show_irregular' => '',
    'sort_view'     => '2',
    'show_class'    => '1',
    'quantity_settings' => '0',
    'no-filter'     => '0',
    'day_of_week'   => ''
  );*/

  $report_option = 
    'dining_option='
    .'&range_from='.$range_from
    .'&range_to='.$range_to
    .'&sort_by=n_items'
    .'&sort_reverse='
    .'&combo_expand='
    .'&employee='
    .'&online_app='
    .'&online_app_type='
    .'&online_app_platform='
    .'&show_unpaid=1'
    .'&show_irregular=1'
    .'&sort_view=2'
    .'&show_product=1'
    .'&quantity_settings=0'
    .'&no-filter=0'
    .'&day_of_week='
  ;
  $request_header = array(
    'Content-Type: application/x-www-form-urlencoded',
    'Expect:'
  );
  curl_setopt_array($conn, array(
    CURLOPT_URL         => $url,
    CURLOPT_COOKIEJAR   => COOKIE_FILE,
    CURLOPT_COOKIEFILE  => COOKIE_FILE,
    CURLOPT_FAILONERROR => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_REFERER     => $url,
    CURLOPT_POST        => true,
    CURLOPT_POSTFIELDS  => $report_option,
    CURLOPT_HTTPHEADER  => $request_header,
    CURLOPT_AUTOREFERER => true,
    //CURLOPT_VERBOSE     => true,
    CURLOPT_RETURNTRANSFER => true
));

  return curl_exec($conn);
}

/*function sendmail($timeslot) {
  global $body_text,$body_footer;

  $today = date('d_m_Y');
  $range = $range = get_range_by_timeslot($timeslot);
  $filename1 = "SalesSummary$today.pdf";  //set correct name
  $filedata1 = chunk_split(base64_encode(get_sales_summary($range['range_from'], $range['range_to'])));
  $filename2 = "ProductMix$today.pdf";
  $filedata2 = chunk_split(base64_encode(get_product_mix($range['range_from'], $range['range_to'])));

  $boundary = "__BOUNDARY__";
  $to		= TO_ADDRESS;
  $from		= FROM_ADDRESS;
  $reply_to = REPLY_TO_ADDRESS;
  $subject	= "$timeslot time Sales Summary, Product Mix";
  $headers	= "From: $from
Reply-to: $reply_to
MIME-Version: 1.0
Content-Type: multipart/mixed;boundary=\"$boundary\"
";

  $message_body = "--$boundary
Content-Type: text/plain; charset=\"ISO-2022-JP\"

Today's $timeslot time summary and Product mix.

$body_footer
--$boundary
Content-Type: application/octet-stream; name=\"{$filename1}\"
Content-Disposition: attachment; filename=\"{$filename1}\"
Content-Transfer-Encoding: base64

$filedata1
--$boundary
Content-Type: application/octet-stream; name=\"{$filename2}\"
Content-Disposition: attachment; filename=\"{$filename2}\"
Content-Transfer-Encoding: base64

$filedata2

";
  $message_body .= "--$boundary--";

  mail($to, $subject, $message_body, $headers);
}*/
?>
