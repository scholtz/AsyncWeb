<?php
namespace AsyncWeb\Frontend;
class URLParser {
    public static function get($templateid) {
        $arr = explode("/", URLParser::getCurrent());
		$parsed = self::parse(URLParser::getCurrent());
        $itembase = $templateid;
        $replace = $templateid;
        if ($p = strpos($templateid, ":")) {
            $itembase = substr($templateid, 0, $p);
            $replace = substr($templateid, $p + 1);
        }
        foreach ($arr as $item) {
            $itema = explode(":", $item);
            if (count($itema) < 2) continue;
            if ($itema[0] == $itembase) {
                return $itema[1];
            }
        }
		if(isset($parsed["tmpl"][$itembase])){
			$replace = $parsed["tmpl"][$itembase];
		}
        return $replace;
    }
    public static function getCurrent() {
        return @$_SERVER["REQUEST_URI"];
    }
    protected static $parseCache = array();
    public static function parse($url = "") {
        if (!$url) $url = URLParser::getCurrent();
        if ($pos = strpos($url, "?")) $url = substr($url, 0, $pos);
        if (isset(URLParser::$parseCache[$url])) {
            return URLParser::$parseCache[$url];
        }
		
		$first = true;
        $arr = explode("/", $url);
        $ret = array();
		
		
		// if last item contains dot (.), the loading file should be a file, so return 404 error because it should not be processed by URLParser
		if(strpos($arr[count($arr)-1],".")){
			header("HTTP/1.0 404 Not Found");
			exit;
		}
		
        foreach ($arr as $item) {
            if (!$item) continue;
            if ($p = strpos($item, "=")) {
                $ret["var"][substr($item, 0, $p) ] = urldecode(substr($item, $p + 1));
            } else {
                if ($p = strpos($item, ":")) {
                    $ret["tmpl"][substr($item, 0, $p) ] = urldecode(substr($item, $p + 1));
                } else {
					if($first){
						$ret["tmpl"]["Content_Cat"] = str_replace("-","",ucwords(urldecode($item),"-"));
					}else{
						$ret["tmpl"][$item] = urldecode($item);	
					}
                    
                }
            }
        }
        if (isset($ret["var"])) foreach ($ret["var"] as $k => $v) {
            $_GET[$k] = $v;
            $_REQUEST[$k] = $v;
        }
        URLParser::$parseCache[$url] = $ret;
		
        return $ret;
    }
    public static function merge2(Array $arr1, Array $arr2) {
        foreach ($arr2 as $k1 => $v1) {
            foreach ($v1 as $k2 => $v2) {
                if (!isset($arr1[$k1][$k2])) $arr1[$k1][$k2] = $v2;
            }
        }
        return URLParser::merge($arr1);
    }
    public static function merge(Array $arr) {
        $ret = "";
        if (isset($arr["tmpl"])) {
            foreach ($arr["tmpl"] as $k => $v) {
				if(\AsyncWeb\Menu\MainMenu::$CATEGORY_TAG_NAME && \AsyncWeb\Menu\MainMenu::$CATEGORY_TAG_NAME == $k){
					$ret.= '/' . urlencode($v);
				}elseif ($k == $v) {
                    $ret.= '/' . urlencode($k);
                } else {
                    $ret.= '/' . urlencode($k) . ":" . urlencode($v);
                }
            }
        }
        if (isset($arr["var"])) {
            foreach ($arr["var"] as $k => $v) {
                $ret.= '/' . urlencode($k) . "=" . urlencode($v);
            }
        }
        if (!$ret) $ret = "/";
        return $ret;
    }
    public static function add($param) {
        $arr = URLParser::parse(URLParser::getCurrent());
        $parama = URLParser::parse($param);
        foreach ($parama as $vartmpl => $arr2) {
            foreach ($arr2 as $k => $v) {
                $arr[$vartmpl][$k] = $v;
            }
        }
        return URLParser::merge($arr);
    }
    public static function addVariables(Array $param, $merge = true) {
        $arr = URLParser::parse(URLParser::getCurrent());
        foreach ($param as $k => $v) {
            if ("" . $v === "") {
                if (isset($arr["var"][$k])) unset($arr["var"][$k]);
            } else {
                $arr["var"][$k] = $v;
            }
        }
        if ($merge) {
            return URLParser::merge($arr);
        } else {
            return $arr;
        }
    }
    public static function addVariablesAndBlocks(Array $vars, Array $blocks, $merge = true, $passVariables = true) {
        $arr = URLParser::parse(URLParser::getCurrent());
        if (!$passVariables) {
            if (isset($arr["var"])) unset($arr["var"]);
        }
        foreach ($vars as $k => $v) {
            if ("" . $v === "") {
                if (isset($arr["var"][$k])) unset($arr["var"][$k]);
            } else {
                $arr["var"][$k] = $v;
            }
        }
        foreach ($blocks as $k => $v) {
            if ("" . $v === "") {
                if (isset($arr["tmpl"][$k])) unset($arr["tmpl"][$k]);
            } else {
                $arr["tmpl"][$k] = $v;
            }
        }
        if ($merge) {
            return URLParser::merge($arr);
        } else {
            return $arr;
        }
    }
    public static function noVariables() {
        $arr = URLParser::parse(URLParser::getCurrent());
        if (isset($arr["var"])) unset($arr["var"]);
        return URLParser::merge($arr);
    }
    public static function selectParameters($paramarr) {
        if (!$paramarr) return "";
        $urlarr = URLParser::parse();
        $ret = "";
        foreach ($paramarr as $k => $key) {
            if (isset($urlarr["var"][$key])) {
                $ret.= "/" . $key . "=" . $urlarr["var"][$key];
            }
        }
        return $ret;
    }
    public static function v($name) {
        $data = URLParser::parse();
        if (!isset($data["var"][$name])) {
            /**
             Converts all input to htmlspecialchars. in DB all input should be converted (f.e. " &quot;
             it is prevention against XSS
             */
            if (isset($_REQUEST[$name])) {
                if (is_array($_REQUEST[$name])) {
                    foreach ($_REQUEST[$name] as $k => $v) {
                        if (is_array($v)) {
                            unset($_REQUEST[$name][$k]); // level 2 arrays are not allowed
                            
                        } else {
                            $_REQUEST[$name][$k] = htmlspecialchars($v, ENT_COMPAT | ENT_HTML5, 'UTF-8');
                        }
                    }
                    return $_REQUEST[$name];
                } else {
                    return htmlspecialchars($_REQUEST[$name], ENT_COMPAT | ENT_HTML5, 'UTF-8');
                }
            }
            return null;
        }
        return $data["var"][$name];
    }
}
