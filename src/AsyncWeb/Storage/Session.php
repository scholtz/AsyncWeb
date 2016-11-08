<?php

namespace AsyncWeb\Storage;

define("__SESSION_checksum_var","__SESSION_CHECKSUM");
define("__SESSION_time_var","__SESSION_TIME");
define("__SESSION_max_life_var","__SESSION_MAX_LIFE");

/**
 * Tato trieda sa stara o session, kontroluje, ci je session nezmenena zvonku
 * 
 * ak bola zmenena, pokusi odhlasit system
 * 
 * priklad: (Session::set("my_var","value"));
 * echo (Session::get("my_var"));
 * (Se sion::set("my_var","value1"));
 * echo (Session::get("my_var"));
 * print_r($_SESSION);
 * 
 *@author Ludovit Scholtz
 */

 
class Session{
  public static $SESSION_TIMEOUT = 3600; 	// how long is inactive session held
  public static $MAX_SESSION_LIFE = 86000; 	// maximum time for one session to be held
  public static $CHECK_IP = false;			// Verify against change of IP address
  public static $USES_LONG_TIMEOUT = true;	// Uses long timeout
  public static $USE_DOMAIN_LEVEL_COOOKIE = false; // Use subdomains for the same auth session

  protected static $initializing = false;
  
