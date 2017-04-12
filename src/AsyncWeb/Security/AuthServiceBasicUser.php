<?php
namespace AsyncWeb\Security;
use AsyncWeb\Security\AuthService;
use AsyncWeb\Frontend\URLParser;
use AsyncWeb\DB\DB;
use AsyncWeb\System\Language;
class AuthServiceBasicUser implements AuthService {
    public function SERVICE_ID() {
        return $this->name;
    }
    protected static $DB_TABLE_USERS = "users";
    protected $info = array();
    protected $name = "BasicUserAuth";
    public function check(Array $data = array()) {
        if ($data) {
            return $this->checkData($data);
        }
        return $this->checkAuth();
    }
    public function loginForm() {
        if (function_exists("openssl_random_pseudo_bytes")) {
            $authcode = \bin2hex(\openssl_random_pseudo_bytes(5));
        } else {
            $authcode = md5(uniqid());
        }
        //$authcode = "41e2ca9549";
        $ret = '
		<div class="row">
		<div class="col-md-6">
		<div class="row">
		<div class="col-md-offset-3 col-md-9"><h4>Basic authentication</h4></div>
		<div class="col-md-12">
		<input type="hidden" id="BasicUserAuthHash" name="BasicUserAuthHash" value="' . \AsyncWeb\Storage\Session::set("__BasicUserAuthHash__", $authcode) . '">
		<form action="' . $_SERVER["REQUEST_URI"] . '" class="form-horizontal" method="post" id="BasicUserAuthForm" onsubmit="AUTH_heslo.value=sha256(sha256(\'OFiapci@ifp##!Q-\'+sha256(AUTH_heslo.value)) + BasicUserAuthHash.value);hashing.value=\'SHA256\'; return true;">
		<input type="hidden" id="__AUTHENTICATE__" name="__AUTHENTICATE__" value="1">
		<input type="hidden" id="hashing" name="hashing" value="none">
			<div class="form-group row">
			  <label for="uname9" class="col-md-3 control-label">User Name</label>
			  <div class="col-md-9">
				<input type="text" class="form-control" name="uname9" id="uname9" placeholder="Your user name">
			  </div>
			</div>
			
			<div class="form-group row">
			  <label for="AUTH_heslo" class="col-md-3 control-label">Password</label>
			  <div class="col-md-9">
				<input type="password" class="form-control" name="AUTH_heslo" id="AUTH_heslo" placeholder="Your password">
			  </div>
			</div>
			 
			<div class="form-group row">
			  <div class="col-md-offset-3 col-md-9">
				<input id="AW__LOGIN_BTN" type="submit" class="btn btn-primary" value="Log in">
			  </div>
			</div>
			
		 	<div class="form-group row">
			  <div class="col-md-offset-3 col-md-9">
				<a href="{{{url:Content_Cat:Form_RecoverPassword}}}">' . Language::get("Recover password") . '</a>
			  </div>
			</div>
		 </form>
		 
		 
		</div>
		</div>
		</div>
		<div class="col-md-6"> 
		<div class="row">
		<div class="col-md-offset-4 col-md-8"><h4>Registration</h4></div>
		<div class="col-md-12">
		 {{{Form_BasicAuthRegistration}}}
		</div>
		</div>
		</div>
		</div>
		
		';
        return $ret;
    }
    protected function checkAuth() {
        if (!empty(URLParser::v("__AUTHENTICATE__"))) {
            $row = DB::gr(AuthServiceBasicUser::$DB_TABLE_USERS, array("login" => URLParser::v("uname9")));
            if (!$row) {
                throw new \AsyncWeb\Exceptions\SecurityException(Language::get("Wrong user or password!"));
            }
            $authcode = \AsyncWeb\Storage\Session::get("__BasicUserAuthHash__");
            if (!$authcode) {
                throw new \Exception(Language::get("Session has not provided authorisation code!"));
            }
            if (URLParser::v("hashing") == "none") {
                $hash = $row["password"];
                $tocheck = hash('sha256', $row["cohash"] . hash('sha256', URLParser::v("AUTH_heslo")));
            } else {
                $hash = hash('sha256', $row["password"] . $authcode);
                $tocheck = URLParser::v("AUTH_heslo");
            }
            if ($hash == $tocheck) {
                Auth::auth(array("userid" => $row["id2"]), $this);
                \AsyncWeb\HTTP\Header::s("reload", array("__AUTHENTICATE__" => ""));
                exit;
                return true;
            }
            throw new \AsyncWeb\Exceptions\SecurityException(Language::get("Wrong user or password!"));
        }
        return false;
    }
    protected function checkData($data) {
        $row = \AsyncWeb\DB\DB::qbr(AuthServiceBasicUser::$DB_TABLE_USERS, array("where" => array("id2" => $data["userid"]), "cols" => array("id2")));
        if ($row) {
            return true;
        }
        throw new \AsyncWeb\Exceptions\SecurityException("Error occured! 0x9310591");
    }
}
