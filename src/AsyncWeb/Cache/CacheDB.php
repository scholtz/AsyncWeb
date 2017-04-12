<?php
/**
 This class stores the cache in the DB
 28.4.2013 	modules/Time.php compliant
 */
namespace AsyncWeb\Cache;
use AsyncWeb\DB\DB;
class CacheDB {
    public static function save($item, $value, $timeout = 0, $contenttype = "") {
        $conf = array("cols" => array("data" => array("type" => "blob"), "binary" => true));
        $res = DB::u("cache", md5($item), array("path" => $item, "data" => $value, "time" => \AsyncWeb\Date\Time::get() + \AsyncWeb\Date\Time::span($timeout), "contenttype" => $contenttype, "do" => \AsyncWeb\Date\Time::get() + \AsyncWeb\Date\Time::span($timeout)), $conf, true);
        return $res;
    }
    public static function load($item, $timeout = 0) {
        if ($timeout) {
            $r = DB::gr("cache", array("id2" => md5($item), array("col" => "time", "op" => "gt", "value" => \AsyncWeb\Date\Time::get() - \AsyncWeb\Date\Time::span($timeout))));
            if ($r) {
                return $r;
            }
        } else {
            $r = DB::gr("cache", array("id2" => md5($item)));
            if ($r) {
                $r["etag"] = $r["id2"];
                return $r;
            }
        }
        return null;
    }
    public static function clear() {
        DB::query("truncate table `cache`");
    }
    public static function optimize() {
        DB::query("delete from `cache` where do < unix_timestamp()*" . \AsyncWeb\Date\Time::getMultiplier());
        DB::query("optimize table `cache`");
        return null;
    }
}
?>