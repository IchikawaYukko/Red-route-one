<?php
require_once('CurlWrapper.php');

class iZettlePro {
	private $oauth_token_url, $report_url, $username, $password;
	private $access_token;
	private $curl;

	public function __construct(string $username, string $password) {
		$this->username = $username;
		$this->password = $password;

		$this->oauth_token_url = 'https://oauth.intelligentposapi.com/token';
		$this->report_url = 'https://backoffice.intelligentposapi.com/v2/report/';

		$this->curl = new CurlWrapper();
	}

	private function get_access_token() {
		$this->curl->reset_option();
		$this->curl->add_option([
			CURLOPT_HTTPHEADER	=> ['Authorization: Basic '.base64_encode('iposbackoffice:')],
		]);
		$json = $this->curl->exec_post($this->oauth_token_url,
			"grant_type=password"
			."&scope=ALL%3AINTERNAL"
			."&username={$this->username}"
			."&password={$this->password}"
		);

		$obj = json_decode($json);
		if($json != false) {
			$this->access_token = $obj->access_token;
		} else {
			die("\nWrong Username or Password");
		}
	}

	// $report_type is 'popular-product' or 'full-transaction' or etc...
	public function get_report(string $report_type, string $start_date, string $end_date) :string{
		$url = $this->get_report_url($report_type, $start_date, $end_date);

		$this->curl->reset_option();
		return $this->curl->exec_get($url);
	}

	private function get_report_url(string $type, string $start_date, string $end_date) :string{
		if($this->access_token == null) {
			$this->get_access_token();
		}

		$this->curl->reset_option();
		$this->curl->add_option([
			CURLOPT_HTTPHEADER	=> ["Authorization: Bearer {$this->access_token}"],
		]);
		$json = $this->curl->exec_get(
			$this->report_url
			."$type?startDate=$start_date&endDate=$end_date&export=csv"
		);

		$obj = json_decode($json);
		return $obj->url;
	}
}