<?php
require_once('settings.php');
require_once('Revel.php');

$rg = new RevelGraph;

$rg->x();

class RevelGraph{
	private $r;
	public function __construct() {
		$this->r = new Revel(REVEL_USERNAME, REVEL_PASSWORD, VENUE_NAME);
	}

	public function get_daily_each_pos_sales($epoc) {
		$range		= $this->r->get_range_by_date($epoc);
		$json_array	= json_decode($this->r->get_sales_summary_json($range['range_from'], $range['range_to']));
	
		foreach($json_array[0]->net_sales_by_rev_center as $obj) {
			$each_pos_sales[$obj->name] = $obj->net_sales;
		}
		return $each_pos_sales;
	}
	public function x() {
		//$epoc = mktime(0, 0, 0, $month, $day, $year);

		for($before = 0;$before < 4; $before++) {
			$pos_sales =$this->get_daily_each_pos_sales(strtotime("-$before day"));
		}
	}
}

?>