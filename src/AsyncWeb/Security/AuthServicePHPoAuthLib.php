<?php

namespace AsyncWeb\Security;
use \AsyncWeb\Security\AuthService;


class AuthServicePHPoAuthLib implements AuthService{
	public function SERVICE_ID(){ return $this->name;}
	public static $TABLE_ASSOC = "users_superconnection";
	
	protected $services = array();
	protected $info = array();
	protected $name = "AuthServicePHPoAuthLib";
	protected $icon = null;
	protected $require_email = true;
	public function registerService($name,\OAuth\OAuth2\Service\AbstractService $service,$info,$faicon="",$require_email=true){
		$this->name=$name;
		$this->services[$name] = $service;
		$this->info[$name] = $info;
		if($faicon) $this->icon = $faicon;
		$this->require_email = $require_email;
	}

	public function check(Array $data=array()){
		if($data){ return $this->checkData($data);}
		return $this->checkAuth();
	}
	public function loginForm(){
		$uriFactory = new \OAuth\Common\Http\Uri\UriFactory();
		$currentUri = $uriFactory->createFromSuperGlobalArray($_SERVER);
		foreach($this->services as $service){
			$class= get_class($service);
			$class= str_replace("OAuth\\OAuth2\\Service\\","",$class);
			//$url = $currentUri->getRelativeUri() . '?go='.$class;
			
			$url = \AsyncWeb\System\Path::make(array("go"=>$class));
			$text = "Login with ".$class."!";
			$style = 'border:1px solid gray; width:60px;height:60px;display:inline-block;margin:5px; vertical-align:middle;text-align:center;font-size:50px;';
			if($this->icon){
				$text = '<div style="'.$style.'" title="Login with '.$class.'"><i style="" class="fa fa-'.$this->icon.'"></i></div>';
			}else{
				if($class=="Google"){
					$text = '<div style="'.$style.'" title="Login with '.$class.'"><i style="" class="fa fa-google"></i></div>';
				}		
				if($class=="Vkontakte"){
					$text = '<div style="'.$style.'" title="Login with '.$class.'"><i style="" class="fa fa-vk"></i></div>';
				}		
			}			
			$ret.="<a href='$url'>".$text."</a>";
		}
		
		\AsyncWeb\Storage\Session::set("oauth_path",\AsyncWeb\Frontend\URLParser::getCurrent());
		return $ret;
		
	}
	protected static $DB_TABLE_USERS = "outer_user_access";
	protected function checkAuth(){
		\AsyncWeb\Frontend\URLParser::parse();

		
		if (!empty($_GET['code'])){
			if($provider = \AsyncWeb\Storage\Session::get("oauth_provider")){
				if($provider == $this->name) {
					if(!isset($this->services[$provider])){
						throw new \AsyncWeb\Exceptions\SecurityException("oAuth provider is not registered!");
					}
					
					// This was a callback request from google, get the token
					$service = $this->services[$provider];
					$service->requestAccessToken($_GET['code']);
					// Send a request with it
					$result = json_decode($service->request($this->info[$provider]), true);
					if(!$result["email"] && $this->require_email){
						throw new \AsyncWeb\Exceptions\SecurityException("Authorisation service did not provide your email address!");
					}
					
					$result["id3"] = $result["id"];
					
					$email = $result["email"];
					if(!$email) $email = $result["id"];
					
					$id2 = md5(substr($provider."-".md5($email),0,32));
					unset($result["id"]);
					
					if($usr = \AsyncWeb\DB\DB::gr(AuthServicePHPoAuthLib::$DB_TABLE_USERS,$id2)){
						if($usr["active"] !== null && $usr["active"] != "1") throw new \AsyncWeb\Exceptions\SecurityException("Your account has been blocked! Please contact administrators.");;
					}
					
					$result["active"] = "1";
					$result["last_access"] = \AsyncWeb\Date\Time::get();
					foreach($result as $k=>$v){
						if(is_array($v)) unset($result[$k]);
					}
					\AsyncWeb\DB\DB::u(AuthServicePHPoAuthLib::$DB_TABLE_USERS,$id2,$result);
					
					
					Auth::auth(array("userid"=>$id2),$this);

					if($path =  \AsyncWeb\Storage\Session::get("oauth_path")){
						\AsyncWeb\HTTP\Header::s("location",$path);
						exit;
					}
					
					return true;
				}
			}
		}
		
		foreach($this->services as $name=>$service){
			$url = \AsyncWeb\Frontend\URLParser::parse();
			if(isset($url["var"]["go"]) && $url["var"]["go"] == $name && $url["var"]["go"] == $this->name){

				if(!\AsyncWeb\Storage\Session::get("oauth_path")){
					$path = \AsyncWeb\Frontend\URLParser::addVariables(array("go"=>null));
					\AsyncWeb\Storage\Session::set("oauth_path",$path);
				}
				
				\AsyncWeb\Storage\Session::set("oauth_provider",$name);
				$url = $service->getAuthorizationUri();
				\AsyncWeb\HTTP\Header::s("location",$url);
				exit;
			}
		}

		return false;
	}
	
	protected function checkData($data){
		$row = \AsyncWeb\DB\DB::qbr(AuthServicePHPoAuthLib::$DB_TABLE_USERS,array("where"=>array("id2"=>$data["userid"]),"cols"=>array("id2")));
		if($row){
			return true;
		}
		throw new \AsyncWeb\Exceptions\SecurityException("User is not allowed to be logged as another user! 0x8310591");
	}
	
	
	
}
