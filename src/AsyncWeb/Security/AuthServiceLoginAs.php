<?php

namespace AsyncWeb\Security;
use \AsyncWeb\Security\AuthService;


class AuthServiceLoginAs implements AuthService{
	public static $TABLE_ASSOC = "users_superconnection";
	
	public function SERVICE_ID(){ return "\\AsyncWeb\\Security\\AuthServiceLoginAs";}
	public function check(Array $data=array()){
		if($data){ return $this->checkData($data);}
		return $this->checkAuth();
	}
	protected function checkAuth(){
		if(isset($_REQUEST["AuthServiceLoginAs_LOGIN"]) && $_REQUEST["AuthServiceLoginAs_LOGIN"]){
			$data = array();
			$from = Auth::userId();
			$to = $_REQUEST["AUTH_TO"];
			$row = \AsyncWeb\DB\DB::qbr(AuthServiceLoginAs::$TABLE_ASSOC,array("where"=>array("from"=>$from,"to"=>$to),"cols"=>array("id2")));
			if($row){
				Auth::auth(array("from"=>$from,"userid"=>$to),$this);
				return true;
			}
		}
		return false;
	}
	
	protected function checkData($data){
		$row = \AsyncWeb\DB\DB::qbr(AuthServiceLoginAs::$TABLE_ASSOC,array("where"=>array("from"=>$data["from"],"to"=>$data["userid"]),"cols"=>array("id2")));
		if($row){
			return true;
		}
		throw new \AsyncWeb\Exceptions\SecurityException("User is not allowed to be logged as another user! 0x8310591");
	}
	
	public function loginForm(){
		$ret = "";
		return $ret;
		
	}
	
}
