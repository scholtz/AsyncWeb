<?php
/**
 Executes a function
 28.4.2013 	modules/Time.php compliant
 */
namespace AsyncWeb\System;
class Execute {
    public static $debug = false;
    public static function run($function, $params = null, $doinclude = true) {
        if (Execute::$debug) echo "Execute::run($function,,$doinclude)\n";
        if (is_array($function)) {
            return false;
        }
        $value = $function;
        if (substr($value, 0, 5) == "PHP::") {
            if ($value == "PHP::Time::get()" || $value == "PHP::time()") {
                return \AsyncWeb\Date\Time::get();
            }
            if ($value == "PHP::rand()") return rand();
            if ($value == "PHP::date()") {
                return date("d.m.Y");
            };
            if ($value == "PHP::date(c)") {
                return date("c");
            };
            // pre dynamicke staticke triedy PHP::Broker::getId()
            $value = substr($value, 5);
            if (($pos = strpos($value, "::")) !== false) {
                $class = substr($value, 0, $pos);
                $func = substr($value, $pos + 2);
                if (!class_exists($class) && $doinclude) {
                    if (Execute::$debug) echo "!class_exists($class)\n";
                    if (\AsyncWeb\IO\File::exists($f = "modules/$class.php")) {
                        if (Execute::$debug) echo "File::exists($f)\n";
                        include_once ($f);
                    } else {
                        if (Execute::$debug) echo "!File::exists($f)\n";
                    }
                    if (\AsyncWeb\IO\File::exists($f = "php/$class.php")) {
                        if (Execute::$debug) echo "File::exists($f)\n";
                        include_once ($f);
                    } else {
                        if (Execute::$debug) echo "!File::exists($f)\n";
                    }
                }
                if (substr($func, -2, 2) == "()") $func = substr($func, 0, -2);
                if (class_exists($class)) {
                    $ret = call_user_func(array($class, $func), $params);
                    return $ret;
                } else {
                    if (Execute::$debug) echo "!class_exists($class)\n";
                }
            }
        }
        return false;
    }
}
?>