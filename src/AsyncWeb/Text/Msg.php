<?php
use AsyncWeb\Text\Messages;
namespace AsyncWeb\Text;
class Msg {
    public static function err($error) {
        return Messages::getInstance()->error($error);
    }
    public static function warn($warning) {
        return Messages::getInstance()->warning($warning);
    }
    public static function mes($msg) {
        return Messages::getInstance()->msg($msg);
    }
    public static function show() {
        return Messages::getInstance()->show();
    }
}
?>