<?php #future_sched_generator.php

/** LOCAL VERSIONS **/

require_once("dompdf/dompdf_config.inc.php");

ob_start();
echo '<link rel="stylesheet" type="text/css" media="all" href="style/dompdf.css" />';
echo '<link rel="stylesheet" type="text/css" media="all" href="style/scheduler_tables.css" />';
date_default_timezone_set('America/Denver');
$today = date('Y-m-d');

$yesterday = strtotime('-1 day', strtotime($today));
$yesterday = date('Y-m-d', $yesterday);

/* Delete Yesterday's Schedule */
if(file_exists('future/'.$yesterday.'.pdf')){
	unlink('future/'.$yesterday.'.pdf');
	}
	
/* Add New Future Schedule */
$oneweek = strtotime('+7 days', strtotime($today));
$oneweekfull = date('Y-m-d' , $oneweek);
$day = date('j', $oneweek);
$month = date('F', $oneweek);
$year = date('Y', $oneweek);

$page_title = "$day $month $year";
require_once ('display_functions.php');

require_once ('../mysql_connect.php'); //Connect to the db.

$dom = date('j', $oneweek);
$day_long = date('l', $oneweek);
$month_long = date('F', $oneweek);
	
echo '<div id="wrapper">';
echo "<div class=\"pdf_head\"><h1>$day_long, $dom $month_long $year</h1></div>";
admin_specific($oneweek);
echo '</div>';
$html = ob_get_contents();
ob_end_clean(); 

$old_limit = ini_set("memory_limit", "192M"); 
$dompdf = new DOMPDF();
$dompdf->set_base_path('/'); 
$dompdf->load_html($html);
$dompdf->set_paper('legal', 'portrait');
$dompdf->render();
file_put_contents("future/$oneweekfull.pdf", $dompdf->output());

/** FTP VERSIONS **/
$ftp_user = 'cityofloveftp';
$ftp_pass = 'Sending files!';
$url = 'colftp.ci.loveland.co.us';

$connection = ftp_connect($url);
var_dump($connection);
$login = ftp_login($connection, $ftp_user, $ftp_pass);
if (!$connection || !$login){ die('Connection attempt failed!');}

/* Delete Yesterday's Schedule */
$ftp_files = ftp_nlist($connection, '/FromCity');
if ($ftp_files) {
	foreach ($ftp_files as $file){
		if ($file == ('/FromCity/Library/StaffSchedules/'.$yesterday.'.pdf')) {
			ftp_delete($connection, $file);
			}
		}
}

/* Add New Future Schedule */
$local_file = "future/$oneweekfull.pdf";
$ftp_path = "/FromCity/$oneweekfull.pdf";


ftp_put($connection, $ftp_path, $local_file, FTP_BINARY);
if (!$upload){echo 'FTP upload failed :(';}

ftp_close($connection);

?>