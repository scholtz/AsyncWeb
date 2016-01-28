<?php
namespace AsyncWeb\Menu;
use AsyncWeb\Menu\MenuBuilder;


class ObjectMenuBuilder extends MenuBuilder{
	public static $USES = false;
	public function __construct(){
		ObjectMenuBuilder::$USES = true;
	}
	protected static $menu = array();
	public static function register(Array $menuItem){
		ObjectMenuBuilder::$menu[] = $menuItem;
	}
	protected static $providers = array();
	protected static $providersLoaded = false;
	public static function registerProvider($provider){
		ObjectMenuBuilder::$providers[] = $provider;
	}
	public function loadProviders(){
		foreach(ObjectMenuBuilder::$providers as $provider){
			
			$provider::Build();
		}
		ObjectMenuBuilder::$providersLoaded = true;
	}
	public function makeTopMenu(&$menu,$top=null,$showeditor=true){
		if(!ObjectMenuBuilder::$providersLoaded){
			$this->loadProviders();
		}
		return ObjectMenuBuilder::$menu;
	}
	public function makeLeftMenu(&$menu){
		if(!ObjectMenuBuilder::$providersLoaded){
			$this->loadProviders();
		}
		return $this->makeTopMenu($menu,null,false);
	}
	public function makeNavigator(&$menu){
		if(!ObjectMenuBuilder::$providersLoaded){
			$this->loadProviders();
		}
		$menu2 = array();
		$cur = $this->getCurrent();
		return $cur;
	}
	
	public function getCurrent(){
		return false;
	}
	private $checking = false;
	private function findMenuItem(&$menu,$findMe){
		foreach($menu as $k=>$v){
			if(isset($v["id2"]) && $v["id2"] == $findMe) return $v;
			if(isset($v["submenu"])){
				if($ret = $this->findMenuItem($v["submenu"],$findMe)){
					return $ret;
				}
			}
		}
	}
	public function check(){
		
	}
	public function installDefaultValues(){
		
	}
}

