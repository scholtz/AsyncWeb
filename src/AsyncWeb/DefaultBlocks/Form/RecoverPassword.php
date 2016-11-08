<?php

namespace AsyncWeb\DefaultBlocks\Form;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;
use AsyncWeb\Email\Email;
use AsyncWeb\Text\Texts;
use AsyncWeb\Frontend\URLParser;

class RecoverPassword extends \AsyncWeb\DefaultBlocks\Form{
	protected $showType = "INSERT";
	public static $URI_CODE = "/Content_Cat:Form_RecoverPasswordCode";
	public static $LOGIN_NAME_CREATION_STRATEGY = "last-first-3";
	
	public static function beforeInsert($r){
		$row = DB::gr("users",array("email"=>URLParser::v("passwordrecovery_email")));
		if(!$row){
			throw new \Exception(Language::get("Unknown email: %email%",array("%email%"=>$_REQUEST["passwordrecovery_email"])));
		}
		return true;
	}
	
	public static function onInsert($row){
		$usr = DB::gr("users",array("email"=>URLParser::v("passwordrecovery_email")));
		$login = $usr["login"];
		if($usr["gender"] == "m"){
			$title = "Mr.";
		}else{
			$title = "Mrs.";
		}
		$title .= " ".$usr["lastname"];
		
		$link = "http".(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'?"s":"")."://".$_SERVER["HTTP_HOST"].RecoverPassword::$URI_CODE."/code=".$row["row"]["code"];
		$email = "Dear ".$title."

Someone from IP address ".$_SERVER["REMOTE_ADDR"]." has requested a password recovery for your account $login.

You can set your new password in this link:

$link";

		if(!Email::send($row["row"]["email"],Language::get("Password recovery"),$email)){
			\AsyncWeb\Text\Msg::err(Language::get("Error occured while sending the email."));
		}
		return;
	}
	
	public static function newCode(){
		if(function_exists("openssl_random_pseudo_bytes")){
			return substr(bin2hex(openssl_random_pseudo_bytes(5)),0,10);
		}else{
			return substr(md5(uniqid()),0,10);
		}
	}
	
	public function initTemplate(){
		$this->formSettings = $form = array(
			"table" => "passwordrecovery",
			"col" => array(
			 array("name"=>Language::get("Your email address"),"form"=>array("type"=>"textbox"),"data"=>array("col"=>"email","datatype"=>"email"),"usage"=>array("MFi","MFu","MFd")),
			 //array("name"=>Language::get("Captcha"),"form"=>array("type"=>"captcha"),"data"=>array("col"=>"validacny_kod"),"usage"=>array("MFi","MFu","MFd")),
			 array("form"=>array("type"=>"value"),"data"=>array("col"=>"code"),"texts"=>array("text"=>"PHP::\\AsyncWeb\\DefaultBlocks\\Form\\RecoverPassword::newCode()"),"usage"=>array("MFi",)),
			 array("form"=>array("type"=>"value"),"data"=>array("col"=>"created"),"texts"=>array("text"=>"PHP::time()"),"usage"=>array("MFi",)),
			 array("form"=>array("type"=>"submit"),"texts"=>array("insert"=>Language::get("Reset your password"),"update"=>"MF_update","delete"=>"MF_delete","reset"=>"MF_reset"),"usage"=>array("MFi","MFu","MFd")),
			),
			"uid"=>"passwordrecovery",
			"texts"=>array("insertSuccess"=>Language::get("We have sent you password recovery link to your email address.")),

			"allowInsert"=>true,"allowUpdate"=>false,"allowDelete"=>false,"useForms"=>true,
			//"rights"=>array(),
					 
		    "iter"=>array("per_page"=>"20"),
				"bootstrap"=>"1",
				"execute"=>array(
				  "beforeInsert"=>"PHP::\\AsyncWeb\\DefaultBlocks\\Form\\RecoverPassword::beforeInsert()",
				  "onInsert"=>"PHP::\\AsyncWeb\\DefaultBlocks\\Form\\RecoverPassword::onInsert()",
			 ),

			);
		$this->initTemplateForm();
	}
	protected function initTemplateForm(){
		$ret = "<h1>".Language::get("Recover lost password").'</h1>';
		$form = new \AsyncWeb\View\MakeForm($this->formSettings);
		$form->BT_WIDTH_OF_LABEL = 2;
		$form->check_update();
		$ret .= $form->show($this->showType);
		$this->template = $ret;
		
	}
	
}