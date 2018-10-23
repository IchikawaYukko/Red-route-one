<?php
require_once 'Job.php';
require_once 'iZettlePro.php';

class iZettleProMail implements Job {
	public function do_job(string $arg) {
		echo $arg;
	}

	private function download_n_send() {
		$izettle = new iZettlePro(I_ZETTLE_USERNAME, I_ZETTLE_PASSWORD);
        $attach_file[] = array(
            'filename'  =>  "hoge.html",
			'data'      =>  $izettle->csv2html(dummy()),
			//$izettle->csv2html($test->get_product_mix('10/21/2018', '10/21/2018'));
		);
	}
}