<?php
namespace AsyncWeb\Connectors;

class MyCurl{
	public static function divideHeaders(&$text,&$headers,$followloc=false){
		$continue = false;
		while(($pos=strpos($text,"\n"))!==false){
			$pos2 = $pos+=strlen("\n");
			$move = substr($text,0,$pos2);
			if(trim($move)=="HTTP/1.1 100 Continue"){
					$continue = true;
			}
			if(trim($move)=="HTTP/1.1 302 Found"){
				$continue = true;
			}
			
			if(trim($move)=="HTTP/1.1 302 Moved Temporarily"){
				$continue = true;
			}
			
			$headers .= $move;
			$text = substr($text,$pos2);
			if(bin2hex($move) == "0d0a" || bin2hex($move) == "0a"){
					if($continue) {$continue=false;continue;}
					$headers = trim($headers);
					return;
			}
		}
		$headers = trim($headers);
	}
	public static function devideHeaders(&$text,&$headers){
		return MyCurl::divideHeaders($text,$headers);
	}
	
	
	public static function http_build_query($a,$keyName='',$c=0,$debug=false)
	{
		// from php doc: http://php.net/manual/en/function.http-build-query.php
		// 2017 01 24
		// modified to work properly

		if (!is_array($a)) return false;
		$AllKeysAreNumeric = true;
		foreach ((array)$a as $k=>$v){
			if(!is_int($k)) {
				$AllKeysAreNumeric = false;
				break;
			} 
		}		
		foreach ((array)$a as $k=>$v)
		{
			$k = urlencode($k);
			if ($c)
			{
				if( $AllKeysAreNumeric )
					$k=$keyName."[]";
				else
					$k=$keyName."[".$k."]";
			}
			else
			{   if (is_int($k))
					$k=$keyName.$k; 
			}

			if (is_array($v)||is_object($v))
			{
				$ret = self::http_build_query($v,$k,1,$debug);
				if($ret!== null)
					$r[]=$ret;
					continue;
			}
			if(is_bool($v)){
				if($v){
					$r[]=$k."=1";
				}else{					
					$r[]=$k."=0";
				}
				continue;
			}
			$r[]=$k."=".urlencode($v);
		}
		if($debug){echo "mycurl:";echo print_r($r,true);echo "\n";}
		return implode("&",$r);
	}
}
