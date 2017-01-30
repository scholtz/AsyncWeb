<?php

namespace AsyncWeb\IO;

class File{
	public static function exists($file,$dbg=false)
	{
		
		$paths = explode(PATH_SEPARATOR, get_include_path());
	 
		foreach ($paths as $path) {
			$real = realpath($path);
			// Formulate the absolute path
			
			$pos = strrpos($real,"/");
			if($pos <= 0) $pos = strlen($real);
			if(substr($file,0,$pos) == substr($real,0,$pos)){
				$fullpath =  $file;
			}else{
				$fullpath =  $real . DIRECTORY_SEPARATOR . $file;
			}
			
			// Check it
			if (file_exists($fullpath)) {
				return $fullpath;
			}
		}
		
		return false;
	}
	public static function get($file){
		return file_get_contents($file,true);
	}
	public static function read($file){
		return file_get_contents($file);
	}
	public static function write($file,$content,$options=null){
		return file_put_contents($file,$content,$options);
	}
	public static function load($file){
		return self::read($file);
	}
}