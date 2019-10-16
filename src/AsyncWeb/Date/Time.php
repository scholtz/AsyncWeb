<?php
/**
 Time management class
 The primary purpose of the class is to specify the microsecond time as bigint
 replaces time() function with Time::get()
 functions that requires unix time must convert the time object with Time::getUnix($time);
 to add a time series the time must be added with $t += Time::span($seconds);
 the Time class can be modified on the System::$USE_MICROTIME setting..
 if System::$USE_MICROTIME == false, time() == Time::get()
 2017.02.10 Added support for internalization
 Example:
 \AsyncWeb\System\System::Set("Locale","en_US");
 \AsyncWeb\Date\Time::ToString(time() - 100);
 */
namespace AsyncWeb\Date;
class Time {
    public static $USE_MICROTIME = false;
    public static function get($time = null) {
        if ($time === null) {
            if (Time::$USE_MICROTIME) {
                return Time::format(floor(microtime(true) * 1e6));
            } else {
                return Time::format(time());
            }
        } elseif (!$time) {
            return $time;
        } elseif (ceil(log10($time)) == 10) {
            if (Time::$USE_MICROTIME) {
                return Time::format($time . "000000");
            } else {
                return Time::format($time);
            }
        } elseif (ceil(log10($time)) == 16) {
            if (Time::$USE_MICROTIME) {
                return Time::format($time);
            } else {
                return Time::format(substr($time, 0, 10));
            }
        } else {
            if (Time::$USE_MICROTIME) {
                return Time::format(floor(microtime(true) * 1e6));
            } else {
                return Time::format(time());
            }
        }
    }
    public static function format($number) {
        return number_format($number, 0, ".", "");
    }
    public static function getUnix($time) {
        if (strlen(Time::format($time)) == 10) {
            return $time;
        } elseif (strlen(Time::format($time)) == 16) {
            return substr(Time::format($time), 0, 10);
        } else {
            return time();
        }
    }
    public static function span($seconds) {
        return $seconds * Time::getMultiplier();
    }
    public static function getMultiplier() {
        if (Time::$USE_MICROTIME) {
            return 1e6;
        }
        return 1;
    }
    public static function ToString($date) {
        $date = self::get($date);
        $dt = new \DateTime();
        $dt->setTimestamp($date);
        $locale = \AsyncWeb\System\System::get("Locale");
        if ($locale === null) $locale = "sk_SK";
        if ($locale && class_exists("\IntlDateFormatter")) {
            $format = \IntlDateFormatter::MEDIUM;
            if ($date > (self::get() - self::span(3600 * 24))) {
				return date("H:i:s", $date);
                $format = \IntlDateFormatter::SHORT;
            }
            $formatter = new \IntlDateFormatter($locale, $format, $format);
            return $formatter->format($dt);
        }
        return date("d.m.Y", $date);
    }
    /**
     * This function converts str2time or time2str
     * also parses date 2010-30-10 as time  2010-30-10T23:59:59
     *
     *@input $from true|false If false, than input time is string (str2time).
     */
    public static function ConvertDate($time, $from, $format = "Y-m-d") {
        // tato funkcia skonvertuje cas UNIXovsky na normalny
        // from.. ak true, tak $time je unix, ak nie, tak je zadany vo formate $format
        if (!$format) $format = "Y-m-d";
        if ($from) {
            if (!$time) return "";
            if ($time <= 0) return '';
            // ak nam nechce vratit rovnaky vysledok po strtotime, tak nastav zachranny rezim
            $ret = @date($format, \AsyncWeb\Date\Time::getUnix($time));
            if (\AsyncWeb\Date\Time::get(strtotime($ret)) != $time) {
                $ret = @date("c", \AsyncWeb\Date\Time::getUnix($time)); //ISO 8601
                
            }
            if($format == "Y-m-d"){
                $ret = substr($ret,0,10);
            }
            return $ret;
        } else {
            if (!$time) return 0;
            $time1 = \AsyncWeb\Date\Time::get((int)strtotime($time));
            if (\AsyncWeb\Date\Time::get(strtotime(date("Y-m-d", \AsyncWeb\Date\Time::getUnix($time1)))) == $time1 && mb_strlen($time, 'UTF-8') == 10) { // je vyplneny iba datum vo formate YYYY-MM-DD
                // pridaj k tomu 23hod, 59min, 59 sek
                $time1+= \AsyncWeb\Date\Time::span(24 * 3600 - 1);
            }
            if (\AsyncWeb\Date\Time::get(strtotime(date("d.m.Y", \AsyncWeb\Date\Time::getUnix($time1)))) == $time1 && mb_strlen($time, 'UTF-8') == 10) { // je vyplneny iba datum vo formate DD-MM-YYYY
                // pridaj k tomu 23hod, 59min, 59 sek
                $time1+= \AsyncWeb\Date\Time::span(24 * 3600 - 1);
            }
            return $time1;
        }
    }
}
