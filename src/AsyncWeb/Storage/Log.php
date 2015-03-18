<?php
namespace AsyncWeb\Storage;
use \AsyncWeb\Storage\LogInterface;

/**
 Tato trieda vznikla za ucelom logovania.
 Loguje sa 
  nadpis spravy
  popis spravy
  cas
  $_SESSION["user_id"]
  ipcka
  priorita
  
 version 1.0 .. 20051022 first release
 
 zmeny:
 
 version 1.1 .. 20120819 Added functionality to send automated emails on hacking attempts. It is required to preconfigure in the configuration of the project:

  
 \AsyncWeb\Storage\Log::$alerts = array(
  ML__TOP_PRIORITY=>array(
   array("email"=>"sekretariat@kbb.sk","from"=>"admin@kbb.sk"),
  )
 );

 Priklad::
 \AsyncWeb\Storage\Log::log("Module1","Hacking attempt",ML__TOP_PRIORITY);

 Priklad::
  $my_log = \AsyncWeb\Storage\MysqlLog::getInstance();
  $my_log->set_security_level(ML__MEDIUM_SEC_LEVEL); // defaultne je nast. na low (loguje vsetko)
  $my_log->log(
   "Program vykonal nepovolenu operaciu",
   "Program .................. a nakoniec bude zavrety",
   ML__TOP_PRIORITY
  );
  
 Urovne logovania
  ML__LOW_SEC_LEVEL
  ML__NORMAL_SEC_LEVEL
  ML__MEDIUM_SEC_LEVEL
  ML__HIGH_SEC_LEVEL
  ML__TOP_SEC_LEVEL
 
 uzivatel potom kontroluje 
  $log->log("hck", "niekto sa snazil naburat do systemu cez...",ML__HIGH_PRIORITY);
  ak je nastavena nastavena bezpecnost logovania na ML__HIGH_PRIORITY a viac, tak to zaloguje
  inak nie
  
 mozne priority
  nizka        >  ML__LOW_PRIORITY
  normalna     >  ML__NORMAL_PRIORITY
  stredna      >  ML__MEDIUM_PRIORITY
  vysoka       >  ML__HIGH_PRIORITY
  velmi vysoka >  ML__TOP_PRIORITY
  
*/


/*
 Definicie urovne zabezpecenia
*/
if(!defined('ML__LOW_SEC_LEVEL')){

define('ML__LOW_SEC_LEVEL'   ,0x1);
define('ML__NORMAL_SEC_LEVEL',0x10);
define('ML__MEDIUM_SEC_LEVEL',0x100);
define('ML__HIGH_SEC_LEVEL'  ,0x1000);
define('ML__TOP_SEC_LEVEL'   ,0x10000);
/*
 definicie urovni bezpecnostnych hlasok do logovania
 plati ak $level1 > $level2, tak $level1 je vecsia uroven bezp.
*/
define('ML__LOW_PRIORITY'   ,0x1);
define('ML__NORMAL_PRIORITY',0x10);
define('ML__MEDIUM_PRIORITY',0x100);
define('ML__HIGH_PRIORITY'  ,0x1000);
define('ML__TOP_PRIORITY'   ,0x10000);

}

class Log{
	private static $loggers = array();
	private static $recurs = 0;
	public static $alerts=array();
	public static function registerLogger(\AsyncWeb\System\LogInterface $logger){
		$loggers[] = $logger;
	}
	public static function log(
	  $name,
	  $text,
	  $priority = ML__NORMAL_PRIORITY
	 ){
		log::$recurs++;
		
		if(log::$recurs > 10){
		   throw new Exception(Language::get("Failed to log"));
		}
		
		foreach(Log::$loggers as $logger){
			try{
				$logger->log($name,$text,$priority);
			}catch(Exception $exc){
				
			}
		}
		log::$recurs--;
	}
}

?>