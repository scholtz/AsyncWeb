<?php
namespace AsyncWeb\Text;
class Texts {
    /**
     This function does the substr function, but does not end the string in the middle of the word if the end is between min and max length.
     */
    public static function word_substr($text, $from, $minlength, $maxlength) {
        $text = mb_substr($text, $from, $maxlength);
        $p1 = strrpos($text, ' ');
        if ($p1 >= $minlength) {
            return mb_substr($text, 0, $p1);
        }
        return $text;
    }
    public static function clear_($text) {
        return str_replace("-", "_", Texts::clear($text));
    }
    public static function toAscii($text) {
        setlocale(LC_CTYPE, 'sk_SK.utf8');
        $text = Transliterate::convert($text);
        $text = @iconv("utf-8", "ascii//TRANSLIT//IGNORE", $text);
        return $text;
    }
    public static function clear($text) {
        setlocale(LC_CTYPE, 'sk_SK.utf8');
        $text = Transliterate::convert($text);
        $url = strtolower(@iconv("utf-8", "ascii//TRANSLIT//IGNORE", $text));
        $url = str_replace('+', "_plus_", $url);
        $url = str_replace('–', "-", $url);
        $url = str_replace(chr(226) . chr(180), "", $url);
        $url = str_replace('*', "_", $url);
        $url = str_replace(';', "_", $url);
        $url = str_replace('%', "", $url);
        $url = str_replace('^', "", $url);
        $url = str_replace('#', "", $url);
        $url = str_replace('$', "", $url);
        $url = str_replace(':', "", $url);
        $url = str_replace('•', "", $url);
        $url = str_replace('‹', "", $url);
        $url = str_replace('<', "", $url);
        $url = str_replace('>', "", $url);
        $url = str_replace('`', "", $url);
        $url = str_replace('´', "", $url);
        $url = str_replace('!', "", $url);
        $url = str_replace('?', "", $url);
        $url = str_replace('|', "_", $url);
        $url = str_replace('˝', "", $url);
        $url = str_replace('¨', "", $url);
        $url = str_replace('&', "a", $url);
        $url = str_replace('=', "_je_", $url);
        $url = str_replace('@', "_at_", $url);
        $url = str_replace('/', "_", $url);
        $url = str_replace(" ", "_", $url);
        $url = str_replace("__", "_", $url);
        $url = str_replace("__", "_", $url);
        $url = str_replace("__", "_", $url);
        $url = str_replace("__", "_", $url);
        $url = str_replace("'", "", $url);
        $url = str_replace('"', "", $url);
        $url = str_replace('_-_', "-", $url);
        $url = str_replace('(', "-", $url);;
        $url = str_replace('[', "-", $url);
        $url = str_replace(')', "", $url);
        $url = str_replace(']', "", $url);
        if (substr($url, -1) == "_") $url = substr($url, 0, -1);
        $url = str_replace("_", "-", $url);
        $url = str_replace("--", "-", $url);
        $url = str_replace("--", "-", $url);
        $url = str_replace("--", "-", $url);
        $url = str_replace(",", "-", $url);
        $url = str_replace(".", "-", $url);
        $url = str_replace("--", "-", $url);
        if (substr($url, 0, 1) == "-") $url = substr($url, 1);
        if (substr($url, 0, 1) == "-") $url = substr($url, 1);
        if (substr($url, -1) == "-") $url = substr($url, 0, -1);
        if (substr($url, -1) == "-") $url = substr($url, 0, -1);
        return $url;
    }
    public static function clearName($text) {
        $text = Transliterate::convert($text);
        $dict = array('á' => 'a', 'ä' => 'a', 'č' => 'c', 'ć' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e', 'ë' => 'e', 'í' => 'i', 'ĺ' => 'l', 'ľ' => 'l', 'ł' => 'l', 'ň' => 'n', 'ń' => 'n', 'ô' => 'o', 'ó' => 'o', 'ö' => 'o', 'ő' => 'o', 'š' => 's', 'ř' => 'r', 'ŕ' => 'r', 'ú' => 'u', 'ů' => 'u', 'ü' => 'u', 'ű' => 'u', 'ť' => 't', 'ý' => 'y', 'ž' => 'z', 'Á' => 'A', 'Ä' => 'A', 'Č' => 'C', 'Ć' => 'C', 'Ď' => 'D', 'É' => 'E', 'Ě' => 'E', 'Ë' => 'E', 'Í' => 'I', 'Ĺ' => 'L', 'Ľ' => 'L', 'Ň' => 'N', 'Ń' => 'N', 'Ô' => 'O', 'Ó' => 'O', 'Ö' => 'O', 'Ő' => 'O', 'Š' => 'S', 'Ř' => 'R', 'Ŕ' => 'R', 'Ú' => 'U', 'Ů' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ť' => 'T', 'Ý' => 'Y', 'Ž' => 'Z',);
        $url = $text;
        $url = strtr($url, $dict);
        //	$url = mb_convert_encoding($url,"ascii","utf-8");
        $url = str_replace(",", "", $url);
        $url = strtolower($url);
        $url = str_replace(".", "_", $url);
        $url = trim($url);
        if (strpos($url, ":") !== false) {
            $url = substr($url, 0, strpos($url, ":"));
        }
        if (strpos($url, ";") !== false) {
            $url = substr($url, 0, strpos($url, ";"));
        }
        $url = str_replace('+', "_plus_", $url);
        $url = str_replace('–', "-", $url);
        $url = str_replace(chr(226) . chr(180), "", $url);
        $url = str_replace('*', "_", $url);
        $url = str_replace('#', "", $url);
        $url = str_replace('$', "", $url);
        $url = str_replace(':', "", $url);
        $url = str_replace('•', "", $url);
        $url = str_replace('‹', "", $url);
        $url = str_replace('<', "", $url);
        $url = str_replace('>', "", $url);
        $url = str_replace('`', "", $url);
        $url = str_replace('´', "", $url);
        $url = str_replace('!', "", $url);
        $url = str_replace('?', "", $url);
        $url = str_replace('|', "_", $url);
        $url = str_replace('˝', "", $url);
        $url = str_replace('¨', "", $url);
        $url = str_replace('&', "a", $url);
        $url = str_replace('=', "_je_", $url);
        $url = str_replace('@', "_at_", $url);
        $url = str_replace('/', "_", $url);
        $url = str_replace(" ", "_", $url);
        $url = str_replace("__", "_", $url);
        $url = str_replace("__", "_", $url);
        $url = str_replace("__", "_", $url);
        $url = str_replace("__", "_", $url);
        $url = str_replace("'", "", $url);
        $url = str_replace('"', "", $url);
        $url = str_replace('_-_', "-", $url);
        $url = str_replace('(', "-", $url);;
        $url = str_replace('[', "-", $url);
        $url = str_replace(')', "", $url);
        $url = str_replace(']', "", $url);
        if (substr($url, -1) == "_") $url = substr($url, 0, -1);
        $url = str_replace("_", "-", $url);
        $url = str_replace("--", "-", $url);
        $url = str_replace("--", "-", $url);
        $url = str_replace("--", "-", $url);
        $url = str_replace("--", "-", $url);
        if (substr($url, 0, 1) == "-") $url = substr($url, 1);
        if (substr($url, 0, 1) == "-") $url = substr($url, 1);
        if (substr($url, -1) == "-") $url = substr($url, 0, -1);
        if (substr($url, -1) == "-") $url = substr($url, 0, -1);
        return $url;
    }
}
/**
 * Class for transliterating Cyrillic to Latinic
 *
 * Examples of using class:
 *
 * 1. Creating class instance with arguments (argument is the text we want to transliterate)
 *
 * <code>
 * <?php
 * $text = "This is some text written using Cyrillic character set";
 *
 * $tl = new transliterate($text);
 *
 * // Text is translated and is being held in $string variable
 *
 * echo $transl->string; // Prints transliterated text
 * ?>
 * </code>
 *
 * 2. Using other methods for transliterating text
 *
 * 2.a:
 * <code>
 * $text = "This is some text written using Cyrillic character set";
 *
 * $tl = new transliterate;
 *
 * $translitereated_text = $tl->transliterate_return($text);
 *
 * echo $translitereated_text; // Prints transliterated text
 * </code>
 *
 * 2.b Using "pass by reference" to transliterate text directyle:
 *
 * <code>
 * $text = "This is some text written using Cyrillic character set";
 *
 * $tl = new transliterate;
 *
 * $tl->transliterate_ref($text);
 *
 * echo $text; // Prints transliterated text
 * </code>
 *
 * 2.c If you want to output the text directly:
 * <code>
 * $text = "This is some text written using Cyrillic character set";
 *
 * $tl = new transliterate;
 *
 * echo $tl->transliterate_return($text); // Prints transliterated text
 * </code>
 * @author Mihailo Joksimovic
 * @version 0.1
 */
