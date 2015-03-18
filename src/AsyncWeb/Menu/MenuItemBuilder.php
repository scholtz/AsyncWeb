<?php
namespace AsyncWeb\Menu;

class MenuItemBuilder{
	public static function get($id2,$text,$path,$visible="1",$langs=array(),$run=""){
		if(!$langs){
			foreach(MainMenu::getLangs() as $k=>$v){
				$langs[$k] = $path;
			}
		}
		return array("id2"=>$id2,"path"=>$path,"title"=>$text,"text"=>$text,"type"=>"category","visible"=>$visible,"class"=>"","logintype"=>"all","group"=>null,"run"=>$run,"style"=>"standard","id"=>"","langs"=>$langs);
	}
}
