<?php
require_once("settings.php");

$token_url = "https://$venue_name.revelup.com/";
$auth_url = "https://$venue_name.revelup.com/login/?next=/dashboard/";
$cookiefile = 'token.cookie';

$conn = curl_init();
$token = get_csrfmiddlewaretoken($token_url);
get_auth_cookie($auth_url, $token);

sendmail($argv[1]);
curl_close($conn);

function get_range_by_timeslot($timeslot) {
  global $venue_name, $timezone;

  date_default_timezone_set($timezone);
  $yesterday = date('m/d/Y', strtotime('-1 day'));
  $today = date('m/d/Y');

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
  global $conn, $cookiefile;

  curl_setopt_array($conn, array(
    CURLOPT_URL		=> $url,
    CURLOPT_COOKIEJAR	=> $cookiefile,
    CURLOPT_COOKIEFILE	=> $cookiefile,
    CURLOPT_FAILONERROR	=> true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_AUTOREFERER => true,
    CURLOPT_RETURNTRANSFER => true
  ));

  $response = curl_exec($conn);
  $start = strpos($response, "csrfmiddlewaretoken' value='") + strlen("csrfmiddlewaretoken' value='");
  $end = strpos($response, "'", $start);

  return substr($response, $start, $end - $start);
}

function get_auth_cookie($url, $token) {
  global $conn, $cookiefile, $revel_username, $revel_password;

  $postfields = http_build_query([
    'username' => $revel_username,
    'password' => $revel_password,
    'csrfmiddlewaretoken' => $token
  ]);
  curl_setopt_array($conn, array(
    CURLOPT_URL         => $url,
    CURLOPT_COOKIEJAR   => $cookiefile,
    CURLOPT_COOKIEFILE  => $cookiefile,
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
  global $conn, $cookiefile, $venue_name;

  $url = "https://$venue_name.revelup.com/reports/sales_summary/pdf/?dining_option=&employee=&online_app=&online_app_type=&online_app_platform=&show_unpaid=1&show_irregular=1&range_from=$range_from&range_to=$range_to";

#die($url);
  curl_setopt_array($conn, array(
    CURLOPT_URL         => $url,
    CURLOPT_COOKIEJAR   => $cookiefile,
    CURLOPT_COOKIEFILE  => $cookiefile,
    CURLOPT_FAILONERROR => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_REFERER => $url,
    CURLOPT_AUTOREFERER => true,
    CURLOPT_RETURNTRANSFER => true
));

  return curl_exec($conn);
}

function get_product_mix($range_from, $range_to) {
  global $cookiefile, $venue_name;

  ob_start();
  passthru("curl -sb $cookiefile -H 'Expect:' -d 'dining_option=' -d 'range_from=$range_from' -d 'range_to=$range_to' -d 'sort_by=n_items' -d 'sort_reverse=1' -d 'combo_expand=' -d 'employee=' -d 'online_app=' -d 'online_app_type=' -d 'online_app_platform=' -d 'dining_option=' -d 'show_unpaid=' -d 'show_irregular=' -d 'sort_view=2' -d 'show_class=1' -d 'quantity_settings=0' -d 'no-filter=0' -d 'day_of_week=' https://$venue_name.revelup.com/reports/product_mix/pdf/");
  $output = ob_get_contents();
//  $ob_end_clean();
return $output;
}

function sendmail($timeslot) {
  global $to_address,$from_address,$body_text,$body_footer, $reply_to_address;

  $today = date('d_m_Y');
  $range = $range = get_range_by_timeslot($timeslot);
  $filename1 = "SalesSummary$today.pdf";  //set correct name
  $filedata1 = chunk_split(base64_encode(get_sales_summary($range['range_from'], $range['range_to'])));
  $filename2 = "ProductMix$today.pdf";
  $filedata2 = chunk_split(base64_encode(get_product_mix($range['range_from'], $range['range_to'])));

  $boundary = "__BOUNDARY__";
  $to		= $to_address;
  $from		= $from_address;
  $subject	= "$timeslot time Sales Summary, Product Mix";
  $headers	= "From: $from
Reply-to: $reply_to_address
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
}
?>