  private function __construct($force=true){
	  
	Session::set_cookie_params();
   @session_start();
   \AsyncWeb\HTTP\Header::send("Cache-Control: private");
   Session::check_timeout();
   if(\AsyncWeb\Security\Auth::controllerIsRegistered('\AsyncWeb\Security\TrustedIPController')){
    Session::$CHECK_IP = false;
   }
   Session::$initializing=false;
  }
  public static function init($force=false){
	if(!isset($_COOKIE["PHPSESSID"]) && $force==false){return;}
	
	if(!Session::$inst){
		if(Session::$initializing) return;
		Session::$initializing = true;
		Session::$inst = new Session($force);
	}
  }
  public static function initialized(){
	if(Session::$inst) return true;
	return false;
  }
  public static function id(){
	Session::init();
	if(Session::$inst){
		return session_id();
	}
	return false;
  }
  private static $inst = null;
  /**
   * Tato funkcia vrati premennu zo session
   *
   * @param unknown_type $var
   */
  public static function refresh(){
	Session::set(__SESSION_max_life_var,\AsyncWeb\Date\Time::get());
  }
  public static function get($var){
	Session::init();
    if(!Session::$inst) return false;
  	if(isset($_SESSION)){
  	 if(!array_key_exists(__SESSION_max_life_var,$_SESSION)){ 
		Session::refresh();
	 }
  	}
  	
   /* check for session stealing */
   /* mozno by to bolo dat do casti kde kontroluje ine veci, ktora sa spusta iba raz. /**/

  if(@$_SERVER['REMOTE_ADDR'] && 			!@$_SESSION["SESSION_STEALING__IP"])  Session::set("SESSION_STEALING__IP",@$_SERVER['REMOTE_ADDR']);
  if(@$_SERVER['HTTP_USER_AGENT'] && 		!@$_SESSION["SESSION_STEALING__UA"])  Session::set("SESSION_STEALING__UA",@$_SERVER['HTTP_USER_AGENT']);
  if(@$_SERVER['GATEWAY_INTERFACE'] && 		!@$_SESSION["SESSION_STEALING__GI"])  Session::set("SESSION_STEALING__GI",@$_SERVER['GATEWAY_INTERFACE']);
  if(@$_SERVER['HTTP_X_FORWARDED_FOR'] && 	!@$_SESSION["SESSION_STEALING__FW"])  Session::set("SESSION_STEALING__FW",@$_SERVER['HTTP_X_FORWARDED_FOR']);
  if(@$_SERVER['HTTP_VIA'] && 				!@$_SESSION["SESSION_STEALING__VIA"]) Session::set("SESSION_STEALING__VIA",@$_SERVER['HTTP_VIA']);

//   var_dump($_SESSION);
//   var_dump($_SERVER);

	if(Session::$CHECK_IP){

    if(@$_SESSION["SESSION_STEALING__IP"] != @$_SERVER['REMOTE_ADDR'] ||
     @$_SESSION["SESSION_STEALING__UA"]   != @$_SERVER['HTTP_USER_AGENT'] ||
     @$_SESSION["SESSION_STEALING__GI"]   != @$_SERVER['GATEWAY_INTERFACE'] ||
     @$_SESSION["SESSION_STEALING__FW"]   != @$_SERVER['HTTP_X_FORWARDED_FOR']
     ){
 
     	\AsyncWeb\Storage\Log::log("SESSION_module",
"SESSION STEALING - 
ip:".@$_SERVER['REMOTE_ADDR'].";
ua:".@$_SERVER['HTTP_USER_AGENT'].";
via:".@$_SERVER['HTTP_VIA'].";
for: ".@$_SERVER['HTTP_X_FORWARDED_FOR']."

Was:
ip:".@$_SESSION['SESSION_STEALING__IP'].";
ua:".@$_SESSION['SESSION_STEALING__UA'].";
via:".@$_SESSION['SESSION_STEALING__VIA'].";
for: ".@$_SESSION['SESSION_STEALING__FW']
     	,ML__TOP_PRIORITY);
     	session_destroy();
		Session::set_cookie_params();
     	session_start();
     	if(class_exists("\AsyncWeb\Text\Messages")){
     		\AsyncWeb\Text\Messages::getInstance()->error(\AsyncWeb\System\Language::get("You have changed your IP address, upgraded browser, or some other security error has occured. Your session has been terminated."));
     	}
     }
	 
     }else{
	 
    if(
     @$_SESSION["SESSION_STEALING__UA"]   != @$_SERVER['HTTP_USER_AGENT']
     ){
 
     	
     	\AsyncWeb\Storage\Log::log("SESSION_module",
"SESSION STEALING - 
ip:".@$_SERVER['REMOTE_ADDR'].";
ua:".@$_SERVER['HTTP_USER_AGENT'].";
via:".@$_SERVER['HTTP_VIA'].";
for: ".@$_SERVER['HTTP_X_FORWARDED_FOR']."

Was:
ip:".@$_SESSION['SESSION_STEALING__IP'].";
ua:".@$_SESSION['SESSION_STEALING__UA'].";
via:".@$_SESSION['SESSION_STEALING__VIA'].";
for: ".@$_SESSION['SESSION_STEALING__FW']
     	,ML__TOP_PRIORITY);
     	session_destroy();
		Session::set_cookie_params();
     	session_start();
     	
     	\AsyncWeb\Text\Messages::getInstance()->error(\AsyncWeb\System\Language::get("You have upgraded your browser, or some other security error has occured. Your session has been terminated."));
     	
     }
	 }
	 
     if(@$_SESSION["SESSION_STEALING__VIA"]  != @$_SERVER['HTTP_VIA']){
     	\AsyncWeb\Storage\Log::log("SESSION_module","SESSION_STEALING__VIA changed :".@$_SESSION["SESSION_STEALING__VIA"]."\n".@$_SERVER['HTTP_VIA'],ML__HIGH_PRIORITY);
     }
	/**/
  	
  	
   if(!Session::check_checksum()) return "";
   return @$_SESSION[$var];
  }
  private static function set_cookie_params(){
	if(isset($_SERVER["HTTP_HOST"])){
		if(Session::$USE_DOMAIN_LEVEL_COOOKIE){
			$doma = explode(".",$_SERVER["HTTP_HOST"]);
			$domain = ".".$doma[count($doma)-2].".".$doma[count($doma)-1];
		}else{
			$domain = $_SERVER["HTTP_HOST"];
		}
		if($pos = strpos($domain,":")){
			$domain = substr($domain,0,$pos);
		}
		session_set_cookie_params(0,"/",$domain,$ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'),true);
	}
  }
  private static function check_timeout(){
  	
	$ctime = \AsyncWeb\Date\Time::get();
	$time=$ctime;
  	if(isset($_SESSION)){
  	 if(array_key_exists(__SESSION_time_var,$_SESSION)){ $time = $_SESSION[__SESSION_time_var];}
  	}
  	if(!\AsyncWeb\Security\Auth::$CHECKING && \AsyncWeb\Security\Auth::userId()){
		if(array_key_exists(__SESSION_max_life_var,$_SESSION)){
			if($_SESSION[__SESSION_max_life_var] + Session::$MAX_SESSION_LIFE < $ctime){
				$id = session_id();
				@session_destroy();
				
				Session::set_cookie_params();
				@session_start($id);
				
				\AsyncWeb\Text\Messages::getInstance()->error(\AsyncWeb\System\Language::get("You have been inactive for too long! Your session has expired."));
				\AsyncWeb\HTTP\Header::s("reload");//\AsyncWeb\HTTP\Header::s("location","/");
				
			}
		}
	}
	if(Session::$USES_LONG_TIMEOUT){
		if(!\AsyncWeb\Security\Auth::$CHECKING && \AsyncWeb\Security\Auth::userId()){
			if($time < ($ctime - Session::$SESSION_TIMEOUT)){
				
				@session_destroy();
				@session_start();
				if(class_exists("\AsyncWeb\Text\Messages")){
					\AsyncWeb\Text\Messages::getInstance()->error(\AsyncWeb\System\Language::get("You have been inactive for too long! Your session has expired."));
					\AsyncWeb\HTTP\Header::s("reload");
				}else{
					echo \AsyncWeb\System\Language::get("You have been inactive for too long! Your session has expired.");
					exit;
				}
			}
		}
	}
  }
  /**
   * Nastavi session premennu
   *
   * @param string $var
   * @param string $value
   */
  public static function set($var,$value){
//  	var_dump($var." ".$value);
   Session::init(true);
   if(!Session::check_checksum()) return false;
   $_SESSION[$var] = $value;
   $_SESSION[__SESSION_checksum_var] = Session::make_checksum();
   return $value;
  }
  /**
   * Odstrani session premennu
   *
   * @param unknown_type $var
   * @return unknown
   */
  public static function _unset($var){
   Session::init(true);
   if(!Session::check_checksum()) return false;
   unset($_SESSION[$var]);
   $_SESSION[__SESSION_checksum_var] = Session::make_checksum();
   return true;   
  }
  
  /**
   * Skontroluje checksum
   *
   * @return boolean Ak ano, tak je platna, ak nie, tak nieje platna.
   */
  public static function check_checksum(){
  	
  	if(!isset($_SESSION)){
  		return false;
  	}
  	if(!@$_SESSION[__SESSION_checksum_var]){
  		/**
  		 * Toto asi bude treba opravit, lebo ked niekto ma pristup k sesssion, a ju modifikuje, a zaroven zrusi checksum premennu tak si to system nevsimne
  		 * */
  		return true;
  	}
  	if( (Session::$checksum == null && (Session::make_checksum()  == @$_SESSION[__SESSION_checksum_var])) || 
  		(Session::$checksum != null && (Session::$checksum  == @$_SESSION[__SESSION_checksum_var]))  
  	){
  		return true;
  	}else{
//  		echo "b"; exit;
  		if(isset($_SESSION) && $_SESSION) {session_destroy();$_SESSION = array();}
		\AsyncWeb\Security\Auth::logout();

  		if(class_exists('\AsyncWeb\Text\Messages')){
  			\AsyncWeb\Text\Messages::die_error(\AsyncWeb\System\Language::get("Session was modified outside of system!!!!"));
  		}else{
			echo "killing sess.";
  		}
  		return false;
  	}
  }
  
  
  private static $checksum = null;
  /**
   * Vytvori md5 session checksum
   *
   * @return string32 session checksum
   */
  
  private static function make_checksum(){
   $ret = "";
   $_SESSION[__SESSION_time_var] = \AsyncWeb\Date\Time::get();
   
   if(isset($_SESSION) && is_array($_SESSION))
   foreach ($_SESSION as $s =>$v){
   	if($s!=__SESSION_checksum_var){
   	 $ret = md5($ret.$s);
   	}
   }
   Session::$checksum = $ret;
   return $ret;
  }

  
}
