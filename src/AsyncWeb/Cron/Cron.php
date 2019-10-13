<?php
/**
 @class Cron
 Cron functions allowing CLI scripts not to execute several times each when script already running
 Cron::requireFinish(true); // checks if other script is waiting for the closure of this one
 */
namespace AsyncWeb\Cron;
use AsyncWeb\DB\DB;
define("__RS_CRON_TABLE", "cron");
define("__RS_CRON_STATUS_TABLE", "cron_status");
class Cron {
    public static $CRON_DIR = "/dev/shm/crons";
    public static $logfile = "/tmp/cron-logs.log";
    public static $output = true;
    private static $started = false;
    public static function started() {
        return Cron::$started;
    }
    public static function start($timeout = 3600, $dieOnFailure = true) {
        Cron::$started = true;
        $pid = getMyPid();
        $file = Cron::getFile();
        $ret = 0;
        if (is_file($file)) {
            $d = explode(";", file_get_contents($file));
            if ($d[4]) {
                if (Cron::$logfile) file_put_contents(Cron::$logfile, date("c") . " cron not started - finish required $pid " . $_SERVER["SCRIPT_NAME"] . "\n", FILE_APPEND);
                if ($dieOnFailure) {
                    if (Cron::$output) echo "exitting process already running, finish required:: " . $d[0] . " : $file\n";
                    exit;
                }
                return 3;
            }
            if (\AsyncWeb\System\System::getOS() == "win") {
                $res = true; //exec("cmd /c tasklist /SVC /FO CSV");
                //file_put_contents("out001.txt",$res);
                //exit;
                
            } else {
                $res = exec("ps -Ao pid | grep " . $d[0]);
                $res = $res == $d[0];
            }
            if (!$res) {
                $ret = 4;
            } elseif ($d[1] > time() - $timeout) {
                if (Cron::$logfile) file_put_contents(Cron::$logfile, date("c") . " cron not started - process already running $pid " . $_SERVER["SCRIPT_NAME"] . "\n", FILE_APPEND);
                if ($dieOnFailure) {
                    if (Cron::$output) echo "exitting process already running:: " . $d[0] . " : $file\n";
                    exit;
                }
                return 2;
            } else {
                if ($dieOnFailure) {
                    Cron::kill();
                    $ret = 3;
                } else {
                    $ret = 1;
                }
            }
        }
        file_put_contents($file, "$pid;" . time() . ";" . date("c") . ";" . $_SERVER["SCRIPT_NAME"] . ";0");
        if (Cron::$logfile) file_put_contents(Cron::$logfile, date("c") . " cron start $pid " . $_SERVER["SCRIPT_NAME"] . "\n", FILE_APPEND);
        return $ret;
    }
    public static function getFile() {
        if (!is_dir(Cron::$CRON_DIR)) mkdir(Cron::$CRON_DIR);
        return $file = Cron::$CRON_DIR . "/" . md5($_SERVER["SCRIPT_NAME"]) . ".pid";
    }
    public static function setRequireFinish() {
        $file = Cron::getFile();
        $d = explode(";", file_get_contents($file));
        $out = "";
        $i = 0;
        foreach ($d as $dit) {
            $i++;
            if ($i == 5) {
                $out.= "1;";
            } else {
                $out.= "$dit;";
            }
        }
        file_put_contents($file, $out);
    }
    public static function requireFinish($finish = false) {
        $file = Cron::getFile();
        if (!file_exists($file)) return false;
        $d = explode(";", file_get_contents($file));
        if ($d[4]) {
            if (Cron::$logfile) file_put_contents(Cron::$logfile, date("c") . " cron finish catched " . $d[0] . " " . $_SERVER["SCRIPT_NAME"] . "\n", FILE_APPEND);
            if ($finish) Cron::finish();
            return true;
        }
        return false;
    }
    public static function finish() {
        Cron::end();
        if (class_exists("CLI")) CLI::e();
        exit;
    }
    public static function end() {
        $file = Cron::getFile();
        $d = explode(";", file_get_contents($file));
        unlink($file);
        file_put_contents(Cron::getFile() . ".last", $_SERVER["SCRIPT_NAME"] . ";" . time() . ";" . date("c"));
        if (Cron::$logfile) file_put_contents(Cron::$logfile, date("c") . " cron finished " . $d[0] . " " . $_SERVER["SCRIPT_NAME"] . "\n", FILE_APPEND);
    }
    public static function kill() {
        $file = Cron::getFile();
        Cron::setRequireFinish();
        for ($i = 0;$i < 30;$i++) {
            if (!file_exists($file)) return true;
            $x = file_get_contents($file);
            if (!$x) return true;
            if (Cron::$output) echo "waiting for process to finish... ($i) $file\n";
            sleep(10);
        } /**/
        if (is_file($file)) {
            $d = explode(";", file_get_contents($file));
            if (Cron::$logfile) file_put_contents(Cron::$logfile, date("c") . " cron kill " . $d[0] . " " . $_SERVER["SCRIPT_NAME"] . "\n", FILE_APPEND);
            if (\AsyncWeb\System\System::getOS() == "win") {
                passthru("TASKKILL /F /PID " . $d[0], $vars);
            } else {
                passthru("kill " . $d[0], $vars);
            }
            if (Cron::$output) var_dump($vars);
        }
    }
    private function install() {
        DB::query("CREATE TABLE IF NOT EXISTS `cron` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`min` INT NULL ,
`hod` INT NULL ,
`den` INT NULL ,
`wday` INT NULL ,
`mon` INT NULL ,
`rok` INT NULL ,
`type` SET( 'file', 'url' ) NOT NULL DEFAULT 'url',
`path` VARCHAR( 250 ) NOT NULL ,
`lcahnge` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
)");
        DB::query("
CREATE TABLE IF NOT EXISTS `cron_status` (
  `id` int(11) NOT NULL auto_increment,
  `id_cronu` int(11) NOT NULL,
  `status` set('S','F') collate utf8_unicode_ci NOT NULL default 'S',
  `min` int(11) default NULL,
  `hod` int(11) default NULL,
  `den` int(11) default NULL,
  `wday` int(11) default NULL,
  `mon` int(11) default NULL,
  `rok` int(11) default NULL,
  `lcahnge` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `id_cronu` (`id_cronu`)
)");
    }
    public function show() {
    }
    private function add($min = "*", $hod = "*", $den = "*", $wday = "*", $mon = "*", $rok = "*", $type = "*", $path = "*") {
    }
    private function run() {
        $check_time = 5; //min
        $date2 = getdate(time() - $check_time * 60);
        $date = getdate();
        $min = $date["minutes"];
        $upto = $min - $check_time;
        $hod = $date["hours"];
        $den = $date["mday"];
        $wday = $date["wday"];
        $mon = $date["mon"];
        $mon = $date["mon"];
        $res = DB::query("select * from " . __RS_CRON_TABLE . " where (
		(min is null or (min <= '$min' and min >= '$upto')) and 
		(hod is null or hod = '$hod') and 
		(den is null or den = '$den') and 
		(wday is null or wday = '$wday') and 
		(mon is null or mon = '$mon') and 
		(rok is null or rok = '$rok')
		)
		
		");
        while ($row = DB::fetch_assoc($row)) {
            var_dump($row);
            //for($upto)
            //$this->checkIfStarted($row["id"],"])
            switch ($row["type"]) {
                case 'file':
                break;
                case 'url':
                break;
            }
        }
    }
    private function checkIfStarted($id, $min, $hod, $den, $wday, $mon, $rok) {
        $id = DB::myAddSlashes($id);
        $min = DB::myAddSlashes($min);
        $hod = DB::myAddSlashes($hod);
        $den = DB::myAddSlashes($den);
        $wday = DB::myAddSlashes($wday);
        $mon = DB::myAddSlashes($mon);
        $rok = DB::myAddSlashes($rok);
        $row = DB::fetch_assoc_q("select count(*) c from `" . __RS_CRON_STATUS_TABLE . "` where (
		id_cronu = '$id',
		min = '$min',
		hod = '$hod',
		den = '$den',
		wday ='$wday',
		mon = '$mon',
		rok = '$rok'
		)
		");
        if ($row["c"]) {
            return true;
        } else {
            return false;
        }
    }
}
