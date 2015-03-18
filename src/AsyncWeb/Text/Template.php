<?php

// created by Ludovit Scholtz
// ludovit __ at __ scholtz __ dot __ sk
// 31.8.2012
// 
// UPDATE 
// 13.3.2014 - Template::set($key,$value);
namespace AsyncWeb\Text;
use AsyncWeb\IO\File;

class Template{
	public static function exists($name){
		$path = \AsyncWeb\Frontend\Block::$TEMPLATES_PATH;
		$path = rtrim($path,"/");
		$path = $path."/".$file;
		
		$fullpath = File::exists($path,$dbg);
		if(!$fullpath){$file = "../templates/emailtemplate.html";}
		if(!($fullpath = File::exists($file))){return false;}
		return $fullpath;
	}
	public static function loadTemplate($name,array $data,$engine=false,$dbg=false){
		if(!($path = Template::exists($name))){
			throw new Exception(Language::get("Template %template% not found",array("%template%"=>$file)));
		}
		
		$template = File::load($path);

		if(!$engine) $engine = new \Mustache_Engine();
		return $engine->render($template,$data);
	}
}

?>