<?php
require_once("settings.php");

$token_url = "https://$venue_name.revelup.com/";
$auth_url = "https://$venue_name.revelup.com/login/?next=/dashboard/";
$cookiefile = 'token.cookie';

$conn = curl_init();
$token = get_csrfmiddlewaretoken($token_url);
get_auth_cookie($auth_url, $token);
sendmail(get_target_package(get_pdf_url('sales_summary', 'lunch')));
curl_close($conn);

function get_pdf_url($type, $timeslot) {
  global $venue_name, $timezone;

  date_default_timezone_set($timezone);
  $yesterday = date('m/d/Y', strtotime('-1 day'));
  $today = date('m/d/Y');

  switch ($timeslot) {
    case 'lunch':
      $time_begin = '03:00:01'; $time_end = '15:00:00';
      $range_from = urlencode($today).'+'.urlencode($time_begin);
      $range_to	  = urlencode($today).'+'.urlencode($time_end);
      break;
    case 'tea':
      $time_begin = '15:00:01'; $time_end = '17:30:00';
      $range_from = urlencode($today).'+'.urlencode($time_begin);
      $range_to   = urlencode($today).'+'.urlencode($time_end);
      break;
    case 'dinner':
      $time_begin = '17:30:01'; $time_end = '03:00:00';
      $range_from = urlencode($yesterday).'+'.urlencode($time_begin);
      $range_to   = urlencode($today).'+'.urlencode($time_end);
      break;
  }

  echo $test = "https://$venue_name.revelup.com/reports/$type/pdf/?dining_option=&employee=&online_app=&online_app_type=&online_app_platform=&show_unpaid=1&show_irregular=1&range_from=$range_from&range_to=$range_to";
  return $test;
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
  global $conn, $cookiefile, $smtp_username, $smtp_password;

  $postfields = http_build_query([
    'username' => $smtp_username,
    'password' => $smtp_password,
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

function get_target_package($url) {
  // download target PDF
  global $conn, $cookiefile;

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

function sendmail($filedata) {
//  mb_language("Japanese");
//  mb_internal_encoding("UTF-8");
  global $to_address,$from_address;

  $filename = "hoge.pdf";

  $boundary = "__BOUNDARY__";
  $to		= $to_address;
  $from		= $from_address;
  $subject	= "test";
  $headers	= "From: $from
MIME-Version: 1.0
Content-Type: multipart/mixed;boundary=\"$boundary\"
";

  $message_body = "--$boundary
Content-Type: text/plain; charset=\"ISO-2022-JP\"

Today's summary and Product mix.
--$boundary
Content-Type: application/octet-stream; name=\"{$filename}\"
Content-Disposition: attachment; filename=\"{$filename}\"
Content-Transfer-Encoding: base64\n\n";
  $message_body .= chunk_split(base64_encode($filedata))."\n\n";
#  $message_body .= "--$boundary--";
//  echo $headers.$message_body;
  mail($to, $subject, $message_body, $headers);
}
?>
