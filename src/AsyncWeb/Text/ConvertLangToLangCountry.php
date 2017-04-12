<?php
namespace AsyncWeb\Text;
class ConvertLangToLangCountry {
    public static function convert($lang) {
        switch ($lang) {
            case "sk":
                return "sk-SK";
            case "cs":
                return "cs-CZ";
            case "en":
                return "en-US";
        }
        return lang;
    }
}
