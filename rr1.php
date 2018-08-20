<?php
require_once("password.php");

$token_url = 'https://cube.revelup.com/';
$auth_url = 'https://cube.revelup.com/login/?next=/dashboard/';
$pdf_base_url = 'https://cube.revelup.com/reports/sales_summary/pdf/?dining_option=&employee=&online_app=&online_app_type=&online_app_platform=&show_unpaid=1&show_irregular=1&range_from=08%2F17%2F2018+03%3A00%3A01&range_to=08%2F18%2F2018+03%3A00%3A01';

$cookiefile = 'token.cookie';

$conn = curl_init();
$token = get_csrfmiddlewaretoken($token_url);
get_auth_cookie($auth_url, $token);
get_target_package($pdf_base_url);
curl_close($conn);

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
  global $conn, $cookiefile, $username, $password;

  $postfields = http_build_query([
    'username' => $username,
    'password' => $password,
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

  echo curl_exec($conn);
}
?>
