<?php
namespace AsyncWeb\Text\Translate;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;

// created using https://github.com/MicrosoftTranslator/HTTP-Code-Samples/blob/master/PHP/PHPAzureToken.php example

class Azure implements \AsyncWeb\Text\TranslatorInterface {
    public static $Use = true;
    public static $APP_ID = null;
    protected $translatech;
	protected $accessToken;
    public $CACHE_TABLE = "translations-azure";
	
	public function __construct(){
		$this->init();
	}
	public static function getToken()
	{
		$url = 'https://api.cognitive.microsoft.com/sts/v1.0/issueToken';
		$ch = curl_init();
		$data_string = json_encode('{body}');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data_string),
				'Ocp-Apim-Subscription-Key: ' . self::$APP_ID
			)
		);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$strResponse = curl_exec($ch);
		curl_close($ch);
		if(substr($str_response,0,1) == "{"){
			$json = json_decode($strResponse,true);
			if(is_array($json) && $json["statusCode"] == "401"){
				throw new \Exception($json["statusCode"].": ".$json["message"]);
			}
		}
		return $strResponse;
	}
	public function curlRequest($url)
	{
		$authHeader = "Authorization: Bearer ". $this->accessToken;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($authHeader, "Content-Type: text/xml"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, False);
		$curlResponse = curl_exec($ch);
		curl_close($ch);
		return $curlResponse;
	}
	
    public function init() {
		if(!$this->accessToken){
			$this->accessToken = self::getToken();
		}
    }
    public function translate($text, $from, $to, $usecache = true) {
        $id2 = md5($k = "A:$from-$to-$text");
        if ($usecache) {
            $row = DB::gr($this->CACHE_TABLE, $id2);
            if ($row && $row["translation"]) return $row["translation"];
        }

		$this->init();
		$params = "text=" . urlencode($text) . "&to=" . $to . "&from=" . $from . "&appId=Bearer+" . urlencode($this->accessToken);
		$translateUrl = "http://api.microsofttranslator.com/v2/Http.svc/Translate?$params";
		$curlResponse = $this->curlRequest($translateUrl);
		$xmlObj = simplexml_load_string($curlResponse);
		foreach ((array)$xmlObj[0] as $val) {
			$ret = $val;
		}
		DB::u($this->CACHE_TABLE, $id2, array("from" => $from, "to" => $to, "text" => $text, "translation" => $ret, "json" => $curlResponse));
		return $ret;
    }
	public static $instance = null;
	public static function Instance(){
		if(!self::$instance) self::$instance = new \AsyncWeb\Text\Translate\Azure();
		return self::$instance;
	}
}
