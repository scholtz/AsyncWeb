<?php
namespace AsyncWeb\DefaultBlocks\Content\Auth;
use AsyncWeb\Security\Auth;

class AuthServiceForm extends \AsyncWeb\Frontend\Block {
    public static $DICTIONARY = array(
        "sk-SK" => array(
            "Basic authentication" => "Jednoduché prihlásenie", 
            "User Name" => "Užívateľ", 
            "Your user name" => "Váš login",
            "Password" => "Heslo",
            "Your password" => "Vaše heslo",
            "Recover password" => "Obnoviť heslo",
            "Registration" => "Registrácia",
            "Log in" => "Prihlásiť sa",
            ), 
        "en-US" => array(
            "Basic authentication" => "Basic authentication", 
            "User Name" => "User Name", 
            "Your user name" => "Your user name",
            "Password" => "Password",
            "Your password" => "Your password",
            "Recover password" => "Recover password",
            "Registration" => "Registration",
            "Log in" => "Log in",
            ),);
    public function init() {
        if (function_exists("openssl_random_pseudo_bytes")) {
            $authcode = \bin2hex(\openssl_random_pseudo_bytes(5));
        } else {
            $authcode = md5(uniqid());
        }
        \AsyncWeb\Storage\Session::set("__BasicUserAuthHash__", $authcode);
        
        $this->setData([    
            "URI" => $_SERVER["HTTP_REQUEST_URI"],
            "BasicUserAuthHash"=> $authcode,
            ]);
    }
}
