<?php #block4.php
date_default_timezone_set('America/Denver');
require_once ('../mysql_connect_sched.php'); //Connect to the db.
include ('./display_functions.php');
$today = $_GET['today'];
$now = strtotime($today);
$division = 'Subs';
echo '<div id="weekDiv">';
subs_weekly($division, $now);
echo '</div>';
?>