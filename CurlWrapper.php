<?php
class CurlWrapper {
	private $handle, $option;
	const COOKIE_FILE = 'curl.cookie';

	public function __construct() {
		$this->handle = curl_init();

		$this->set_common_option();
	}

	public function reset_option() {
		curl_reset($this->handle);
		$this->set_common_option();
	}

	private function set_common_option() {
		$this->option = [
			CURLOPT_AUTOREFERER     => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FAILONERROR	    => true,
			CURLOPT_COOKIEJAR       => self::COOKIE_FILE,
			CURLOPT_COOKIEFILE      => self::COOKIE_FILE,
			CURLOPT_FOLLOWLOCATION  => true,
		];
	}

	public function add_option(array $option) {
		$this->option = $this->option + $option;
	}

	public function add_debug_option() {
		$this->add_option([
			CURLOPT_VERBOSE => true
		]);
	}

	public function exec_get(string $url) {
		$this->add_option([
			CURLOPT_URL	=> $url,
			CURLOPT_HTTPGET => true
		]);

		return $this->exec();
	}

	public function exec_post(string $url, string $request_body) {
		$this->add_option([
			CURLOPT_URL			=> $url,
			CURLOPT_POST		=> true,
			CURLOPT_POSTFIELDS	=> $request_body,
		]);

		return $this->exec();
	}

	private function exec() {
		curl_setopt_array($this->handle, $this->option);
		$response = curl_exec($this->handle);

		if($response === false) {
			echo curl_error($this->handle);
		}
		return $response;
	}
}