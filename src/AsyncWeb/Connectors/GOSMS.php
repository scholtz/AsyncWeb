<?php
namespace AsyncWeb\Connectors;

class GOSMS
{
	public static $CLIENT_ID = '';
	public static $CLIENT_SECRET = '';
	public static $CHANNEL = '';
	protected static $token;
	public static function getToken($renew=false){
		if(!$renew) if(GOSMS::$token) return GOSMS::$token;
		$data  =\AsyncWeb\Connectors\Page::get(
			"https://app.gosms.cz/oauth/v2/token",
			array(
				"client_id"=>GOSMS::$CLIENT_ID,
				"client_secret"=>GOSMS::$CLIENT_SECRET,
				"grant_type"=>"client_credentials"),false);
		$json = json_decode($data,true);
		if(isset($json["access_token"])){
			return GOSMS::$token = $json["access_token"];
		}
		if(isset($json["error"])){
			throw new \Exception($json["error"]);
		}
		throw new \Exception("Unable to parse access token");
	}
	static public function getRemainingCredit(){
		$token  = GOSMS::getToken();
		
		$data = \AsyncWeb\Connectors\Page::get("https://app.gosms.cz/api/v1/","",false,array(
			CURLOPT_HTTPHEADER=>array(
			 "Authorization"=>"Bearer $token"
			),
		));
		$json = json_decode($data,true);
		if(!$json){
			throw new \Exception("Unable to parse response");
		}
		if(isset($json["error"])){
			throw new \Exception($json["error"]);
		}
		if(isset($json["detail"])){
			throw new \Exception($json["detail"].":".implode(",",$json["errors"]));
		}
		return $data["currentCredit"];
	}
	static public function sendMessage($recipient, $message,$sendAt=null) {
		
		$token  = GOSMS::getToken();
		
		$send = array(
				"message"=>$message,
				"recipients"=>$recipient,
				"channel"=>GOSMS::$CHANNEL,
				);
		if($sendAt){$send["expectedSendStart"] = date("c",$sandAt);}
				
		$sendMsg = json_encode($send);		
		echo $sendMsg;
		$data = \AsyncWeb\Connectors\Page::get("https://app.gosms.cz/api/v1/messages",$sendMsg,false,array(
			CURLOPT_HTTPHEADER=>array(
			 "Authorization"=>"Bearer $token"
			),
		));
		$json = json_decode($data,true);
		if(!$json){
			throw new \Exception("Unable to parse response");
		}
		if(isset($json["error"])){
			throw new \Exception($json["error"]);
		}
		if(isset($json["detail"])){
			throw new \Exception($json["detail"].":".implode(",",$json["errors"]));
		}
		return $data;		
	}
}