/**
 * Class body
 * @package transliterate
 */
class Transliterate {
    /**
     * This function returns translated text to be stored in variable or echoed :-)
     *
     * Example 1:
     * <code>
     * $text = "This is some text written using Cyrillic character set";
     *
     * $tl = new transliterate;
     *
     * $translitereated_text = $tl->transliterate_return($text);
     *
     * echo $translitereated_text; // Prints transliterated text
     * </code>
     * Example 2:
     * <code>
     * $text = "This is some text written using Cyrillic character set";
     *
     * $tl = new transliterate;
     *
     * echo $tl->transliterate_return($text); // Prints transliterated text
     * </code>
     *
     * @param string $str Text to be translated
     * @return string
     */
    public static function convert($str) {
        return preg_replace(Transliterate::$niddle, Transliterate::$replace, $str);
    }
    /**
     * This function uses "pass by reference" method to directlty transliterate text
     * Better said, if you want to transliterate text stored in $text variable,
     * you should do something similar to this:
     * <code>
     * $text = "This is some text written using Cyrillic character set";
     *
     * $tl = new transliterate;
     *
     * $tl->transliterate_ref($text);
     *
     * echo $text; // Prints transliterated text
     * </code>
     * @param string &$str
     */
    public static function transliterate_ref(&$str) {
        $this->string = preg_replace($this->niddle, $this->replace, $str);
        $str = $this->string;
    }
    private static $niddle = array("/а/", "/б/", "/в/", "/г/", "/д/", "/ђ/", "/е/", "/ж/", "/з/", "/и/", "/ј/", "/к/", "/л/", "/љ/", "/м/", "/н/", "/њ/", "/о/", "/п/", "/р/", "/с/", "/т/", "/ћ/", "/у/", "/ф/", "/х/", "/ц/", "/ч/", "/џ/", "/ш/", "/А/", "/Б/", "/В/", "/Г/", "/Д/", "/Ђ/", "/Е/", "/Ж/", "/З/", "/И/", "/Ј/", "/К/", "/Л/", "/Љ/", "/М/", "/Н/", "/Њ/", "/О/", "/П/", "/Р/", "/С/", "/Т/", "/Ћ/", "/У/", "/Ф/", "/Х/", "/Ц/", "/Ч/", "/Џ/", "/Ш/", "/я/", "/ы/", "/ю/", "/щ/", "/ь/", "/ч/", "/ъ/", "/а/", "/б/", "/в/", "/г/", "/д/", "/е/", "/ё/", "/ж/", "/з/", "/и/", "/й/", "/к/", "/л/", "/м/", "/н/", "/о/", "/п/", "/р/", "/с/", "/т/", "/у/", "/ф/", "/х/", "/ц/", "/ч/", "/ш/", "/щ/", "/ъ/", "/ы/", "/ь/", "/э/", "/ю/", "/я/", "/а/", "/б/", "/в/", "/г/", "/д/", "/е/", "/ё/", "/ж/", "/з/", "/и/", "/й/", "/к/", "/л/", "/м/", "/н/", "/о/", "/п/", "/р/", "/с/", "/т/", "/у/", "/ф/", "/х/", "/ц/", "/ч/", "/ш/", "/щ/", "/ъ/", "/ы/", "/ь/", "/э/", "/ю/", "/я/");
    private static $replace = array("a", "b", "v", "g", "d", "d", "e", "z", "z", "i", "j", "k", "l", "lj", "m", "n", "nj", "o", "p", "r", "s", "t", "c", "u", "f", "h", "c", "c", "dz", "s", "A", "B", "B", "G", "D", "D", "E", "Z", "Z", "I", "J", "K", "L", "LJ", "M", "N", "NJ", "O", "P", "R", "S", "T", "C", "U", "F", "H", "C", "C", "DZ", "S", "ja", "y", "ju", "sc", "'", "4", "'", "a", "b", "v", "g", "d", "e", "jo", "z", "z", "i", "j", "k", "l", "m", "n", "o", "p", "r", "s", "t", "u", "f", "ch", "c", "c", "s", "sc", "'", "y", "'", "e", "ju", "ja", "a", "b", "v", "g", "d", "e", "jo", "z", "z", "i", "j", "k", "l", "m", "n", "o", "p", "r", "s", "t", "u", "f", "ch", "c", "c", "s", "sc", "'", "y", "'", "e", "ju", "ja",);
    /**
     * Transliterated text is always saved in this variable, so you can use
     * it numerous times ... :-)
     *
     * @var string
     * @access public
     *
     */
    public $string;
}
