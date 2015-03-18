<?php

namespace AsyncWeb\Security;
use \AsyncWeb\Storage\Session;

class Auth{
	protected static $SESS_CHAIN_NAME = "_AUTH_CHAIN";
	public static function check(){
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
					throw new \AsyncWeb\Exceptions\SecurityException("Service provider is not registered! 0x9319510");
				}
				if(!Auth::$services[$service["id"]]->check($service)){
					throw new \AsyncWeb\Exceptions\SecurityException("Out of date data! 0x9319511");
				}
			}
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
		return $ret;
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
		return Session::set(Auth::$SESS_CHAIN_NAME,$auth);
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
	public static function userId(){
		$auth = Session::get(Auth::$SESS_CHAIN_NAME);
		if($auth && is_array($auth)){
			$data = array_pop($auth);
			return $data["userid"];
		}
		return false;
	}
	protected static $services = array();
	public static function register(\AsyncWeb\Security\AuthService $service){
		Auth::$services[$service->SERVICE_ID()] = $service;
	}
	protected static $controllers = array();
	public static function registerController(\AsyncWeb\Security\AuthController $controller){
		Auth::$controllers[$controller->SERVICE_ID()] = $controller;
	}
	public static function loginForm(){
		$ret = '';
		foreach(Auth::$services as $service){
			$ret.=$service->loginForm();
		}
		return $ret;
	}
}
