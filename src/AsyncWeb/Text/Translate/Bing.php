<?php
namespace AsyncWeb\Text\Translate;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;
class Bing implements \AsyncWeb\Text\TranslatorInterface {
    public $APP_ID = null;
    protected $translatech;
    public function init() {
        $headers = array("Keep-Alive: 300", "Connection: keep-alive",
        //"Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
        );
        $this->translatech = curl_init("http://www.bing.com/translator/");
        $options = array(CURLOPT_HEADER => 0, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 10, CURLOPT_USERAGENT => "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)", CURLOPT_ENCODING => "gzip,deflate", CURLOPT_COOKIEFILE => "cookie.txt", CURLOPT_COOKIEJAR => "cookie.txt", CURLOPT_SSL_VERIFYPEER => false,);
        curl_setopt_array($this->translatech, $options);
        curl_setopt($this->translatech, CURLOPT_HTTPHEADER, $headers);
    }
    public function translate($text, $from, $to, $usecache = true) {
        $text = str_replace('"', "'", $text);
        $text = str_replace('[', "", $text);
        $text = str_replace(']', "", $text);
        if (strlen($text) > 250) return $text;
        $id2 = md5($from . $to . $text);
        if ($usecache) {
            $row = DB::qbr("translations-bing", array("where" => array("id2" => $id2), "cols" => array("translation")));
            if ($row) return $row["translation"];
        }
        if (!$this->translatech) {
            $this->init();
        }
        if ($this->APP_ID) {
            $appId = $this->APP_ID;
        } else {
            $html = Page::get("http://www.bing.com/translator/");
            //var_dump($text);exit;
            $find = "AjaxApiAppId = '";
            if ($pos = strpos($html, $find)) {
                $pos+= strlen($find);
                $pos2 = strpos($html, "'", $pos + 1);
                if ($pos2) {
                    $this->APP_ID = $appId = substr($html, $pos, $pos2 - $pos);
                    //echo "setting bing appid: $appId\n";
                    
                }
            }
        }
        curl_setopt($this->translatech, CURLOPT_URL, 'http://api.microsofttranslator.com/v2/ajax.svc/TranslateArray?appId=' . $appId . '&texts=["' . urlencode($text) . '"]&from="' . $from . '"&to="' . $to . '"&oncomplete=_mstc2&onerror=_mste2&loc=en&ctr=CzechRepublic&rgp=cce376b');
        $data = curl_exec($this->translatech);
        if (($pos = strpos($data, $t = 'TranslatedText":"')) !== false) {
            $start = $pos + strlen($t);
            $pos2 = strpos($data, '"', $start);
            if ($pos2 > $start) {
                $l = $pos2 - $start;
            } else {
                $l = 0;
            }
            if ($l > 0) {
                $ret = substr($data, $start, $l);
                if ($ret) DB::u("translations-bing", $id2, array("from" => $from, "to" => $to, "text" => $text, "translation" => $ret));
                return $ret;
            }
        }
        return $text;
    }
}
