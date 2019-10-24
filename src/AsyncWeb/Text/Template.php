<?php
// created by Ludovit Scholtz
// ludovit __ at __ scholtz __ dot __ sk
// 31.8.2012
//
// UPDATE
// 13.3.2014 - Template::set($key,$value);
namespace AsyncWeb\Text;
use AsyncWeb\IO\File;
class Template {
    public static function exists($name,$allowBlock = true) {
        if($allowBlock){
            try {
                if($ret = \AsyncWeb\Frontend\Block::exists($name)){
                    return $ret;
                }
            }
            catch(\Exception $exc) {
            }
        }

        $path = \AsyncWeb\Frontend\Block::$TEMPLATES_PATH;
        $path = rtrim($path, "/");
        $path = $path . "/" . $name;

        $fullpath = File::exists($path, $dbg);
        if($fullpath) return $fullpath;
        
        $name = str_replace("_","/",$name);
        $lang = \AsyncWeb\System\Language::getLang();
        foreach(\AsyncWeb\Frontend\Block::$TEMPLATE_PATHS as $path=>$weight){
            $f = $path."/".$name.".$lang.html";
            if($f){
                if($fullpath = File::exists($f, $dbg)){
                    return $fullpath;
                }
            }
            $f = $path."/".$name.".html";
            if($f){
                if($fullpath = File::exists($f, $dbg)){
                    return $fullpath;
                }
            }
        }
        
        return File::exists("../templates/emailtemplate.html");
    }
    public static function loadTemplate($name, array $data, $engine = false, $dbg = false,$allowBlock = true) {
        $path = self::exists($name);
        if (!$path) {
            throw new \Exception(\AsyncWeb\System\Language::get("Template %template% not found", array("%template%" => $file)));
        }
        $template = File::load($path);
        if (!$engine) $engine = new \Mustache_Engine();
        return $engine->render($template, $data);
    }
}
