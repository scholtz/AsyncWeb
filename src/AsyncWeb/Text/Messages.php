<?php
/**
 Tato trieda sa bude starat o chybové hlášky, varovné hlášky a správy systému
 verzion 1.0 .. 20050806 first release
 20051116 uprava dokumentu cez analyzer.
 verzion 1.1
 20051117 pridana funkcia msg
 20051117 zmena defaultnej hodnoty alert pri mes na false
 20060129 pridana funkcia die_error.. hlavne pre db, aby mohla zobrazit hlasku, ked sa zruti server..
 
 Example::
 

 
 
  $mes = Messages::getInstance();
  $mes->error("Error message");
  
  || 
  
  try{
   ..
  }catch(Exception $e){
   $mes = Messages::getInstance();
   $mes->error($e->getMessage());
  }
  
  ..potom
  echo $mes->show();
*/

namespace AsyncWeb\Text;

class Messages{

 private $err = "";
 private $msg = "";
 private $warn = "";
 private $alert = "";

 private $conf_ses_save_err = "Message_err";
 private $conf_ses_save_warn = "Message_warn";
 private $conf_ses_save_msg = "Message_msg";
 private $conf_ses_save_alert = "Message_alert";

 private function __construct(){
  if($this->err == "" && \AsyncWeb\Storage\Session::get($this->conf_ses_save_err)){ $this->err = \AsyncWeb\Storage\Session::get($this->conf_ses_save_err);}
  if($this->warn == "" && \AsyncWeb\Storage\Session::get($this->conf_ses_save_warn)){ $this->warn = \AsyncWeb\Storage\Session::get($this->conf_ses_save_warn);}
  if($this->msg == "" && \AsyncWeb\Storage\Session::get($this->conf_ses_save_msg)){ $this->msg = \AsyncWeb\Storage\Session::get($this->conf_ses_save_msg);}
  if($this->alert == "" && \AsyncWeb\Storage\Session::get($this->conf_ses_save_alert)){ $this->alert = \AsyncWeb\Storage\Session::get($this->conf_ses_save_alert);}
 }
/**
 * Instance
 *
 * @return Messages instance
 */
 public static function getInstance(){
  static $instance;
  if($instance == NULL){
   $instance = new Messages();
  }
  return $instance;
 }

 private function addAlert($mes){
  $this->alert .= "alert('".$mes."');";
  \AsyncWeb\Storage\Session::set($this->conf_ses_save_alert, $this->alert);
 }

 public function error($mes,$alert = false, $finish = false){
  \AsyncWeb\Storage\Log::log(
  "MessagesError","Bola zobrazena chybova hlaska: ".addslashes($mes),ML__LOW_PRIORITY
  );
  $this->err .= '<div class="error alert alert-danger">'.$mes.'</div>'."\n";
  \AsyncWeb\Storage\Session::set($this->conf_ses_save_err, $this->err);
  if($alert){
   $this->addAlert($mes);
  }
  if($finish){
  	echo $this->show();
  	exit;
  }
 }

 
 public static function die_error($error){
	\AsyncWeb\HTTP\Header::send("Content-type: text/html; charset=UTF-8");
	\AsyncWeb\HTTP\Header::send('HTTP/1.1 500 Internal Server Error');
 	echo $error;
 	exit;
 }
 
 public function msg($mes,$alert = false){
 	$this->mes($mes,$alert);
 }
 
 public function mes($mes,$alert = false){
  $this->msg .= '<div class="msg alert alert-success">'.$mes.'</div>'."\n";
  \AsyncWeb\Storage\Session::set($this->conf_ses_save_msg, $this->msg);
  if($alert){
   $this->addAlert($mes);
  }
 }
 
 /**
  Tu je zatial class="error"
 */
 public function warning($mes,$alert = true){
  \AsyncWeb\Storage\Log::log(
  "MessagesWarning","Bola zobrazena varovacia hlaska: ".addslashes($mes),ML__LOW_PRIORITY
  );
  $this->warn .= '<div class="error alert alert-warning">'.$mes.'</div>'."\n";
  \AsyncWeb\Storage\Session::set($this->conf_ses_save_err, $this->warn);
  if($alert){
   $this->addAlert($mes);
  }
 }

 public function show(){
  $ret = $this->err.$this->warn.$this->msg;
  $this->clear();
  return $ret;
 }

 public function show_alert(){
  $ret = $this->alert;
  $this->clear_alert();
  return htmlspecialchars(strip_tags($ret));
 }/**/
 
 private function clear_msg(){
  $this->msg = "";
  \AsyncWeb\Storage\Session::set($this->conf_ses_save_msg, "");
 }
 
 private function clear_warn(){
  $this->warn = "";
  \AsyncWeb\Storage\Session::set($this->conf_ses_save_warn,"");
 }
 
 private function clear_err(){
  $this->err = "";
  \AsyncWeb\Storage\Session::set($this->conf_ses_save_err, "");
 }
 private function clear_alert(){
  $this->alert = "";
  \AsyncWeb\Storage\Session::set($this->conf_ses_save_alert, "");
 }
 
 public function clear(){
  $this->clear_msg();
  $this->clear_warn();
  $this->clear_err();
  $this->clear_alert();
 }
 
 public static function message($msg){
  Messages::getInstance()->msg($msg);
 }
 public static function err($msg){
  Messages::getInstance()->error($msg);
 }
};

?>