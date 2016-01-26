<?php
namespace AsyncWeb\System;


class Router{
	protected static $routes = array();
	public static function addRoute($match,$callback,$continue=false){
		Router::$routes[] = array("match"=>$match,"callback"=>$callback,"continue"=>$continue);
	}
	/**
	@return 0|1 0 if should continue, 1 if match found with no continue
	*/
	public static function run($url = null){
		if(!$url) $url = \AsyncWeb\Frontend\URLParser::getCurrent();
		foreach(Router::$routes as $route){
			$matches = array();
			if(preg_match($route["match"], $url, $matches)) {
				call_user_func_array($route['callback'], $matches);
				if(!$route["continue"]) return 1;
			}
		}
		return 0;
	}
}