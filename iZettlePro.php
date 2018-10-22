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

		$this->access_token = $obj->access_token;
	}

	public function get_product_mix() :string{
		$url = $this->get_product_mix_url();

		$this->curl->reset_option();
		return $this->curl->exec_get($url);
	}

	private function get_product_mix_url() :string{
		if($this->access_token == null) {
			$this->get_access_token();
		}

		$this->curl->reset_option();
		$this->curl->add_option([
			CURLOPT_HTTPHEADER	=> ["Authorization: Bearer {$this->access_token}"],
		]);
		$json = $this->curl->exec_get($this->report_url.'popular-product?startDate=10/21/2018&endDate=10/21/2018&export=csv');

		$obj = json_decode($json);
		return $obj->url;
	}
}

$test = new iZettlePro("foobar", "baz");
echo $test->get_product_mix();