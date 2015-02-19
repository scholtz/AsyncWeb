<?php
use AsyncWeb\Frontend\BlockManagement;

require '../vendor/autoload.php';

if(file_exists("settings.php")){
	require_once("settings.php");
}

BlockManagement::renderWeb();

?>