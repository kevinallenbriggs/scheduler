<?php
session_save_path("../sess_tmp/");
session_name ('VisitID');
session_start();

if(!isset($_SESSION['role'])){
	$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
	if ((substr($url, -1) == '/') OR (substr($url, -1) == '\\')){
		$url = substr($url, 0, -1);
		}
	$url .= '/login';
	header("Location: $url");
	exit();
	}
	
if (($_SESSION['role'] != 'Admin')&&($_SESSION['role'] != 'Supervisor')&&($_SESSION['role'] != 'Subs')){
	$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
	if ((substr($url, -1) == '/') OR (substr($url, -1) == '\\')){
		$url = substr($url, 0, -1);
		}
	$url .= '/404';
	header("Location: $url");
	exit();
	}

?>