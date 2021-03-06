<?php
namespace AsyncWeb\HTTP;
/**
 Header class
 by Ludovit Scholtz
 ludovit@scholtz.sk
 1.12.2010
 Example:
 Header::send("Location: http://ludovit.scholtz.sk/");
 Header::s("reload");
 Header::s("location","?");
 /*
 */
class Header {
    public static function send($text, $b = null) {
        @header($text, $b);
		return true;
    }
    public static function reload($value) {
		
        return Header::s("reload", $value);
    }
    public static function s($type, $value = "") {
        switch ($type) {
            case "reload":
				if(\AsyncWeb\Storage\Session::get("__AW__HEADER__LAST_RELOAD"))
				if(\AsyncWeb\Storage\Session::get("__AW__HEADER__LAST_RELOAD") > microtime(true) - 0.5){
					\AsyncWeb\Text\Msg::err(\AsyncWeb\System\Language::get("Website has detected too fast redirect"));
					return false;
				}
				\AsyncWeb\Storage\Session::set("__AW__HEADER__LAST_RELOAD",microtime(true));

				$url = "";
                if ($_SERVER["SERVER_PORT"] == 443) {
                    $url = "https://";
                } else {
                    $url = "http://";
                }
                $url.= $_SERVER["HTTP_HOST"];
                if (is_array($value)) {
                    $uri = \AsyncWeb\System\Path::make($value, $moveparams = true, $uri = null, $paramsAreSafe = false, $getIsSafe = false, $js = true);
                } else {
                    $uri = $_SERVER["REQUEST_URI"];
                }
                $url.= $uri;
                return Header::send("Location: $url");
            break;
            case "location":
                if ($value == "?") return Header::s("reload", array("REMOVE_VARIABLES" => "1"));
                if (substr($value, 0, 4) != "http") {
                    if (@$_SERVER["SERVER_PORT"] == 443) {
                        $prot = "https://";
                    } else {
                        $prot = "http://";
                    }
                    if (substr($value, 0, 1) == "?") {
                        $pos = strpos($_SERVER["REQUEST_URI"], "?");
                        if ($pos) {
                            $value = $prot . $_SERVER["HTTP_HOST"] . substr($_SERVER["REQUEST_URI"], 0, $pos) . $value;
                        } else {
                            $value = $prot . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . $value;
                        }
                    } else {
                        $value = $prot . @$_SERVER["HTTP_HOST"] . $value;
                    }
                }
                return Header::send("Location: $value");
                break;
            default:
                return Header::send("$type: $value");
            }
    }
}
