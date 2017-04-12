<?php
/** 
 Class group manage users in groups
 created by Ludovit Scholtz, 12. july 2011 ludovit __at__ scholtz __dot__ sk
 20120828 	Login ::is_in_group() moze obsahovat array() skupin.. ak je aspon v jednej z tych skupin, vrati true. inak vrati false.
 Pouzitie: npr Login :: requiredLoggedIn2(array("CEO","COO"));
 */
namespace AsyncWeb\Objects;
use AsyncWeb\DB\DB;
use AsyncWeb\Security\Auth;
use AsyncWeb\Cache\Cache;
use AsyncWeb\System\Language;
class Group {
    private static function resetCache() {
        Group::$getCache = array();
        Group::$getByNameCache = array();
        Group::$getMySkupinyCache = array();
        Group::$getMySkupinyNamesCache = array();
    }
    public static function exists($id2) {
        if (Group::get($id2)) return true;
        return false;
    }
    public static function existsByName($name) {
        if (Group::getByName($name)) return true;
        return false;
    }
    private static $getCache = array();
    public static function get($id2) {
        if (isset(Group::$getCache[$id2])) return Group::$getCache[$id2];
        $ret = DB::gr("groups", array("id2" => $id2));
        Group::$getCache[$id2] = $ret;
        return $ret;
    }
    private static $getByNameCache = array();
    public static function getByName($name) {
        if (isset(Group::$getByNameCache[$name])) return Group::$getByNameCache[$name];
        $ret = DB::gr("groups", array("id3" => $name));
        if (!$ret) $ret = DB::gr("groups", array("name" => $name));
        Group::$getByNameCache[$name] = $ret;
        return $ret;
    }
    private static $getMySkupinyCache = array();
    public static function getMySkupiny($usr = null) {
        if (isset(Group::$getMySkupinyCache[$usr])) return Group::$getMySkupinyCache[$usr];
        $ret = array();
        if (!$usr) {
            $usr = Auth::userId();
        }
        if (!$usr) return $ret;
        $res = DB::g("users_in_groups", array("users" => $usr));
        while ($row = DB::fetch_assoc($res)) {
            $ret[$row["groups"]] = DB::gr("groups", $row["groups"]);
        }
        Group::$getMySkupinyCache[$usr] = $ret;
        return $ret;
    }
    private static $getMySkupinyNamesCache = array();
    public static function getMySkupinyNames($usr = null) {
        if (isset(Group::$getMySkupinyNamesCache[$usr])) return Group::$getMySkupinyNamesCache[$usr];
        $ret = array();
        foreach (Group::getMySkupiny($usr) as $id => $row) {
            $key = "id3";
            if (!isset($row[$key])) $key = "name";
            if (isset($row[$key]) && $row[$key]) array_push($ret, $row[$key]);
        }
        Group::$getMySkupinyNamesCache[$usr] = $ret;
        return $ret;
    }
    /**
     * Táto funkcia vracia true, ak je uzivatel v skupine nazyvanej $string
     *
     * @param string $string Meno skupiny
     * @return boolean True, ak je v danej skupine, false, ak nepatri do danej skupiny.
     */
    public static function is_in_group($string, $usr = null) {
        if (!$usr) {
            $usr = Auth::userId();
        }
        if (!$usr) return false;
        $my_groups = Group::getMySkupinyNames($usr);
        if (is_array($string)) {
            foreach ($string as $str) {
                if (Group::is_in_group($str, $usr)) return true;
            }
            return false;
        }
        if (in_array($string, $my_groups)) {
            return true;
        } else {
            return false;
        }
    }
    public static function isInGroupId($id, $usr = null) {
        if (!$usr) {
            $usr = Auth::userId();
        }
        if (!$usr) return false;
        $my_groups = Group::getMySkupiny($usr);
        if (isset($my_groups[$id])) return true;
        return false;
    }
    public static function userInGroup($user, $groupName = null) {
        if ($groupName === null) {
            return Group::is_in_group($user);
        }
        return Group::is_in_group($groupName, $user);
    }
    public static function addUserToGroup($groupName, $usr = null) {
        $row = Group::getByName($groupName);
        if (!$row) {
            Group::create($groupName, $groupName);
            $row = Group::getByName($groupName);
        }
        if ($grp = $row["id2"]) {
            if (!$usr) $usr = Auth::userId();
            if ($usr) {
                if (!Group::is_in_group($grp, $usr)) {
                    Group::resetCache();
                    Cache::invalidate("menu");
                    return DB::u("users_in_groups", md5(uniqid()), array("groups" => $grp, "users" => $usr));
                }
            }
        }
        return false;
    }
    public static function create($codename, $text, $level = 16) {
        $row = DB::gr("groups", array("id3" => $codename));
        if (!$row) {
            Language::set($term = md5(uniqid()), $text);
            $ret = DB::u("groups", md5(uniqid()), $upd = array("id3" => $codename, "name" => $text, "utajenie" => $level));
            Group::resetCache();
            return $ret;
        }
        return false;
    }
}
