<?php
namespace AsyncWeb\Menu;

class MenuItemBuilder{
	public static $DEFAULT_TYPE = "category";
	public static function get($id2,$text,$path,$visible="1",$langs=array(),$run="",$fa=""){
		if(!$langs){
			foreach(MainMenu::getLangs() as $k=>$v){
				$langs[$k] = $path;
			}
		}
		return array("id2"=>$id2,"path"=>$path,"title"=>$text,"text"=>$text,"type"=>MenuItemBuilder::$DEFAULT_TYPE,"visible"=>$visible,"class"=>"","logintype"=>"all","group"=>null,"run"=>$run,"style"=>"standard","id"=>"","langs"=>$langs,"fa"=>$fa);
	}
}
