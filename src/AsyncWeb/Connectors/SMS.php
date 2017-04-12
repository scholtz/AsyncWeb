<?php
namespace AsyncWeb\Connectors;
use AsyncWeb\DB\DB;
class SMS {
    public static $login = ""; //set up your login details in settings file
    public static $pass = ""; //set up your pass details in settings file
    public static $smsprice = 0.5;
    public static function send($to, $text, $user = "") {
        $ret = "failed: not implemented yet";
        $smsprice = SMS::$smsprice;
        $res = file_get_contents("https://api.sms-brana.org/https/send_sms.php?login=" . SMS::$login . "&password=" . SMS::$pass . "&to=$to&sms_text=" . urlencode($text));
        $resa = explode(":", $res);
        if (count($resa) == 5) {
            DB::u("sms", $id = $resa[2], array("id2" => $id, "to" => $to, "text" => $text, "ret" => $resa[1], "statuscode" => "1", "cena" => $resa[3], "kredit" => $resa[4]));
        } else {
            DB::u("sms", $id = md5(uniqid()), array("id2" => $id, "to" => $to, "text" => $text, "ret" => $ret));
        }
        if ($resa[0] == "OK") {
            return true;
        }
        return false;
    }
    public static function spracuj() {
        if ($_REQUEST["msg_id"]) {
            return DB::u("sms", $_REQUEST["msg_id"], array("statuscode" => $_REQUEST["status_code"], "to" => $_REQUEST["mt_to"]));
        }
        return false;
    }
}
?>