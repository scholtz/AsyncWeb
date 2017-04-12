<?php
namespace AsyncWeb\DefaultBlocks\Layout;
class Footer extends \AsyncWeb\Frontend\Block {
    public static $DICTIONARY = array("sk-SK" => array("You are logged as" => "Ste prihlásený ako", "Login using Google" => "Prihláste sa cez Google", "Log out" => "Odhlásiť sa", "Set up Auth service in your settings" => "Nastavte si spôsob prihlasovania vo vašich nastaveniach",), "en-US" => array("You are logged as" => "You are logged as", "Login using Google" => "Login using Google", "Log out" => "Log out", "Set up Auth service in your settings" => "Set up Auth service in your settings",),);
    protected function initTemplate() {
        if (\AsyncWeb\Security\Auth::userId()) {
            $content = '{{You are logged as}}: ' . \AsyncWeb\Objects\User::getEmail() . ' <a href="/logout=1">{{Log out}}</a>';
        } else {
            if (\AsyncWeb\Security\Auth::serviceIsRegistered("Google")) {
                $content = '<a href="/go=Google">{{Login using Google}}</a>';
            } else {
                $content = "{{Set up Auth service in your settings}}";
            }
        }
        $this->template = '<footer><div class="well">' . $content . '</div></footer>';
    }
}
