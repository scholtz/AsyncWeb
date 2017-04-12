<?php
namespace AsyncWeb\System;
/*
 
 this class provides basic functionality for path creation
 Usage: Path::make(array("var1"=>"value"));
 
*/
use AsyncWeb\Frontend\URLParser;
class Path {
    public static $AsyncWeb = true;
    public static function make_path($type, $name = "", $lang = null) {
        if (!$lang) $lang = Lang::getLang();
        if (Config::$load) {
            $items = Config::getInstance()->query("//typ_odkazov");
        } else {
            $items = false;
        }
        if ($items && $items->item(0)) {
            $typ = $items->item(0)->textContent;
        } else {
            $typ = "normal";
        }
        static $multijazycny_system = false;
        static $pridaj = "";
        if ($typ != "normal") {
            if (Config::$load) {
                $items = Config::getInstance()->query("//obecne/multijazycny_system");
                if ($items && $items->item(0)) {
                    $multijazycny_system = (boolean)$items->item(0)->textContent;
                }
            } else {
                $multijazycny_system = true;
            }
            $pridaj = Lang::getText("article") . "/";
        }
        if ($typ == "normal") {
            switch ($type) {
                case 'category':
                    return "/category.php?go=" . $name;
                break;
                case 'article':
                    return "/clanok.php?clanok=" . $name;
                break;
                case 'xml':
                    return "/xml.php?clanok=" . $name;
                break;
                case 'text':
                    return "/text.php?clanok=" . $name;
                break;
                case 'discuss':
                    return "/komentare.php?clanok=" . $name;
                break;
                case 'change_lang':
                    return "?lang=" . $name;
                break;
                case 'RSS':
                    return "/rss.php?do=" . $name;
                break;
            }
        } else {
            //   $lang = Lang::getLang();
            //$url = Config::getInstance()->getValue("/konfiguracia/obecne/url");
            if ($_SERVER['SERVER_PORT'] == 443) {
                $url = "https://" . $_SERVER['HTTP_HOST'];
            } else {
                $url = "http://" . $_SERVER['HTTP_HOST'];
            }
            if (substr($url, -1) == "/") {
                $url = substr($url, 0, strlen($url) - 1);
            }
            switch ($type) {
                case 'category':
                    if ($multijazycny_system) {
                        return "$url/$lang/" . Lang::getText("category", null, null, $lang) . "/$name/";
                    } else {
                        return "$url/" . Lang::getText("category", null, null, $lang) . "/$name/";
                    }
                break;
                case 'article':
                    if ($multijazycny_system) {
                        return "$url/$lang/${pridaj}${name}/";
                    } else {
                        return "$url/${pridaj}${name}/";
                    }
                break;
                case 'xml':
                    if ($multijazycny_system) {
                        return "$url/$lang/${pridaj}${name}/xml/";
                    } else {
                        return "$url/${pridaj}${name}/xml/";
                    }
                break;
                case 'text':
                    if ($multijazycny_system) {
                        return "$url/$lang/${pridaj}${name}/text/";
                    } else {
                        return "$url/${pridaj}${name}/text/";
                    }
                break;
                case 'discuss':
                    if ($multijazycny_system) {
                        return "$url/$lang/${pridaj}${name}/" . Lang::getText("diskusia", null, null, $lang) . "/";
                    } else {
                        return "$url/${pridaj}${name}/" . Lang::getText("diskusia", null, null, $lang) . "/";
                    }
                break;
                case 'change_lang':
                    if (strpos($_SERVER["REQUEST_URI"], "?") === false) {
                        return "?lang=$name";
                    } else {
                        return $_SERVER["REQUEST_URI"] . "&amp;lang=$name";
                    }
                    //   	 return "?lang=".$name;
                    
                break;
                case 'RSS':
                    if ($multijazycny_system) {
                        if ($name) return "$url/$lang/rss/$name/";
                        if (!$name) return "$url/$lang/rss/";
                    } else {
                        if ($name) return "$url/rss/$name/";
                        if (!$name) return "$url/rss/";
                    }
                    break;
                }
            }
        }
        public static function m(Array $var = array(), Array $tmpl = array()) {
            return \AsyncWeb\Frontend\URLParser::addVariablesAndBlocks($var, $tmpl, true);
        }
        public static function c(Array $tmpl) {
            return \AsyncWeb\Frontend\URLParser::addVariablesAndBlocks($var = array(), $tmpl, true, false);
        }
        public static function makeAW($params = array(), $moveparams = true, $uri = null, $paramsAreSafe = false, $getIsSafe = false, $js = false) {
            //if(!$uri){$uria = explode("/",$_SERVER["REQUEST_URI"]);$uri = $uria[0];}
            if (class_exists("\\AsyncWeb\\Frontend\\URLParser")) {
                if (isset($params["REMOVE_VARIABLES"]) && $params["REMOVE_VARIABLES"] == "1") {
                    $ret = \AsyncWeb\Frontend\URLParser::noVariables();
                } else {
                    $ret = \AsyncWeb\Frontend\URLParser::addVariables($params);
                }
                return $ret;
            }
            throw new Exception("URLParser is missing");
        }
        public static function make($params = array(), $moveparams = true, $uri = null, $paramsAreSafe = false, $getIsSafe = false, $js = false) {
            if (Path::$AsyncWeb) return Path::makeAW($params, $moveparams, $uri, $paramsAreSafe, $getIsSafe, $js);
            $amp = '&amp;';
            if ($js) $amp = "&";
            if (!$uri) {
                $uria = explode("?", $_SERVER["REQUEST_URI"]);
                $uri = $uria[0];
            }
            $uricontainsq = false;
            if (strpos($uri, "?") !== false) $uricontainsq = true;
            if (!is_array($params)) return $uri;
            $par = "";
            $usedkeys = array();
            foreach ($params as $key => $value) {
                if (!$paramsAreSafe) {
                    $key = htmlentities($key);
                    $value = htmlentities($value);
                }
                //echo $key.":".$usedkeys[$key]."\n";
                if (@$usedkeys[$key]) continue;
                $usedkeys[$key] = true;
                if ($value) {
                    if (!$par && !$uricontainsq) {
                        $par = "?";
                    } else {
                        $par.= $amp;
                    }
                    $par.= $key . "=" . $value;
                }
            }
            if ($moveparams) {
                foreach ($_GET as $key => $value) {
                    if (!$getIsSafe) {
                        $key = htmlentities($key);
                        $value = htmlentities($value);
                    }
                    //echo $key.":".$usedkeys[$key]."\n";
                    if (@$usedkeys[$key]) continue;
                    $usedkeys[$key] = true;
                    if (!$par && !$uricontainsq) {
                        $par = "?";
                    } else {
                        $par.= $amp;
                    }
                    $par.= $key . "=" . $value;
                }
            }
            return $uri . $par;
        }
        public static function getMovingParams() {
            $ret = "";
            foreach ($_GET as $key => $value) {
                if ($key == "AJAX") continue;
                $ret.= '&amp;' . htmlentities($key) . "=" . htmlentities($value);
            }
            return $ret;
        }
    }
    