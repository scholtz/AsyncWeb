<?php
/**
 This class stores the cache in file system
 28.4.2013 	modules/Time.php compliant
 */
namespace AsyncWeb\Cache;
class CacheFile {
    public static $path = false;
    public static function setPath($path) {
        if (!is_dir($path)) @mkdir($path);
        CacheFile::$path = $path;
        return is_dir($path);
    }
    public static function save($item, $value, $timeout = 0, $contenttype = "") {
        if (!CacheFile::$path) return false;
        if (!is_dir(CacheFile::$path)) mkdir($path);
        $cache = CacheFile::load($item);
        $od = \AsyncWeb\Date\Time::get();
        $do = \AsyncWeb\Date\Time::get() + \AsyncWeb\Date\Time::span($timeout);
        $etag = md5($value);
        if ($cache) {
            if ($cache["etag"] == $etag) {
                $od = $cache["od"];
            }
        }
        $c = @file_put_contents(CacheFile::$path . "/" . md5($item) . ".cache", ($od . "\t" . $do . "\t" . $etag . "\t" . $contenttype) . "\n" . $value);
        if (!$c) {
            // skus subor vymazat a dat ho tam znovu
            @unlink(CacheFile::$path . "/" . md5($item) . ".cache");
            //chmod(CacheFile::$path."/".md5($item).".cache", 0777);
            $c = file_put_contents(CacheFile::$path . "/" . md5($item) . ".cache", ($od . "\t" . $do . "\t" . $etag . "\t" . $contenttype) . "\n" . $value);
        }
        $f = CacheFile::$path . "/cache" . $item . ".html";
        $info = pathinfo($f);
        if (!is_dir($info["dirname"])) @mkdir($info["dirname"]);
        $c = @file_put_contents($info, $value);
        if (!$c) {
            // skus subor vymazat a dat ho tam znovu
            @unlink($f);
            //chmod(CacheFile::$path."/".md5($item).".cache", 0777);
            $c = @file_put_contents($f, $value);
        }
        $f = CacheFile::$path . "/cache" . $item . "";
        $info = pathinfo($f);
        if (!is_dir($info["dirname"])) @mkdir($info["dirname"]);
        $c = @file_put_contents($info, $value);
        if (!$c) {
            // skus subor vymazat a dat ho tam znovu
            @unlink($f);
            //chmod(CacheFile::$path."/".md5($item).".cache", 0777);
            $c = @file_put_contents($f, $value);
        }
        return $c;
    }
    public static function load($item, $timeout = 0) {
        if (!CacheFile::$path) return false;
        if (!is_dir(CacheFile::$path)) mkdir(CacheFile::$path, 0777, true);
        //if(!is_file() return null;
        $cache = @file_get_contents($file = CacheFile::$path . "/" . md5($item) . ".cache");
        if (!$cache) return null;
        $pos = strpos($cache, "\n");
        $data = explode("\t", substr($cache, 0, $pos));
        $od = $data[0];
        $do = $data[1];
        $etag = $data[2];
        $contenttype = $data[3];
        if (\AsyncWeb\Date\Time::get($do) > \AsyncWeb\Date\Time::get() - \AsyncWeb\Date\Time::span($timeout)) {
            return array("data" => substr($cache, $pos + 1), "od" => $od, "do" => $do, "etag" => $etag, "contenttype" => $contenttype);
        }
        return null;
    }
    public static function clear() {
    }
    public static function optimize() {
    }
}
?>