<?php
require_once('settings.php');
require_once('Revel.php');
require_once('vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\SpreadSheet;
use PhpOffice\PhpSpreadsheet;

$rg = new RevelGraph;

//var_dump( $rg->get_one_week_each_pos_sales());
$rg->write_graph('graph.xlsx');

class RevelGraph{
	private $r, $spreadsheet;
	public function __construct() {
		//$this->spreadsheet = new Spreadsheet();
		$this->r = new Revel(REVEL_USERNAME, REVEL_PASSWORD, VENUE_NAME);
	}

	public function get_daily_each_pos_sales(int $epoch) : array{
		$range		= $this->r->get_range_by_date($epoch);
		$json_array	= json_decode($this->r->get_sales_summary_json($range['range_from'], $range['range_to']));
	
		foreach($json_array[0]->net_sales_by_rev_center as $obj) {
			$each_pos_sales[$obj->name] = $obj->net_sales;
		}
		return $each_pos_sales;
	}
	public function get_one_week_each_pos_sales(): array {
		$pos_sales = [];
		define('WORKING_DAYS_IN_A_WEEK', 6);

		for($before = 0;$before < WORKING_DAYS_IN_A_WEEK; $before++) {
			$pos_sales[] = $this->get_daily_each_pos_sales(strtotime("-$before day"));
		}
		var_dump( $pos_sales);
		return $pos_sales;
	}
	public function write_graph(string $filename) {
		//$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('weekly-graph.xls');
		$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
		$reader->setIncludeCharts(true);
		$spreadsheet = $reader->load('weekly-graph.xls');

		$sheet = $spreadsheet->getActiveSheet();
		$pos_sales = $this->get_one_week_each_pos_sales();

		$rowOffset = 17;
		$colOffset = 2;

		foreach ($pos_sales as $col => $eachday) {
			$sheet->setCellValueByColumnAndRow($col + $colOffset, $rowOffset, $eachday['Sushi']);
			$sheet->setCellValueByColumnAndRow($col + $colOffset, $rowOffset + 1, $eachday['Main']);
			$sheet->setCellValueByColumnAndRow($col + $colOffset, $rowOffset + 2, $eachday['Bar']);
		}
		
		$sheet->setCellValue('A1', 'Week XY Sales Amount (NET)');

		/*
		$class = \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf::class;
		\PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', $class);
		$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Pdf');
		*/

		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
		$writer->setIncludeCharts(true);
		$writer->save($filename);
	}
}

?>