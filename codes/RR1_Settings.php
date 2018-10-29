<?php
class RR1_Settings {
    private $obj;
    public function __construct(string $json_filename) {
		$this->obj = json_decode(file_get_contents($json_filename));

		if(is_null($this->obj)) {
			$error = 'JSON: '.json_last_error_msg() . PHP_EOL;
			die($error);
		}
    }

    public function get_settings(string $class, string $sub_class) {
        return $this->obj->$class->$sub_class;
    }

    public static function combine_mail_address(array $addrs) {
        $combined = '';

		foreach($addrs as $index => $addr) {
			$combined .= $addr;
			
			// don't add comma after last address
            if($index + 1 != count($addrs)) {
                $combined .=', ';
            }
        }
        return $combined;
	}
}