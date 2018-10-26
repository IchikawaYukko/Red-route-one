<?php
class EmailAddress {
    public $name, $address;

    public function __construct(string $address, string $name = null) {
        $this->name = $name;
        $this->address = $address;
    }

    public function format(string $rfc5322_type) :string {
        $function_name = 'format2'.$rfc5322_type;
        return $this->$function_name();
    }

    private function format2name_addr() :string {
        return "{$this->name} <{$this->address}>";
    }

    private function format2addr_apec() :string {
        return $this->address;
    }

    private function format2display_name() :string {
        return $this->name;
    }

    public static function combine_address(array $addrs) :string {
        $combined = '';

		foreach($addrs as $index => $addr) {
			$combined .= $addr->format('name_addr');
			
			// don't add comma after last address
            if($index + 1 != count($addrs)) {
                $combined .=', ';
            }
        }
        return $combined;
	}
}