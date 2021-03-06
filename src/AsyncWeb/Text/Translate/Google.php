<?php
namespace AsyncWeb\Text\Translate;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
class Google implements \AsyncWeb\Text\TranslatorInterface {
    public static $Use = true;
    public static $APP_ID = null;
    public $CUSTOM_APP_ID = null;
    public $CACHE_TABLE = "translations-googleapi";
    public function translate($text, $from, $to, $usecache = true) {
        $id2 = md5($k = "G:$from-$to-$text");
        if ($usecache) {
            $row = DB::gr($this->CACHE_TABLE, $id2);
            if ($row && $row["translation"]) {
				$ret =  $row["translation"];
				
				for($i = 1;$i < 10; $i++){
					if(strpos($text,"%$i") !== false){
						$ret = str_replace("% $i"," %$i",$ret);
					}
				}

				return $ret;
			}
        }
        $appid = false;
        if (Google::$APP_ID) $appid = Google::$APP_ID;
        if ($this->CUSTOM_APP_ID) $appid = $this->CUSTOM_APP_ID;
        if (!$appid) throw new Exception("Unable to translate because you did not set the APP ID for google translator. Please see your Google Developer Console.");
		
		if(!class_exists("\\Google\\Cloud\\Translate\\TranslateClient")){
			throw new \Exception("google translate client is not installed. Please run composer require google/cloud-translate");
		}
		$translate = new \Google\Cloud\Translate\TranslateClient(["projectId" => Google::$APP_ID]);
		try{
			$translation = $translate->translate($text, [
				'source' => $from,
				'target' => $to,
			]);
			$ret = $translation['text'];
			$ret = html_entity_decode($ret);
			
			for($i = 1;$i < 10; $i++){
				if(strpos($text,"%$i") !== false){
					$ret = str_replace("% $i"," %$i",$ret);
				}
			}
			
			/**/
			
			DB::u($this->CACHE_TABLE, $id2, array("from" => $from, "to" => $to, "text" => $text, "translation" => $ret, "json" => json_encode($translation)));
			return $ret;
		}catch(\Exception $exc){
			throw $exc;
		}
    }
	public static $instance = null;
	public static function Instance(){
		if(!self::$instance) self::$instance = new \AsyncWeb\Text\Translate\Google();
		return self::$instance;
	}
}
