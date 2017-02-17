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
			curl_setopt($curl, CURLOPT_POSTFIELDS,  \AsyncWeb\Connectors\MyCurl::http_build_query($variables));
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
		$info = curl_getinfo($curl);
		
		if($info["http_code"] != "200"){
			throw new \Exception(\AsyncWeb\System\Language::get("API Server returned http status code %code%",array("%code"=>$info["http_code"])));
		}
		
		if(!$response){
			throw new \Exception(\AsyncWeb\System\Language::get("API Server returned empty result"));
		}
		
		if($resp = @json_decode($response,true)){
			if(isset($resp["Status"]) && $resp["Status"] == "error"){
				throw new \Exception($resp["Text"]);
			}elseif(isset($resp["Result"])){
				return $resp["Result"];
			}else{
				throw new \Exception(\AsyncWeb\System\Language::get("API Server did not responded according to AW REST Standards"));
			}
		}else{
			throw new \Exception(\AsyncWeb\System\Language::get("API Server has not provided any reasonable response"));
		}
	}
	/**
		Creates HashString according to AW REST Standard
		
		array("1"=>"2","3"=>array("4"=>"5"),"6"=>"7") will convert to 
		
		1=2&3=4=5&6=7
	
	*/
	
	public static function MakeHashString($variables){
		$toHash = "";
		$ucfirst = true;
		if(isset($variables["col"]) && isset($variables["op"])){
			$ucfirst = false;
		}
		foreach($variables as $k=>$v){
			if($v === null) continue;
			if($v === "") continue;
			if($v === false) $v = "0";
			if($k ===  "CRC") continue;
			if($v === "__AW__VALUE_NOT_CHANGED") continue;
			if($ucfirst) $k = ucfirst($k);
				
			if(is_array($v)){
				if(!count($v)) continue;
				$str = Client::MakeHashString($v);
				if($str){
					$toHash .= $k."=".$str."&";
				}
			}else{
				$toHash .= $k."=".$v."&";
			}
		}
		return $toHash = rtrim($toHash,"&");
	}
	public static function MakeCRC($variables=array(),$password){
		
		$toHash = Client::MakeHashString($variables);
		$toHash.= "&ApiSecret=".$password;
		//throw new \Exception($toHash);
		return hash("sha512",$toHash);
	}
}