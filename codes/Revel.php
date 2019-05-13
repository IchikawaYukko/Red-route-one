<?php
require_once('CurlWrapper.php');

class Revel {
    private $username, $password, $csrf_token, $curl_handle;
    private $base_url, $auth_url;
    private $pos_codes = [
        'main' => '1',
        'bar' => '2',
        'sushi' => '5',
    ];
    private $curl;
    const COOKIE_FILE = 'token.cookie';
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:66.0) Gecko/20100101 Firefox/66.0';

    public function __construct(string $username, string $password, string $venue_name) {
        $this->username = $username;
        $this->password = $password;

        $this->base_url = "https://{$venue_name}.revelup.com/";
		$this->auth_url = "{$this->base_url}login/?next=/dashboard/";
		$this->sales_summary_url = "{$this->base_url}reports/sales_summary/";

        $this->curl_handle = curl_init();
        $this->csrf_token = $this->get_csrfmiddlewaretoken();
        $this->get_auth_cookie();

        $this->curl = new CurlWrapper();
    }

    public function __destruct() {
        curl_close($this->curl_handle);
    }

    private function get_csrfmiddlewaretoken() {
        curl_setopt_array($this->curl_handle, [
            CURLOPT_URL		        => $this->base_url,
            CURLOPT_COOKIEJAR	    => self::COOKIE_FILE,
            CURLOPT_COOKIEFILE	    => self::COOKIE_FILE,
            CURLOPT_FAILONERROR	    => true,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_AUTOREFERER     => true,
            CURLOPT_HTTPHEADER      => ['User-Agent: '.self::USER_AGENT],
            /*CURLOPT_VERBOSE => true,*/
            CURLOPT_RETURNTRANSFER  => true
        ]);

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
            case 'wholeday':
                $time_begin = '03:00:01'; $time_end = '03:00:00';
                $range_from = urlencode($yesterday).'+'.urlencode($time_begin);
                break;
            case 'weekly':
                $time_begin = '03:00:01'; $time_end = '03:00:00';
                $last_monday = date('m/d/Y', strtotime('-7 day'));
                $range_from = urlencode($last_monday).'+'.urlencode($time_begin);
                break;
            case 'monthly':
                $time_begin = '03:00:01'; $time_end = '03:00:00';
                $last_monday = date('m/d/Y', strtotime('first day of last month'));
                $range_from = urlencode($last_monday).'+'.urlencode($time_begin);
                break;
        }
		$range_to   = urlencode($today).'+'.urlencode($time_end);
        return ['range_from' => $range_from, 'range_to' => $range_to];
	}

	public function get_range_by_date(int $epoch) : array {
		$specify_date	= date('m/d/y', $epoch);
		$next_date		= date('m/d/y', strtotime('+1 day'));

		$time_begin = '03:00:01'; $time_end = '03:00:00';
		$range_from = urlencode($specify_date).'+'.urlencode($time_begin);
		$range_to   = urlencode($next_date).'+'.urlencode($time_end);

		return ['range_from' => $range_from, 'range_to' => $range_to];
	}

    public static function get_filename_suffix_by_timeslot(string $timeslot) : string {
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
            case 'wholeday':
                $filedate = $yesterday;
                break;
            case 'weekly':
                $filedate = date('W'); // week number
                break;
            case 'monthly':
                $filedate = date('j', strtotime('first day of last month')); // month number
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
        curl_setopt_array($this->curl_handle, [
            CURLOPT_URL         => $this->auth_url,
            CURLOPT_COOKIEJAR   => self::COOKIE_FILE,
            CURLOPT_COOKIEFILE  => self::COOKIE_FILE,
            CURLOPT_POST	    => false,
            CURLOPT_POSTFIELDS  => $postfields,
            CURLOPT_FAILONERROR => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_REFERER     => $this->auth_url,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_HTTPHEADER      => ['User-Agent: '.self::USER_AGENT],
            //CURLOPT_VERBOSE => true,
            CURLOPT_RETURNTRANSFER => true
        ]);

        curl_exec($this->curl_handle);
    }

    // Download and return Sales Summary PDF
    public function get_sales_summary(string $range_from, string $range_to, string $pos_location = null) {

        if($pos_location !== null) {
            $pos_station = "posstation={$this->pos_codes[$pos_location]}&";
        } else {
            $pos_station = '';
        }

        $url = "{$this->sales_summary_url}pdf/?dining_option=&{$pos_station}employee=&online_app=&online_app_type=&online_app_platform=&show_unpaid=1&show_irregular=1&range_from=$range_from&range_to=$range_to";

        return $this->get_data_by_get_method($url);
	}
	
	public function get_sales_summary_json(string $range_from, string $range_to) {
		$url = "{$this->sales_summary_url}json/?dining_option=&employee=&online_app=&online_app_type=&online_app_platform=&show_unpaid=1&show_irregular=1&range_from=$range_from&range_to=$range_to&format=json";

        return $this->get_data_by_get_method($url);
	}

    private function get_data_by_get_method(string $url) {
        curl_setopt_array($this->curl_handle, [
            CURLOPT_URL         => $url,
            CURLOPT_COOKIEJAR   => self::COOKIE_FILE,
            CURLOPT_COOKIEFILE  => self::COOKIE_FILE,
            CURLOPT_FAILONERROR => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_REFERER     => $url,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_HTTPHEADER      => ['User-Agent: '.self::USER_AGENT],
            //CURLOPT_VERBOSE => true,  /* for debuging */
            CURLOPT_RETURNTRANSFER => true
        ]);
        return curl_exec($this->curl_handle);
    }

    // Download and return Product Mix csv
    public function get_product_mix_csv(string $range_from, string $range_to) {
        $url = "{$this->base_url}reports/product_mix/data/?"
        .'sort_by=n_items&'
        .'sort_reverse=&'
        .'combo_expand=&'
        .'employee=&'
        .'online_app=&'
        .'online_app_type=&'
        .'online_app_platform=&'
        .'dining_option=&'
        .'show_unpaid=1&'
        .'show_irregular=1&'
        .'sort_view=2&'
        .'show_product=1&'
        .'quantity_settings=0&'
        .'no-filter=0&'
        .'day_of_week=&'
        ."range_from=$range_from&"
        ."range_to=$range_to&"
        .'format=csv';

        return $this->get_data_by_get_method($url);
    }

    // Download and return Product Mix PDF
    public function get_product_mix(string $range_from, string $range_to) {
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
        $request_header = [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: '.self::USER_AGENT,
            'Expect:'
        ];
        curl_setopt_array($this->curl_handle, [
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
        ]);

        return curl_exec($this->curl_handle);
    }

    public function product_mix_is_empty(string $range_from, string $range_to) : bool {
        if(strlen($this->get_product_mix_csv($range_from, $range_to)) === 0) {
            return true;
        }
        return false;
    }
}
?>