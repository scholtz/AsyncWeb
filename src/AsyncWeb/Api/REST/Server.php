<?php

namespace AsyncWeb\Api\REST;

class Server{
	public static $Services = array();
	public static function Register($prepend = "/rest/", $classes=array(), $namespace = "\\"){
		$prepend = str_replace("/","\\/",$prepend);
		foreach($classes as $class){
			if(isset(self::$Services[$class])) continue;
			self::$Services[$class] = $class;
			$methods = get_class_methods($namespace.$class);
			if($methods){
				foreach($methods as $method){
					\AsyncWeb\System\Router::addRoute($match = '/^'.$prepend.$class.'\/'.$method.'[\/]*(.*)$/',array("\\AsyncWeb\\Api\REST\\Server","Process"),false,array("class"=>$namespace.$class,"method"=>$method));
					if($class != strtolower($class)){
						$classLower = strtolower($class);
						\AsyncWeb\System\Router::addRoute($match = '/^'.$prepend.$classLower.'\/'.$method.'[\/]*(.*)$/',array("\\AsyncWeb\\Api\REST\\Server","Process"),false,array("class"=>$namespace.$class,"method"=>$method));
					}
				}
			}
		}
		
		if(!$classes){
			// register failover
			\AsyncWeb\System\Router::addRoute($match = '/^'.$prepend.'(.*)$/',array("\\AsyncWeb\\Api\REST\\Server","MethodDoesNotExists"),false,array());
		}
	}
	public static function MethodDoesNotExists($router){
		$exc =  new \Exception(\AsyncWeb\System\Language::get("Method does not exists: %method%",array("%method%"=>$router["matches"][1]))); 
		header("Content-Type: application/json");
		$error = array("Status"=>"error","Text"=>$exc->getMessage(),"Type"=>get_class($exc));
		echo json_encode($error);
		exit;
	}
	public static function Process($router){
		header("Content-Type: application/json");
		try{
			$params = explode("/",@$router["matches"][1]);
			$ret = array();
			$ret["Result"] = call_user_func_array(array($router["data"]["class"],$router["data"]["method"]),$params);
			$ret["Status"] = "ok";
			echo json_encode($ret);
			exit;
		}catch(\Exception $exc){
			$error = array("Status"=>"error","Text"=>$exc->getMessage(),"Type"=>get_class($exc));
			echo json_encode($error);
			exit;
		}
	}
}
