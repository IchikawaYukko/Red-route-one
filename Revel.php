<?php
class Revel {
    private $username, $password, $csrf_token, $curl_handle;
    private $base_url, $auth_url;
    const COOKIE_FILE = 'token.cookie';

    public function __construct(string $username, string $password, string $venue_name) {
        $this->username = $username;
        $this->password = $password;

        $this->base_url = "https://{$venue_name}.revelup.com/";
        $this->auth_url = "{$this->base_url}login/?next=/dashboard/";

        $this->curl_handle = curl_init();
        $this->csrf_token = $this->get_csrfmiddlewaretoken();
        $this->get_auth_cookie();
    }

    public function __destruct() {
        curl_close($this->curl_handle);
    }

    private function get_csrfmiddlewaretoken() {
        curl_setopt_array($this->curl_handle, array(
            CURLOPT_URL		        => $this->base_url,
            CURLOPT_COOKIEJAR	    => self::COOKIE_FILE,
            CURLOPT_COOKIEFILE	    => self::COOKIE_FILE,
            CURLOPT_FAILONERROR	    => true,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_AUTOREFERER     => true,
            /*CURLOPT_VERBOSE => true,*/
            CURLOPT_RETURNTRANSFER  => true
        ));
      
        $response = curl_exec($this->curl_handle) or die('fail on get_csrfmiddlewaretoken()');

        // Parse HTML
        $start = strpos($response, "csrfmiddlewaretoken' value='") + strlen("csrfmiddlewaretoken' value='");
        $end = strpos($response, "'", $start);

        // Find token
        return substr($response, $start, $end - $start);
    }

    public static function get_range_by_timeslot(string $timeslot) {
        date_default_timezone_set(TIME_ZONE);
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

    public static function get_filename_suffix_by_timeslot(string $timeslot) {
        date_default_timezone_set(TIME_ZONE);
        $yesterday = date('m_d_Y', strtotime('-1 day'));
        $today = date('m_d_Y');

        switch ($timeslot) {
            case 'lunch':
                $filedate = $today;
                break;
            case 'tea':
                $filedate = $today;
                break;
            case 'dinner':
                $filedate = $yesterday;
                break;
        }
        return $filedate.$timeslot;
    }

    // Get Auth Cookie (Cookie will be saved as file (not return value))
    private function get_auth_cookie() {
        $postfields = http_build_query([
            'username' => $this->username,
            'password' => $this->password,
            'csrfmiddlewaretoken' => $this->csrf_token
        ]);
        curl_setopt_array($this->curl_handle, array(
            CURLOPT_URL         => $this->auth_url,
            CURLOPT_COOKIEJAR   => self::COOKIE_FILE,
            CURLOPT_COOKIEFILE  => self::COOKIE_FILE,
            CURLOPT_POST	    => false,
            CURLOPT_POSTFIELDS  => $postfields,
            CURLOPT_FAILONERROR => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_REFERER     => $this->auth_url,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_RETURNTRANSFER => true
        ));

        curl_exec($this->curl_handle);
    }

    // Download and return Sales Summary PDF
    public function get_sales_summary($range_from, $range_to) {
        $url = "{$this->base_url}reports/sales_summary/pdf/?dining_option=&employee=&online_app=&online_app_type=&online_app_platform=&show_unpaid=1&show_irregular=1&range_from=$range_from&range_to=$range_to";

        curl_setopt_array($this->curl_handle, array(
            CURLOPT_URL         => $url,
            CURLOPT_COOKIEJAR   => self::COOKIE_FILE,
            CURLOPT_COOKIEFILE  => self::COOKIE_FILE,
            CURLOPT_FAILONERROR => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_REFERER     => $url,
            CURLOPT_AUTOREFERER => true,
            /*CURLOPT_VERBOSE => true,*/
            CURLOPT_RETURNTRANSFER => true
        ));
        return curl_exec($this->curl_handle);
    }

    // Download and return Product Mix PDF
    public function get_product_mix($range_from, $range_to) {
        $url = "{$this->base_url}reports/product_mix/pdf/";

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
        curl_setopt_array($this->curl_handle, array(
            CURLOPT_URL             => $url,
            CURLOPT_COOKIEJAR       => self::COOKIE_FILE,
            CURLOPT_COOKIEFILE      => self::COOKIE_FILE,
            CURLOPT_FAILONERROR     => true,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_REFERER         => $url,
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => $report_option,
            CURLOPT_HTTPHEADER      => $request_header,
            CURLOPT_AUTOREFERER     => true,
            //CURLOPT_VERBOSE     => true,
            CURLOPT_RETURNTRANSFER  => true
        ));

        return curl_exec($this->curl_handle);
    }
}
?>