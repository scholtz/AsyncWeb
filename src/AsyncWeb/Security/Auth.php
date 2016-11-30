<?php

namespace AsyncWeb\Security;
use \AsyncWeb\Storage\Session;
use \AsyncWeb\DB\DB;

class Auth{
	protected static $SESS_CHAIN_NAME = "_AUTH_CHAIN";
	public static $TRY_TO_INSTALL_DATA = true;
	private	static $CHECKED = null;
	public static $CHECKING = false;
	/**
		Function returns 
		 if $skipControllers == true, TRUE if user is logged in or has just successfully logged in, no matter on controller state
		 if $skipControllers == true, FALSE if user is not logged in or has not just logged in
		 if $skipControllers == false, TRUE if user is logged in and all controllers are passed
		 if $skipControllers == false, FALSE if user is logged in and any of the controller has not passed
		 
		 
	*/
	public static function check($skipControllers=false){
		if(Auth::$CHECKED !== null) return Auth::$CHECKED;
		if(Auth::$CHECKING) return;
		Auth::$CHECKING = true;
		$ret = false;
		foreach(Auth::$services as $service){
			if($service->check()){
				$ret=true;
			}
		}
		
		$auth = Session::get(Auth::$SESS_CHAIN_NAME);
		if($auth && is_array($auth)){
			foreach($auth as $service){
				if(!Auth::$services[$service["id"]]){
					if(!$skipControllers){
						Auth::$CHECKING = false;
						Auth::$CHECKED = false;
					}
					throw new \AsyncWeb\Exceptions\SecurityException("Service provider (".$service["id"].") is not registered! 0x9319510");
				}
				if(!Auth::$services[$service["id"]]->check($service)){
					if(!$skipControllers){
						Auth::$CHECKING = false;
						Auth::$CHECKED = false;
					}
					throw new \AsyncWeb\Exceptions\SecurityException("Out of date data! 0x9319511");
				}
			}
			$ret = true; // if none fails, than we are ok
			if(isset($_REQUEST["logout"])){
				Auth::logout();
				\AsyncWeb\HTTP\Header::s("reload",array("logout"=>""));
				exit;
			}
		}else{
			if($ret){
				throw new \AsyncWeb\Exceptions\SecurityException("Service provider did not register user properly! 0x9319512");
			}
		}
		if(!$skipControllers && $ret){$ret = (true === Auth::checkControllers());}// if any controller fails, check is false
		Auth::$CHECKING = false;
		if(!$skipControllers){
			Auth::$CHECKED = $ret;
		}
		return $ret;
	}
	public static function countControllers(){
		return count(Auth::$controllers);
	}
	public static function checkControllers(){
		$ret = true;
		foreach(Auth::$controllers as $k=>$controller){
			if(!$controller->check()){
				return $k;
			}
		}
		return $ret;
	}
	public static function showControllerForm(){
		$k = Auth::checkControllers();
		
		return Auth::$controllers[$k]->form();
	}
	public static function auth(Array $data, AuthService $service){
		if(!isset(Auth::$services[$service->SERVICE_ID()])){throw new \AsyncWeb\Exceptions\SecurityException("Service provider '".$service->SERVICE_ID()."' is not registered! 0x9319520");}
		$auth = Session::get(Auth::$SESS_CHAIN_NAME);
		$data["id"] = $service->SERVICE_ID();
		$auth[] = $data;
		$ret= Session::set(Auth::$SESS_CHAIN_NAME,$auth);
		return $ret;
	}
	public static function logout($completelogout=false){
		if($completelogout){
			$auth = array();
		}else{
			$auth = Session::get(Auth::$SESS_CHAIN_NAME);
			array_pop($auth);
		}
		return Session::set(Auth::$SESS_CHAIN_NAME,$auth);
	}
	public static function superUserId(){
		$auth = Session::get(Auth::$SESS_CHAIN_NAME);
		if($auth && is_array($auth)){
			$data = array_shift($auth);
			return $data["userid"];
		}
		return false;
	}
	protected static $installing = false;
	public static function userId(){
		$auth = Session::get(Auth::$SESS_CHAIN_NAME);
		if($auth && is_array($auth)){
			$data = array_pop($auth);
			
			if(!Auth::$installing && $data["userid"] && Auth::$TRY_TO_INSTALL_DATA){
				Auth::$installing = true;
				$g = DB::gr("groups");
				if(!$g){
					// i have to install default groups
					DB::u("groups","1",array("id3"=>"admin","name"=>\AsyncWeb\System\Language::set("Admin"),"level"=>"256"));
					DB::u("groups","2",array("id3"=>"editor","name"=>\AsyncWeb\System\Language::set("Editor"),"level"=>"16"));
					DB::u("groups","5",array("id3"=>"HTMLEditor","name"=>\AsyncWeb\System\Language::set("Editor of HTML articles"),"level"=>"17"));
					DB::u("groups","7",array("id3"=>"PHPEditor","name"=>\AsyncWeb\System\Language::set("Editor of PHP articles"),"level"=>"17"));
					DB::u("groups","6ecacf46f8b9d84870ed676627ac84f7",array("id3"=>"MenuEditor","name"=>\AsyncWeb\System\Language::set("Editor Menu"),"level"=>"16"));
					DB::u("groups","5df18354b6fc1fb58bdc473253b17197",array("id3"=>"ProcessEditor","name"=>\AsyncWeb\System\Language::set("Process Editor"),"level"=>"16"));
					
					$usr = $data["userid"];
					DB::u("users_in_groups",md5("$usr-1"),array("users"=>$usr,"groups"=>"1"));
					DB::u("users_in_groups",md5("$usr-5"),array("users"=>$usr,"groups"=>"5"));
					DB::u("users_in_groups",md5("$usr-7"),array("users"=>$usr,"groups"=>"7"));
					DB::u("users_in_groups",md5("$usr-6ecacf46f8b9d84870ed676627ac84f7"),array("users"=>$usr,"groups"=>"6ecacf46f8b9d84870ed676627ac84f7"));
					DB::u("users_in_groups",md5("$usr-5df18354b6fc1fb58bdc473253b17197"),array("users"=>$usr,"groups"=>"5df18354b6fc1fb58bdc473253b17197"));
					
					\AsyncWeb\Cache\Cache::invalidate("menu");
					
					\AsyncWeb\Text\Msg::mes(\AsyncWeb\System\Language::get("You have just installed groups, and added your self to Admin and Editor groups."));

				}
				
			}
			
			return $data["userid"];
		}
		return false;
	}
	protected static $services = array();
	public static function register(\AsyncWeb\Security\AuthService $service){
		Auth::$services[$service->SERVICE_ID()] = $service;
	}
	public static function serviceIsRegistered($id){
		return isset(Auth::$services[$id]);
	}
	public static function countServices(){
		return count(Auth::$services);
	}

	protected static $controllers = array();
	public static function registerController(\AsyncWeb\Security\AuthController $controller){
		Auth::$controllers[$controller->SERVICE_ID()] = $controller;
	}
	public static function controllerIsRegistered($id){
		return isset(Auth::$controllers[$id]);
	}
	public static function loginForm(){
		$ret = '';
		foreach(Auth::$services as $service){
			$ret.=$service->loginForm();
		}
		if($ret){
			$ret='<fieldset><legend>'.\AsyncWeb\System\Language::get("Log in using").'</legend>'.$ret.'</fieldset>';
		}
		return $ret;
	}
}
