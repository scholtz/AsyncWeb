<?php
namespace AsyncWeb\DefaultBlocks\Form;
use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\DB\DB;
use AsyncWeb\Email\Email;
use AsyncWeb\Text\Texts;
use AsyncWeb\Frontend\URLParser;
use AsyncWeb\Text\Msg;
class RecoverPasswordCode extends \AsyncWeb\DefaultBlocks\Form {
    public static function onUpdate($row) {
        $res = DB::u("users", $row["new"]["id2"], array("password" => hash("sha256", $row["new"]["cohash"] . $row["new"]["password"])));
        return;
        $recovery = DB::gr("passwordrecovery", array("code" => URLParser::v("code")));
        DB::u("passwordrecovery", $recovery["id2"], array("used" => time()));
        Msg::mes(Language::get("Your new password has been saved"));
        header("Location: /");
        exit;
    }
    public static function beforeUpdate() {
        if ($_REQUEST["newPass2"] != $_REQUEST["newPass"]) {
            \AsyncWeb\Text\Msg::err(Language::get("Passwords do not match!"));
            return false;
        }
        $pwd = $_REQUEST["newPass"];
        $error = "";
        if (strlen($pwd) < 8) {
            $error.= Language::get("Password is too short!") . " <br />";
        }
        if (!preg_match("#[0-9]+#", $pwd)) {
            $error.= Language::get("Password must contain at least one number!") . " <br />";
        }
        if (!preg_match("#[a-z]+#", $pwd)) {
            $error.= Language::get("Password must contain at least one letter [a-z]!") . " <br />";
        }
        if ($error) {
            Msg::err($error);
            return false;
        }
        return "1";
    }
    public function initTemplate() {
        $this->template = " ";
        if (!URLParser::v("code")) {
            Msg::err(Language::get("You have not provided the code!"));
            header("Location: /Content_Cat:Form_RecoverPassword/");
            exit;
        } elseif (!($recovery = DB::gr("passwordrecovery", array("code" => URLParser::v("code"))))) {
            Msg::err(Language::get("Your code is not valid!"));
            header("Location: /Content_Cat:Form_RecoverPassword/");
            exit;
        } elseif ($recovery["created"] < time() - 24 * 3600) {
            Msg::err(Language::get("Your code is already invalid."));
            header("Location: /Content_Cat:Form_RecoverPassword/");
            exit;
        } elseif (!($usr = DB::gr("users", array("email" => $recovery["email"])))) {
            Msg::err(Language::get("User does not exists."));
            header("Location: /Content_Cat:Form_RecoverPassword/");
            exit;
        } elseif (!$usr["id2"]) {
            Msg::err(Language::get("User does not exists."));
            header("Location: /Content_Cat:Form_RecoverPassword/");
            exit;
        } elseif ($recovery["used"]) {
            Msg::err(Language::get("This code has already been used."));
            header("Location: /Content_Cat:Form_RecoverPassword/");
            exit;
        } else {
            $this->formSettings = $form = array("table" => "users", "col" => array(array("name" => Language::get("New password"), "form" => array("type" => "password"), "data" => array("col" => "password", "var" => "newPass", "cohash" => "OFiapci@ifp##!Q-"), "usage" => array("MFi", "MFu", "MFd")), array("name" => Language::get("Repeat password"), "form" => array("type" => "password"), "data" => array("col" => "password", "var" => "newPass2", "cohash" => "OFiapci@ifp##!Q-"), "usage" => array("MFi", "MFu", "MFd")),), "where" => array("id2" => $usr["id2"],), "order" => array("od" => "asc",), "uid" => "user_settings_2", "show_export" => false, "show_filter" => false, "bootstrap" => "1", "allowInsert" => false, "allowUpdate" => true, "allowDelete" => false, "useForms" => true, "iter" => array("per_page" => "20"), "execute" => array("beforeUpdate" => "PHP::\\AsyncWeb\\DefaultBlocks\\Form\\RecoverPasswordCode::beforeUpdate", "onUpdate" => "PHP::\\AsyncWeb\\DefaultBlocks\\Form\\RecoverPasswordCode::onUpdate",),);
            $_REQUEST["user_settings_2___ID"] = $usr["id"];
            $_REQUEST["user_settings_2___UPDATE1"] = 1;
            $this->initTemplateForm();
        }
    }
    protected function initTemplateForm() {
        $ret = "<h1>" . Language::get("Recover lost password") . '</h1>';
        $form = new \AsyncWeb\View\MakeForm($this->formSettings);
        $form->BT_WIDTH_OF_LABEL = 2;
        $form->check_update();
        $ret.= $form->show($this->showType);
        $this->template = $ret;
    }
}
