<?php

namespace AsyncWeb\Api\REST;

class Client{
	public static $curlOptions = array(
		CURLOPT_HEADER => false,
		CURLOPT_RETURNTRANSFER => true,
	);
	public static function Call($url,$variables=array(),$type=null){
		$curl = curl_init($url);
		curl_setopt_array($curl, self::$curlOptions);
		if($variables){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($variables));

		}else{
			switch($type){
				case "POST":
				case "GET": 
				case "PUT":
				case "DELETE":
					curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $type);
				break;
			}
		}
		
		$response = curl_exec($curl);
		if($resp = @json_decode($response,true)){
			if(isset($resp["Status"]) && $resp["Status"] == "error"){
				throw new \Exception($resp["Text"]);
			}elseif(isset($resp["Result"])){
				return $resp["Result"];
			}else{
				throw new \Exception("Server did not responded according to CEB REST Standards");
			}
		}else{
			throw new \Exception("Server has not provided any reasonable response");
		}
	}
	/**
		Creates HashString according to CEB REST Standard
		
		array("1"=>"2","3"=>array("4"=>"5"),"6"=>"7") will convert to 
		
		1=2&3=4=5&6=7
	
	*/
	
	public static function MakeHashString($variables){
		$toHash = "";
		foreach($variables as $k=>$v){
			if(!$v) continue;
			if($k ===  "CRC") continue;
			if($v === "__AW__VALUE_NOT_CHANGED") continue;
			
				
			if(is_array($v)){
				if(!count($v)) continue;
				$toHash .= $k."=".Client::MakeHashString($v)."&";
			}else{
				$toHash .= $k."=".$v."&";
			}
		}
		return $toHash = rtrim($toHash,"&");
	}
	public static function MakeCRC($variables=array(),$password){
		
		$toHash = Client::MakeHashString($variables);
		$toHash.= "&ApiSecret=".$password;
		return hash("sha512",$toHash);
	}
}