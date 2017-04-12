<?php
namespace AsyncWeb\Cron;
class CLI {
    public static function e() {
        if (class_exists("\\AsyncWeb\\Cron\\Cron")) {
            if (Cron::started()) {
                Cron::end();
            }
        }
        exit;
    }
}
?>