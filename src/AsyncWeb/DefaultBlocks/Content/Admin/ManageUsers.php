<?php
namespace AsyncWeb\DefaultBlocks\Content\Admin;
use AsyncWeb\System\Language;

class ManageUsers extends \AsyncWeb\Frontend\Block{
	public static $DICTIONARY = array(
		"sk-SK"=>array(
			"Users"=>"Užívatelia",
			"L__Loginname"=>"Email užívateľa",
			"L__active"=>"Aktívny",
			"Zablokovaný"=>"Zablokovaný",
			"Aktívny"=>"Aktívny",
			"L__LastAccess"=>"Posledné prihlásenie",
			"L__Groups"=>"Počet skupín",
		),
		"en-US"=>array(
			"Users"=>"Users",
			"L__Loginname"=>"User email",
			"L__active"=>"Active",
			"Zablokovaný"=>"Blocked",
			"Aktívny"=>"Active",
			"L__LastAccess"=>"Last access",
			"L__Groups"=>"Group count",
		),
	);
	public static function countGroups($row){
		$r = \AsyncWeb\DB\DB::qbr("users_in_groups",array("where"=>array("users"=>$row["row"]["id2"]),"cols"=>array("c"=>"count(id2)")));
		return $r["c"];
	}
	protected function initTemplate(){
		$this->template = "<h1>{{Users}}</h1>";
		
		
		$form = array(
			"table" => "outer_user_access",
			"col" => array(
			 array("name"=>"{{L__Loginname}}","form"=>array("type"=>"textbox"),"data"=>array("col"=>"email"),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe")),
			 array("name"=>"{{L__LastAccess}}","form"=>array("type"=>"textbox"),"data"=>array("col"=>"last_access","datatype"=>"date"),"usage"=>array("DBVs","DBVe")),
			 array("name"=>"{{L__active}}","data"=>array("col"=>"active"),"form"=>array("type"=>"select"),"filter"=>array("type"=>"option","option"=>array(
			   "0"=>"{{Zablokovaný}}",
			   "1"=>"{{Aktívny}}",
			   ),),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe")),
			 array("name"=>"{{L__Groups}}","virtual"=>true,"filter"=>array("type"=>"php","function"=>"PHP::\AsyncWeb\DefaultBlocks\AdminManageUsers::countGroups()"),"data"=>array("datatype"=>"number"),"usage"=>array("DBVs","DBVe")),

			),
			"order" => array(
			 "od"=>"asc",
			 ),
					
			"uid"=>"admin_users_ext",
			"show_export"=>true,"show_filter"=>true,

			"allowInsert"=>true,"allowUpdate"=>true,"allowDelete"=>true,"useForms"=>true,
			"rights"=>array("insert"=>"admin","update"=>"admin","delete"=>"admin",),
					 
		    "iter"=>array("per_page"=>"20"),
					 
			);
			$form = new \AsyncWeb\View\MakeForm($form);
			
			$ret .=$form->show_results();
			$ret .=$form->show("ALL");
		
		$this->template.=$ret;
	}
}