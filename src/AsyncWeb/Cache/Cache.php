<?php
namespace AsyncWeb\Cache;
/*
 Cache manager:
 
 To change from default (DB), in the code use:
 \AsyncWeb\Cache\Cache::$manager = "File";
 or
 \AsyncWeb\Cache\Cache::$manager = "DB";
 
 Using: 
 if(($o = \AsyncWeb\Cache\Cache::load($_SERVER["REQUEST_URI"]))!==null){echo $o;exit;}
 
 ... 
 
 \AsyncWeb\Cache\Cache::save($_SERVER["REQUEST_URI"],$out,60);

 To clear Cache::
 
 \AsyncWeb\Cache\Cache::invalidate("menu");
 
*/
		
class Cache{
	private static $debug = false;
	private static $debugfile="d:\debubcache.txt";
	public static $manager = false;
	public static $objectCacheDir = false;//"/dev/shm/maincache/";
	private static function serializeKey($key){
		$key = str_replace(array(":","\n",";","\t"),"_",$key);
		return $key;
	}
	public static function set($key,$group,$value){
		if(!Cache::$objectCacheDir) return false;
		if(Cache::$debug){$debug=debug_backtrace();$line=microtime(true).":E:";foreach($debug as $dbg){if(isset($dbg["function"])) $line.="F=".$dbg["function"].":A="; foreach($dbg["args"] as $k=>$v) $line.="$k=$v;";}$line.="\n";file_put_contents(Cache::$debugfile,$line,FILE_APPEND);}		
		if(!is_dir($dir = Cache::$objectCacheDir."$group/")){mkdir($dir,0777,true);}
		$key = Cache::serializeKey($key);
		$ret=file_put_contents($dir.$key,serialize($value));
		if(Cache::$debug){$debug=debug_backtrace();$line=microtime(true).":E:";foreach($debug as $dbg){if(isset($dbg["function"])) $line.="F=".$dbg["function"].":A="; foreach($dbg["args"] as $k=>$v) $line.="$k=$v;";}$line.="\n";file_put_contents(Cache::$debugfile,$line,FILE_APPEND);}		
		return $ret;
	}
	public static function get($key,$group){
		if(!Cache::$objectCacheDir) return null;
		if(Cache::$debug){$debug=debug_backtrace();$line=microtime(true).":E:";foreach($debug as $dbg){if(isset($dbg["function"])) $line.="F=".$dbg["function"].":A="; foreach($dbg["args"] as $k=>$v) $line.="$k=$v;";}$line.="\n";file_put_contents(Cache::$debugfile,$line,FILE_APPEND);}		
		if(!is_dir($dir = Cache::$objectCacheDir."$group/")){mkdir($dir,0777,true);}
		$key = Cache::serializeKey($key);
		if(!is_file($dir.$key)) return false;
		$ret=unserialize(file_get_contents($dir.$key));
		if(Cache::$debug){$debug=debug_backtrace();$line=microtime(true).":E:";foreach($debug as $dbg){if(isset($dbg["function"])) $line.="F=".$dbg["function"].":A="; foreach($dbg["args"] as $k=>$v) $line.="$k=$v;";}$line.="\n";file_put_contents(Cache::$debugfile,$line,FILE_APPEND);}
		return $ret;
	}
	
	private static function delTree($dir) {
		$files = glob( $dir . '*', GLOB_MARK );
		foreach( $files as $file ){
			if( substr( $file, -1 ) == '/' )
				Cache::delTree( $file );
			else
				unlink( $file );
		}
		rmdir( $dir );
	} 
	public static function invalidate($group,$key=false){
		if(!Cache::$objectCacheDir) return true;
		if($key){
			if(is_dir($dir = Cache::$objectCacheDir."$group/")){
				$key = Cache::serializeKey($key);
				if(is_file($file=$dir.$key)){
					ulink($file);
				}
			}
		
		}else{
			if(is_dir($dir = Cache::$objectCacheDir."$group/")) Cache::delTree($dir);
		}
	}
	
	public static function save($item,$value,$timeout=0,$contenttype="",$useparams=false){
		if(!$useparams){
			if(($pos=strpos($item,"?")) !== false)$item = substr($item,0,$pos);
		}
		switch(Cache::$manager){
			case "DB":
				return \AsyncWeb\Cache\CacheDB::save($item,$value,$timeout,$contenttype="");
			break;
			case "File":
				return \AsyncWeb\Cache\CacheFile::save($item,$value,$timeout,$contenttype="");
			break;
		}
		return null;
	}
	public static function load($item,$timeout=0,$useparams=false){
		if(!$useparams){
			if(($pos=strpos($item,"?")) !== false)$item = substr($item,0,$pos);
		}
		switch(Cache::$manager){
			case "DB":
				return \AsyncWeb\Cache\CacheDB::load($item,$timeout);
			break;
			case "File":
				return \AsyncWeb\Cache\CacheFile::load($item,$timeout);
			break;
		}
		return null;
	}
	public static function clear(){
		switch(Cache::$manager){
			case "DB":
				return \AsyncWeb\Cache\CacheDB::clear();
			break;
			case "File":
				return \AsyncWeb\Cache\CacheFile::clear();
			break;
		}
		return null;
	}
	public static function optimize(){
		switch(Cache::$manager){
			case "DB":
				return \AsyncWeb\Cache\CacheDB::optimize();
			break;
			case "File":
				return \AsyncWeb\Cache\CacheFile::optimize();
			break;
		}
		return null;
	}
	public static function showIfCached($item,$useparams=false){
		if(!$useparams){
			if(($pos=strpos($item,"?")) !== false)$item = substr($item,0,$pos);
		}
		if(($o = Cache::load($item))!==null){ 
			\AsyncWeb\HTTP\Header::send("X-Cache-Manager: AsyncWeb");		
			if($o["contenttype"]){
				\AsyncWeb\HTTP\Header::send("Content-Type: ".strlen($o["contenttype"]));		
			}
			\AsyncWeb\HTTP\Header::send("Content-length: ".strlen($o["data"]));		
			//Header::send("ETag: ".$o["etag"]); // testujem ci to bude fungovat v ie
//			if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE")){
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
/*			}else{
				Header::send("Cache-control: private");
			}/**/
			
//			header("Last-Modified: " . gmdate("D, d M Y H:i:s",$o["od"]) . " GMT");
//			header("Expires: " . gmdate("D, d M Y H:i:s",$o["do"]) . " GMT");
			//var_dump("showing cachce");			exit;
			echo $o["data"];
			exit;
		  }

	}
}

?>