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
}

?>