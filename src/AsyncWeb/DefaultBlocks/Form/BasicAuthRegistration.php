<?php

namespace AsyncWeb\DefaultBlocks\Form;

use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;
use AsyncWeb\Email\Email;
use AsyncWeb\Text\Texts;
use AsyncWeb\Frontend\URLParser;

class BasicAuthRegistration extends \AsyncWeb\DefaultBlocks\Form{
	protected $showType = "INSERT";
	
	public static $LOGIN_NAME_CREATION_STRATEGY = "last-first-3";
	
	public static function beforeInsert($r){
		$row = DB::gr("users",$where=array("email"=>URLParser::v("basicauthregistration_email")));
		if($row){
			throw new \Exception(Language::get("Email %email% has been already registered",array("%email%"=>$row["email"])));
		}
		return true;
	}
	
	public static function onInsert($row){
		if(function_exists("openssl_random_pseudo_bytes")){
			$pass = bin2hex(openssl_random_pseudo_bytes(5));
			$cohash = bin2hex(openssl_random_pseudo_bytes(5));
		}else{
			$pass = substr(md5(uniqid()),0,10);
			$cohash = substr(md5(uniqid()),0,10);
		}
		if(BasicAuthRegistration::$LOGIN_NAME_CREATION_STRATEGY == "email"){
			$login = $row["row"]["email"];
		}else if(BasicAuthRegistration::$LOGIN_NAME_CREATION_STRATEGY == "last-first-3"){
			$login = Texts::clear($row["row"]["lastname"]."-".$row["row"]["firstname"]."-".rand(100,999));
		}
		$cohash = "OFiapci@ifp##!Q-";
		$passH = hash('sha256', $pass);
		$passH = hash('sha256', $cohash.$passH);
		DB::u("users",$row["row"]["id2"],array("login"=>$login,"password"=>$passH,"cohash"=>$cohash,"active"=>"1","active_until"=> "0"));
		$title = "Mr.";
		if($row["row"]["gender"] == "f") $title = "Mrs.";
		
		if($row["row"]["firstname"]) $title.=" ".$row["row"]["firstname"];
		if($row["row"]["lastname"]) $title.=" ".$row["row"]["lastname"];
		
		$email = "Dear $title 
		
Please find your credentials to ".$_SERVER["HTTP_HOST"]." below:

Username: $login
Password: $pass";

		if(!Email::send($row["row"]["email"],"Registration",$email)){
			\AsyncWeb\Text\Msg::err(Language::get("Error occured while sending the email."));
		}
		return;
	}
	public function initTemplate(){
		$this->formSettings = $form = array(
			"table" => "users",
			"col" => array(
			 array("name"=>Language::get("Gender"),"data"=>array("col"=>"gender"),"form"=>array("type"=>"select"),"filter"=>array("type"=>"option","option"=>array(
			   "m"=>Language::get("Male"),
			   "f"=>Language::get("Female"),
				),
			 ),),
			 array("name"=>Language::get("First name"),"form"=>array("type"=>"textbox"),"data"=>array("col"=>"firstname"),"usage"=>array("MFi","MFu","MFd")),
			 array("name"=>Language::get("Last name"),"form"=>array("type"=>"textbox"),"data"=>array("col"=>"lastname"),"usage"=>array("MFi","MFu","MFd")),
			 array("name"=>Language::get("Email"),"form"=>array("type"=>"textbox"),"data"=>array("col"=>"email","datatype"=>"email"),"usage"=>array("MFi","MFu","MFd")),
			 array("name"=>Language::get("Captcha"),"form"=>array("type"=>"captcha"),"data"=>array("col"=>"captcha"),"usage"=>array("MFi","MFu","MFd")),
			 array("form"=>array("type"=>"submit"),"texts"=>array("insert"=>Language::get("Register"),"update"=>"MF_update","delete"=>"MF_delete","reset"=>"MF_reset"),"usage"=>array("MFi","MFu","MFd")),
			),
			"order" => array(
			 "od"=>"asc",
			 ),
			"texts"=>array("insertSuccess"=>Language::get("You have successfully registered. We have sent you credentials to your email address.")),
			"uid"=>"BasicAuthRegistration",
			"show_export"=>true,"show_filter"=>true,

			"allowInsert"=>true,"allowUpdate"=>false,"allowDelete"=>false,"useForms"=>true,
			//"rights"=>array(),
					 
		    "iter"=>array("per_page"=>"20"),
			"bootstrap"=>true,
			
			"execute"=>array(
				  "beforeInsert"=>"PHP::\\AsyncWeb\\DefaultBlocks\\Form\\BasicAuthRegistration::beforeInsert()",
				  "onInsert"=>"PHP::\\AsyncWeb\\DefaultBlocks\\Form\\BasicAuthRegistration::onInsert()",
			 ),

			);
		
		if(\AsyncWeb\Objects\Group::is_in_group("admin")){
			$this->formSettings["where"] = array();
		}
		//
		$this->initTemplateForm();
	}
	protected function initTemplateForm(){
		$ret = "";
		$form = new \AsyncWeb\View\MakeForm($this->formSettings);
		$form->BT_WIDTH_OF_LABEL = 4;
		$form->check_update();
		$ret .= $form->show($this->showType);
		$this->template = $ret;
		
	}
	
}