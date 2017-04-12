<?php
namespace AsyncWeb\Text\Translate;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
class Google implements \AsyncWeb\Text\TranslatorInterface {
    public static $APP_ID = null;
    public $CUSTOM_APP_ID = null;
    public $CACHE_TABLE = "translations-googleapi";
    public function translate($text, $from, $to, $usecache = true) {
        $id2 = md5("G:$from-$to-$text");
        if ($usecache) {
            $row = DB::gr($this->CACHE_TABLE, $id2);
            if ($row && $row["translation"]) return $row["translation"];
        }
        $appid = false;
        if (Google::$APP_ID) $appid = Google::$APP_ID;
        if ($this->CUSTOM_APP_ID) $appid = $this->CUSTOM_APP_ID;
        if (!$appid) throw new Exception("Unable to translate because you did not set the APP ID for google translator. Please see your Google Developer Console.");
        $page = Page::get($path = "https://www.googleapis.com/language/translate/v2?q=" . urlencode($text) . "&target=" . $to . "&source=" . $from . "&fields=translations%2FtranslatedText&key=" . urlencode($appid), "", false);
        $json = json_decode($page, true);
        $ret = $json["data"]["translations"][0]["translatedText"];
        if ($ret) DB::u($this->CACHE_TABLE, $id2, array("from" => $from, "to" => $to, "text" => $text, "translation" => $ret, "json" => $page));
        return $ret;
    }
}
