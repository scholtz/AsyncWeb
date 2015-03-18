<?php

namespace AsyncWeb\Security;
use \AsyncWeb\System\Language;
use \AsyncWeb\DB\DB;

class TrustedIPController implements AuthController{
	public function SERVICE_ID(){return "TrustedIPController";}
	public function check(){
  		$usr = Auth::superUserId();
		if(@$_REQUEST["verify"] && @$_REQUEST["AUTH_Code_TrustedIP"]){
			
			if($r = \AsyncWeb\DB\DB::gr("users_trustedips",array("users"=>$usr,"code"=>$_REQUEST["AUTH_Code_TrustedIP"],"ip"=>$_SERVER["REMOTE_ADDR"]))){
				DB::u("users_trustedips",$r["id2"],array("state"=>"verified"));
				\AsyncWeb\Text\Msg::mes(Language::get("LOGIN_form_ipverif_verified"));
			}elseif($r = DB::gr("users_trustedips",array("users"=>$usr,"code2"=>$_REQUEST["AUTH_Code_TrustedIP"],"ip"=>$_SERVER["REMOTE_ADDR"]))){
				DB::u("users_trustedips",$r["id2"],array("state"=>"verifiedtmp","verificationtime"=>Time::get()));
				\AsyncWeb\Text\Msg::mes(Language::get("LOGIN_form_ipverif_verifiedtmp"));
			}else{
				\AsyncWeb\Text\Msg::err(Language::get("LOGIN_form_ipverif_failed"));
			}
			\AsyncWeb\HTTP\Header::s("reload",array("AUTH_Code_TrustedIP"=>""));
			exit;
		}
		if(isset($_REQUEST["resend"]) && $_REQUEST["resend"]){
			TrustedIPController::sendTrustedIpEmail();
			\AsyncWeb\HTTP\Header::s("reload",array("resend"=>""));
			exit;
		}
		if(DB::gr("users_trustedips",array("users"=>$usr,"state"=>"verified","ip"=>$_SERVER["REMOTE_ADDR"]))){
			return true;
		}
		return false;
	}
	
	public static function sendTrustedIpEmail(){
		$code = substr(md5(uniqid()),0,6);
		$code2 = substr(md5(uniqid()),0,6);
		$usr = Auth::superUserId();
		
		$txt = Language::get("LOGIN_trusted_email_text",array("%code%"=>$code,"%code2%"=>$code2,"%ip%"=>$_SERVER["REMOTE_ADDR"]));
		$subj = Language::get("LOGIN_trusted_email_subject",array("%ip%"=>$_SERVER["REMOTE_ADDR"]));
		$emails = \AsyncWeb\Objects\User::getEmails($usr);
		foreach($emails as $email){
			if(Email::send($email,$subj,$txt,"admin@kbb.sk",array(),"html")){
				DB::delete("users_trustedips",array("users"=>Login::superUserId(),"ip"=>$_SERVER["REMOTE_ADDR"])); 
				DB::u("users_trustedips",md5(uniqid()),array("users"=>$usr,"ip"=>$_SERVER["REMOTE_ADDR"],"state"=>"unverified","sent"=>"1","code"=>$code,"code2"=>$code2));
				\AsyncWeb\Text\Msg::mes(Language::get("LOGIN_form_ipverif_email_sent"));
			}else{
				DB::delete("users_trustedips",array("users"=>Login::superUserId(),"ip"=>$_SERVER["REMOTE_ADDR"]));
				DB::u("users_trustedips",md5(uniqid()),array("users"=>$usr,"ip"=>$_SERVER["REMOTE_ADDR"],"state"=>"unverified","sent"=>"1","code"=>$code,"code2"=>$code2));
				\AsyncWeb\Text\Msg::mes(Language::get("LOGIN_form_ipverif_email_error"));
			}
		}
		return "verifyip";
	}
	public function form(){

	  $ret = '<form action="?" method="post">
		 <div id="login_form_1">
		<div id="login_form">
		 <h1>'.Language::get("LOGIN_form_ipverif_h1").'</h1><p>'.Language::get("LOGIN_form_ipverif_text",array("%ip%"=>$_SERVER["REMOTE_ADDR"])).'</p>
		 <table><tr><td><label for="AUTH_Code_TrustedIP">'.Language::get('LOGIN_form_ipverif_code').'</label>:</td><td><input id="AUTH_Code_TrustedIP" name="AUTH_Code_TrustedIP" value="" /></td></tr>
		 <tr><td></td><td><input type="submit" name="verify" value="'.Language::get("LOGIN_form_ipverif_submit").'" /> <input type="submit" name="resend" value="'.Language::get("LOGIN_form_ipverif_resend").'" /></td></tr></table>
		</div>
	   </div>';
	  
	  return $ret;
		
	}
}
