<?php
namespace AsyncWeb\System;

class System{
	public static function isSecure() {
	  return
	    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
	    || $_SERVER['SERVER_PORT'] == 443;
	}
	public static function getOS(){
		if(PHP_OS == "Linux") return "linux";
		if(substr(strtoupper(PHP_OS),0,3) == "WIN") return "win";

		if(strpos($_SERVER["SystemRoot"],"indows")!==false){
			return "win";
		}
		return "linux";
	}
	private static $dom = null;
	public static function getDomain(){
		if(System::$dom !== null) return System::$dom;
		return $_SERVER["HTTP_HOST"];
	}
	public static function setDomain($domain){
		System::$dom = $domain;
	}
	public static function setSecureHost($secure){
		System::$usessl = $secure || $secure;
	}
	private static $usessl = false;
	public static function getAddr(){
		if(System::$usessl) return "https://".System::getDomain();
		return "http://".System::getDomain();
	}
}
