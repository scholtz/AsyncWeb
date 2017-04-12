<?php
namespace AsyncWeb\System;
class Language {
    /**
     Change your default lang in your settings file:
     \AsyncWeb\System\Language::$DEFAULT_LANGUAGE = "de-DE";
     */
    public static $DEFAULT_LANGUAGE = "en-US";
    public static $SUPPORTED_LANGUAGES = array("en-US" => "");
    //public static $LANG_DIR = "../dictionary/";
    protected static $LANG_DIRS = array("../dictionary/", "../vendor/scholtz/async-web/lang/");
    protected static $lang = "en-US";
    protected static $dictionary = array();
    protected static $reversedictionary = array();
    protected static $id2toid3 = array();
    protected static $id3toid2 = array();
    public static $SupressNoDictionaryDataMsg = true;
    public static function setLangFromSession($sess) {
        $lang = Sess::getLang($sess);
        return Language::setLang($lang);
    }
    public static function setLang($lang = false, $useLang = true) {
        if (!$lang) {
            // detect language
            $lang = Language::getDefaultLang();
        }
        if (!isset(Language::$SUPPORTED_LANGUAGES[$lang])) {
            $lang = Language::$DEFAULT_LANGUAGE;
        }
        Language::$lang = $lang;
    }
    public static function getDomain() {
        return $_SERVER["HTTP_HOST"];
    }
    public static function getDefaultLang() {
        foreach (Language::$SUPPORTED_LANGUAGES as $lang => $arr) {
            if ($arr == Language::getDomain() || (isset($arr["domain"]) && $arr["domain"] == Language::getDomain())) {
                return $lang;
            }
        }
        $langs = Language::parseBrowserLang();
        foreach ($langs as $arr) {
            foreach (Language::$SUPPORTED_LANGUAGES as $lang => $d) {
                if ($arr["code"] == $lang) return $lang;
                if ($arr["code"] == "cs-CZ") return "sk-SK";
            }
        }
        foreach (Language::$SUPPORTED_LANGUAGES as $lang => $d) {
            return $lang;
        }
        return Language::$DEFAULT_LANGUAGE;
    }
    public static function getLang() {
        return Language::$lang;
    }
    public static $gettingDictionary = false;
    public static function set($origterm, $value = null, $lang = false) {
        if ($value === null) {
            $value = $origterm;
            $origterm = "";
        }
        $term = $origterm;
        if (!$term) $term = "L_" . substr(md5(uniqid()), 0, 30);
        if (!$lang) $lang = Language::$lang;
        $row = \AsyncWeb\DB\DB::gr("dictionary", array("key" => $term, "lang" => $lang));
        $id2 = md5(uniqid());
        if ($row) {
            $id2 = $row["id2"];
        }
        $ret = \AsyncWeb\DB\DB::u("dictionary", $id2, array("key" => $term, "value" => $value, "lang" => $lang), array("cols" => array("value" => array("type" => "text"))));
        Language::makeCache();
        if (!$origterm && $ret) return $term;
        return $ret;
    }
    protected static function init($lang) {
        if (!$lang) $lang = Language::getLang();
        if (!isset(Language::$dictionary[$lang])) {
            if (Language::$gettingDictionary) return;
            Language::$gettingDictionary = true;
            try {
                if ($L = \AsyncWeb\Cache\Cache::get("lang_" . $lang, "lang")) {
                    //echo "got from cache\n";
                    Language::$dictionary[$lang] = $L;
                    $id2toid3 = \AsyncWeb\Cache\Cache::get("2to3", "lang");
                    if ($id2toid3) {
                        Language::$id2toid3 = array_merge(Language::$id2toid3, $id2toid3);
                        Language::$id3toid2 = array_merge(Language::$id3toid2, \AsyncWeb\Cache\Cache::get("3to2", "lang"));
                    }
                    Language::$reversedictionary[$lang] = \AsyncWeb\Cache\Cache::get("revl_" . $lang, "lang");
                } else {
                    Language::$dictionary[$lang] = $L = Language::build($lang);
                    Language::makeCache($lang);
                }
            }
            catch(\Exception $exc) {
            }
            Language::$gettingDictionary = false;
        }
    }
    public static function is_set($term, $lang = false) {
        if (!$lang) $lang = Language::getLang();
        Language::init($lang);
        return isset(Language::$dictionary[$lang][$term]);
    }
    public static function getReverse($term = false, $useids = false, $lang = false, $dbg = false) {
        if (!$lang) $lang = Language::getLang();
        Language::init($lang);
        if (!isset(Language::$reversedictionary[$lang][$term])) return array();
        if ($useids) {
            $ret = array();
            foreach (Language::$reversedictionary[$lang][$term] as $v) {
                if (isset(Language::$id3toid2[$v])) {
                    $ret[] = Language::$id3toid2[$v];
                } else {
                    $ret[] = $v;
                }
            }
            return $ret;
        }
        return Language::$reversedictionary[$lang][$term];
    }
    public static function get($term = false, $params = array(), $inLang = false, $dbg = false) {
        if ($params && !is_array($params)) {
            $params = array("%p1%" => $params);
        }
        $dbgi = 0;
        if ($dbg) {
            echo Timer1::show() . "Language:get:$term:" . ($dbgi++) . ":\n";
        }
        if ($term === "0" || $term === 0) return $term;
        if ($term === false) return Language::getLang();
        if (!$term) return "";
        if ($dbg) {
            echo Timer1::show() . "Language:get:$term:" . ($dbgi++) . ":\n";
        }
        if (is_array($term)) {
            echo "Term in Lang is array!\n";
            exit;
        };
        if ($dbg) {
            echo Timer1::show() . "Language:get:$term:" . ($dbgi++) . ":\n";
        }
        $lang = Language::$lang;
        if ($inLang && isset(Language::$SUPPORTED_LANGUAGES[$inLang])) $lang = $inLang;
        if ($dbg) {
            echo Timer1::show() . "Language:get:$term:" . ($dbgi++) . ":\n";
        }
        if (!isset(Language::$dictionary[$lang])) {
            if (\AsyncWeb\DB\DB::$CONNECTING) {
                return $term;
            }
            Language::init($lang);
            if (isset(Language::$dictionary[$lang])) {
                $L = Language::$dictionary[$lang];
            } else {
                $L = array();
            }
        } else {
            $L = Language::$dictionary[$lang];
        }
        if (!is_array($L)) {
            $L = Language::getL($lang);
        }
        if (!is_array($L)) {
            return $L;
        }
        if ($dbg) {
            echo Timer1::show() . "Language:get:$term:" . ($dbgi++) . ":\n";
        }
        if (array_key_exists($term, $L)) {
            $ret = Language::fillParams($L[$term], $params);
            Language::$gettingDictionary = false;
            if ($dbg) {
                echo Timer1::show() . "Language:get:array_key_exists:$term:" . ($dbgi++) . ":\n";
            }
            if (strlen($ret) == 32 && strpos($ret, "__")) {
                // resetni cache
                Language::makeCache($lang, true);
                $L = Language::$dictionary[$lang];
                $ret = Language::fillParams($L[$term], $params);
            }
            return $ret;
        } else {
            /*if($t = Language::db_dict($term,$lang)){
            $ret=  Language::fillParams($t,$params);
            Language::$gettingDictionary = false;
            if($dbg){echo Timer1::show()."Language:get:db_dict:$term:".($dbgi++).":\n";}
            return $ret;
            }/**/
            if ($dbg) {
                echo Timer1::show() . "Language:get:$term:" . ($dbgi++) . ":\n";
            }
            $ret = Language::fillParams($term, $params);
            Language::$gettingDictionary = false;
            if ($dbg) {
                echo Timer1::show() . "Language:get:retl:$term:" . ($dbgi++) . ":\n";
            }
            return $ret;
            if ($dbg) {
                echo Timer1::show() . "Language:get:nie je zadany?:$term:" . ($dbgi++) . ":\n";
            }
            return "?$term?1?";
        }
    }
    public static $USE_PHP_LANG_FILES = true;
    public static $USE_CSV_LANG_FILES = true;
    public static $USE_DB_DICTIONARY = true;
    public static function registerLangPath($path) {
        Language::$LANG_DIRS[] = $path;
    }
    protected static function build($lang, $D = false) {
        $L = array();
        if (!$D && $lang != Language::$DEFAULT_LANGUAGE) {
            if (isset(Language::$SUPPORTED_LANGUAGES[Language::$DEFAULT_LANGUAGE])) {
                // we want to make sure that at least in english each dictionary item is available
                $L = Language::build(Language::$DEFAULT_LANGUAGE);
            }
        }
        $dirs = Language::$LANG_DIRS;
        if ($D) $dirs = array($D);
        foreach ($dirs as $dir) {
            $dir = rtrim($dir, "/");
            if (!is_dir($dir)) continue;
            if (Language::$USE_CSV_LANG_FILES) {
                if (file_exists($p = $dir . "/" . str_replace("-", "_", $lang) . ".csv")) {
                    $L = array_merge($L, Language::buildFromCSV($p));
                }
            }
            if (!$D) {
                $dir = $dir . "/" . $lang;
            }
            if (is_dir($dir)) foreach (scandir($dir) as $file) {
                if ($file == "." || $file == "..") continue;
                $path = $dir . "/" . $file;
                if (is_dir($path)) {
                    $L = array_merge($L, Language::build($lang, $path));
                } else {
                    switch (pathinfo($file, PATHINFO_EXTENSION)) {
                        case "php":
                            if (Language::$USE_PHP_LANG_FILES) {
                                $L = array_merge($L, Language::buildFromPHP($path));
                            }
                        break;
                        case "csv":
                            if (Language::$USE_CSV_LANG_FILES) {
                                $L = array_merge($L, Language::buildFromCSV($path));
                            }
                        break;
                    }
                }
            }
        }
        if (!$D) {
            if (!$L) {
                if (!Language::$SupressNoDictionaryDataMsg) {
                    throw new \AsyncWeb\Exceptions\FatalException("Unable to find any dictionary item!");
                }
            }
            if (Language::$USE_DB_DICTIONARY) {
                $res = \AsyncWeb\DB\DB::g("dictionary", array("lang" => $lang));
                while ($row = \AsyncWeb\DB\DB::f($res)) {
                    $L[$row["key"]] = $row["value"];
                    Language::$id2toid3[$row["id2"]] = $row["key"];
                    Language::$id3toid2[$row["key"]] = $row["id2"];
                }
                $res = \AsyncWeb\DB\DB::g("dictionary");
                while ($row = \AsyncWeb\DB\DB::f($res)) {
                    if (!isset($L[$row["key"]])) {
                        $L[$row["key"]] = $row["value"];
                        Language::$id2toid3[$row["id2"]] = $row["key"];
                        Language::$id3toid2[$row["key"]] = $row["id2"];
                    }
                }
            }
            Language::$reversedictionary[$lang] = array();
            foreach ($L as $k => $v) {
                Language::$reversedictionary[$lang][$v][] = $k;
            }
        }
        return $L;
    }
    protected static function buildFromCSV($file) {
        $L = array();
        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000000, ",")) !== FALSE) {
                if (count($data) < 2) continue;
                $L[$data[0]] = $data[1];
            }
            fclose($handle);
        }
        return $L;
    }
    protected static function buildFromPHP($file) {
        $L = array();
        include $file;
        return $L;
    }
    /*
    protected static function getL($lang){
    //var_dump($lang);
    $L = array();
    if(file_exists(Language::$LANG_DIR."/sk/dictionary.php")){
    include(Language::$LANG_DIR."/sk/dictionary.php");
    }
    
    if(file_exists($f=Language::$LANG_DIR."/$lang/dictionary.php")){
    include(Language::$LANG_DIR."/$lang/dictionary.php");
    
    }else{
    if(file_exists(Language::$LANG_DIR."/sk/dictionary.php")){
    include(Language::$LANG_DIR."/sk/dictionary.php");
    }else{
    // php file dictionary is not used
    //throw new \AsyncWeb\Exceptions\FatalException("Unable to find any dictionary!");
    }
    }
    if(!$L){
    throw new \AsyncWeb\Exceptions\FatalException("Unable to find any dictionary item!");
    }
    $res = \AsyncWeb\DB\DB::g("dictionary",array("lang"=>$lang));
    while($row = \AsyncWeb\DB\DB::f($res)){
    $L[$row["key"]] = $row["value"];
    Language::$id2toid3[$row["id2"]] = $row["key"];
    Language::$id3toid2[$row["key"]] = $row["id2"];
    }
    $res = \AsyncWeb\DB\DB::g("dictionary");
    while($row = \AsyncWeb\DB\DB::f($res)){
    if(!isset($L[$row["key"]])){
    $L[$row["key"]] = $row["value"];
    Language::$id2toid3[$row["id2"]] = $row["key"];
    Language::$id3toid2[$row["key"]] = $row["id2"];
    }
    }
    Language::$reversedictionary[$lang] = array();
    foreach($L as $k=>$v){
    Language::$reversedictionary[$lang][$v][] = $k;
    }
    return $L;
    }
    /**/
    public static function makeCache($lang = null, $reset = false) {
        if (!$lang) {
            foreach (Language::$SUPPORTED_LANGUAGES as $lang => $domain) {
                if (!$lang) continue;
                Language::makeCache($lang);
            }
            return;
        }
        if ($reset || !isset(Language::$dictionary[$lang])) {
            Language::$dictionary[$lang] = Language::build($lang);
        }
        $res = \AsyncWeb\DB\DB::g("dictionary", array("lang" => $lang));
        while ($row = \AsyncWeb\DB\DB::f($res)) {
            Language::$dictionary[$lang][$row["key"]] = $row["value"];
        }
        Language::$reversedictionary[$lang] = array();
        foreach (Language::$dictionary[$lang] as $k => $v) {
            Language::$reversedictionary[$lang][$v][] = $k;
        }
        \AsyncWeb\Cache\Cache::set("lang_" . $lang, "lang", Language::$dictionary[$lang]);
        \AsyncWeb\Cache\Cache::set("revl_" . $lang, "lang", Language::$reversedictionary[$lang]);
        \AsyncWeb\Cache\Cache::set("2to3", "lang", Language::$id2toid3);
        \AsyncWeb\Cache\Cache::set("3to2", "lang", Language::$id3toid2);
    }
    public static function fillParams($term, $params) {
        foreach ($params as $key => $value) {
            /*if(is_array($key)){var_dump($key);}
            if(is_array($value)){var_dump($value);}
            if(is_array($term)){var_dump($term);}/**/
            $term = str_replace($key, $value, $term);
        }
        return $term;
    }
    public static function db_dict($term, $inLang = false) {
        $lang = Language::$lang;
        if ($inLang && isset(Language::$SUPPORTED_LANGUAGES[$inLang])) $lang = $inLang;
        $row = \AsyncWeb\DB\DB::gr("dictionary", array("key" => $term, "lang" => $lang)); //md5($term."-".Language::$lang));
        if ($row) {
            return $row["value"];
        }
        $row = \AsyncWeb\DB\DB::gr("dictionary", array("key" => $term, "lang" => Language::$lang)); //md5($term."-".Language::$lang));
        if ($row) {
            return $row["value"];
        }
        if (Language::$lang != "en") {
            $row = \AsyncWeb\DB\DB::gr("dictionary", array("key" => $term, "lang" => "en")); //md5($term."-".Language::$lang));
            if ($row) {
                return $row["value"];
            }
        }
        if (Language::$lang != "sk") {
            $row = \AsyncWeb\DB\DB::gr("dictionary", array("key" => $term, "lang" => "sk")); //md5($term."-".Language::$lang));
            if ($row) {
                return $row["value"];
            }
        }
        $row = \AsyncWeb\DB\DB::gr("dictionary", array("key" => $term)); //md5($term."-".Language::$lang));
        if ($row) {
            return $row["value"];
        }
        return false;
    }
    public static function db_dict_find_by_value($value, $lang = false, $exact = true) {
        if (!$lang) $lang = Language::$lang;
        if ($exact) {
            $where = array(array("col" => "value", "op" => "like", "value" => "%$value%"), array("col" => "lang", "op" => "eq", "value" => $lang));
        } else {
            $where = array("value" => $value, "lang" => $lang);
        }
        $res = \AsyncWeb\DB\DB::g("dictionary", $where);
        $ret = array();
        if ($exact) return $ret;
        while ($row = \AsyncWeb\DB\DB::f($res)) {
            $ret[] = $row["key"];
        }
        if ($ret) return $ret;
        $res = \AsyncWeb\DB\DB::g("dictionary", array("value" => $value));
        while ($row = \AsyncWeb\DB\DB::f($res)) {
            $ret[] = $row["key"];
        }
        return $ret;
    }
    public static function parseBrowserLang() {
        $langsStr = @$_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $langs = explode(',', $langsStr);
        $langa = array();
        foreach ($langs as $lang) {
            if (preg_match('#^([a-z]{1,2})(-([a-z0-9]+))?(;q=([0-9\.]+))?#i', $lang, $found)) {
                $code = htmlentities($found[1], ENT_QUOTES);
                $coef = sprintf('%3.1f', $found[5] ? $found[5] : '1');
                $key = $coef . '-' . $code;
                if (strpos($code, "-") === false) {
                    $code = \AsyncWeb\Text\ConvertLangToLangCountry::convert($code);
                }
                $langa[$key] = array('code' => $code, 'coef' => $coef);
            }
        }
        krsort($langa);
        return $langa;
    }
    public static function checkDomain() {
        foreach (Language::$SUPPORTED_LANGUAGES as $lang => $arr) {
            if (is_array($arr) && $arr["domain"] == self::getDomain()) {
                return true;
            } else if (!is_array($arr) && $arr == self::getDomain()) {
                return true;
            }
        }
        $deflang = self::getDefaultLang();
        if (isset(Language::$SUPPORTED_LANGUAGES[$deflang])) {
            if (is_array(Language::$SUPPORTED_LANGUAGES[$deflang])) {
                $defaultDomain = Language::$SUPPORTED_LANGUAGES[$deflang]["domain"];
            } else {
                $defaultDomain = Language::$SUPPORTED_LANGUAGES[$deflang];
            }
        } else {
            $defaultDomainarr = array_pop(Language::$SUPPORTED_LANGUAGES);
            if (is_array($defaultDomainarr)) {
                $defaultDomain = $defaultDomainarr["domain"];
            } else {
                $defaultDomain = $defaultDomainarr;
            }
        }
        if ($defaultDomain) {
            if (\AsyncWeb\System\System::isCommandLineInterface()) {
                if (!isset($_SERVER["HTTP_HOST"]) || !$_SERVER["HTTP_HOST"]) {
                    $_SERVER["HTTP_HOST"] = $defaultDomain;
                }
                return true;
            }
            header("Location: http://" . $defaultDomain);
            exit;
        }
        return false;
    }
}
