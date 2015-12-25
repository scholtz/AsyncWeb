<?php
namespace AsyncWeb\Menu;
use AsyncWeb\Menu\MainMenu;
use AsyncWeb\Menu\MenuItemBuilder;
use AsyncWeb\Objects\Group;
use AsyncWeb\Cache\Cache;
use AsyncWeb\System\Path;
use AsyncWeb\Storage\Session;
use AsyncWeb\DB\DB;
use AsyncWeb\Menu\MenuBuilder;
use AsyncWeb\System\Language;
use AsyncWeb\Security\Auth;
use AsyncWeb\Frontend\URLParser;

/*

Setup use of DBMenu5 in the settings file:


\AsyncWeb\Menu\MainMenu::registerBuilder(new \AsyncWeb\Menu\DBMenu5(),-99);


/**/

class DBMenu5 extends MenuBuilder{
	public static $USES = false;
	public function __construct(){
		DBMenu5::$USES = true;
	}
	private function spracuj(&$row){		
		if(!$row) return $row;
		foreach(MainMenu::getLangs() as $k=>$lang){
			$row["langs"][$k] = Language::get($row["path"],array(),$k);
			
			if(substr($row["langs"][$k],0,7) == "http://"){
			
			}else{
				//if(substr($row["langs"][$k],0,1) != "/") $row["langs"][$k] = "/".$row["langs"][$k];
				//if(strpos($row["langs"][$k],"?") === false && substr($row["langs"][$k],-1) != "/") $row["langs"][$k] = $row["langs"][$k]."/";
			}
		}
		
		
		$row["path"] = $row["langs"][Language::getLang()];

		$row["text"] = Language::get($row["text"]);
		$row["imgalt"] = Language::get($row["text"]);
		
		if($row["visible"]) $row["visible"] = Language::get($row["visible"]);
		$row["title"] = Language::get($row["title"]);
		$row["description"] = Language::get($row["description"]);
		$row["keywords"] = Language::get($row["keywords"]);
		
	}
	public function makeTopMenu(&$menu,$top=null,$showeditor=true){
		$showeditor = MainMenu::$editingmenu;
		if(!Group::is_in_group("MenuEditor")) $showeditor = false;

		
		if(!$top){
			$key = "TopMenu_l:".Language::getLang()."_t:${top}_e:${showeditor}_u:".Auth::userId()."_p:".MainMenu::$PAGE;
			if($menu = Cache::get($key,"menu")){
				return $menu;
			}
		}
		$menu2 = array();
		if($top===null){
			$res3 = DB::g("menu",array(array("col"=>"parent","op"=>"is","value"=>null),"page"=>MainMenu::$PAGE),null,null,array("order"=>"asc"));
		}else{
			$res3 = DB::g("menu",array(array("col"=>"parent","op"=>"eq","value"=>$top),"page"=>MainMenu::$PAGE),null,null,array("order"=>"asc"));
		}
		
		$sub = "";
		$i = 0;
		while($row = DB::f($res3)){$i++;
			$this->spracuj($row);
			$ret1 = $this->showMenuItem($row);

			if(!$ret1) continue;
			$row["submenu"] = $this->makeTopMenu($menu,$row["id2"],$showeditor);
			$order = $row["order"];
			while(isset($menu2[$order*100])){
				$order++;
			}
			
			
				if($showeditor){
					if(!$row["submenu"]){
						$row["submenu"][1] = MenuItemBuilder::get(md5(uniqid()),Language::get('New submenu'),Path::make(array("insert_data_dbmenu5_edit"=>"1","addmenuitemsub"=>$row["id2"]),false,"?"));
					}
					
					//$menu2[$order*100-2] = MenuItemBuilder::get(md5(uniqid()),"&lt;*",Path::make(array("insert_data_dbmenu5_edit"=>"1","addmenuitembefore"=>$row["id2"]),false,"?"));
					if(!$row["path"] || !$row["text"]) $menu2[$order*100-1] = MenuItemBuilder::get(md5(uniqid())," !! ",Path::make(array("dbmenu5_edit___ID"=>$row["id"],"dbmenu5_edit___UPDATE1"=>"1")));
				}		
				$menu2[$order*100] = $row;
			//
				if($showeditor){ $menu2[$order*100+1] = MenuItemBuilder::get(md5(uniqid()),'<i class="fa fa-plus-circle"></i>',Path::make(array("insert_data_dbmenu5_edit"=>"1","addmenuitemafter"=>$row["id2"]),false,"?"));}
				
			
		}
		//exit;
		if(!$top){
			Cache::set($key,"menu",$menu2);
		}
		return $menu2;
	}
	private function showMenuItem(&$row){
		if(Group::is_in_group("MenuEditor") && MainMenu::$editingmenu) return true;
		if(isset($row["visible"]) && !$row["visible"]) return false;
		
		switch($row["logintype"]){
				case "logged": 
					if(!\AsyncWeb\Security\Auth::userId()){
						if(Language::get($row["visible"]) != "all") $row["visible"] = false;
					}
				break;
				case "notlogged": 
					if(\AsyncWeb\Security\Auth::userId()){
						$row["visible"] = false;
					}
				break;
				case "all": 
				break;
		}
		if(@$row["group"]){
			if(!Group::isInGroupId($row["group"])){
				$row["visible"] = false;
				//return false;
			}
		}
		
		return true;
	}
	public function makeLeftMenu(&$menu){
		return $this->makeTopMenu($menu,null,false);
	}
	public function makeNavigator(&$menu){
		$key = "Nav_l:".Language::getLang()."_u:".Auth::userId()."_p:".MainMenu::$PAGE;
		if($menu = Cache::get($key,"menu")){
			return $menu;
		}

	
	
		$menu2 = array();
		
		$cur = $this->getCurrent();
		
		$done=array();
		$pathvis = false;
		
		while($cur){
			if(@$done[$cur["id2"]]) break; $done[$cur["id2"]]=true;
			
			if($cur["id2"]==md5("/")) $pathvis = true;
			$this->spracuj($cur);

			array_unshift($menu2,$cur);
			$cur = DB::gr("menu",$cur["parent"]);
		}
		
		if(!$pathvis){
			$row = DB::gr("menu",array("id2"=>$id2=md5("/"),"page"=>MainMenu::$PAGE));

			$this->spracuj($row);
			array_unshift($menu2,$row);
		}
		Cache::set($key,"menu",$menu2);
		return $menu2;
	}
	public static $addSlashOnEnd = false;
	public function getCurrent(){
		return false;
		$cura = explode("?",$_SERVER["REQUEST_URI"]);
		$cur = urldecode($cura[0]);
		if(substr($cur,0,1) == "/"){
			$cur = substr($cur,1);
		}
		
		if(substr($cur,-1) == "/"){
			$cur = substr($cur,0,-1);
		}
		
		
		if(!$cur){
			$menu= DB::gr("menu",array("id2"=>md5("/"),"page"=>MainMenu::$PAGE));
			return $menu;
		}
		
		$menu = "";
		$vals = Language::db_dict_find_by_value($cur);
		
		foreach($vals as $val){
			$menu = DB::gr("menu",array("path"=>$val,"page"=>MainMenu::$PAGE));
			if($menu) break;
		}
		
		if(!$menu){
			$vals = Language::db_dict_find_by_value("/".$cur);
			foreach($vals as $val){
				$menu = DB::gr("menu",array("path"=>$val,"page"=>MainMenu::$PAGE));
				if($menu) break;
			}
		}
		if(!$menu){
			$vals = Language::db_dict_find_by_value("/".$cur."/");
			foreach($vals as $val){
				$menu = DB::gr("menu",array("path"=>$val,"page"=>MainMenu::$PAGE));
				if($menu) break;
			}
		}
					
		

		
		$cur = substr($cur,0,strripos($cur, '/')); 
		
		while(!$menu && $cur){
			$val = Language::db_dict_find_by_value($cur);
			foreach($vals as $val){
				$menu = DB::gr("menu",array("path"=>$val,"page"=>MainMenu::$PAGE));
				if($menu) break;
			}
			
			$pos = strripos($cur, '/');
			if(!$pos) {$cur = "";}else{
				$cur = substr($cur,0,strripos ($cur, '/')); 
			}
		}
		
		
		
		
		if($menu){
			if($menu["logintype"] == "logged"){
				global $menurecurs;
				if(!$menurecurs){
					$menurecurs ++;
					//TODO Login::requiredLoggedIn2();
				}
			}
		}
		if(isset($menu["id2"])){
			$m = array();
			$m = $this->makeTopMenu($m);
			if($ret = $this->findMenuItem($m,$menu["id2"])){
				return $ret;
			}
		}
		return $menu;
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
		if(!MainMenu::$editingmenu) return false;
		if($this->checking) return false;
		$this->checking = true;
		
				

		if(!Group::is_in_group("MenuEditor")) return;

		if(URLParser::v("closeMenuEditor") == "1"){
			Session::set("menuEditing","0");
			\AsyncWeb\HTTP\Header::s("reload",array("closeMenuEditor"=>null,"addmenuitemsub"=>null,"addmenuitembefore"=>null,"addmenuitemafter"=>null,"insert_data_dbmenu5_edit"=>null,"editmenu"=>null));
			exit;
		}
		
		if((@URLParser::v("addmenuitemsub") || @URLParser::v("addmenuitembefore") || @URLParser::v("addmenuitemafter") || @URLParser::v("editmenu") || @URLParser::v("deletemenu")) || Session::get("menuEditing")){
			$addmenutype = false;
			
			if(@URLParser::v("addmenuitemsub")){
				$addmenutype = "addmenuitemsub";
			}
			if(@URLParser::v("addmenuitembefore")){
				$addmenutype = "addmenuitembefore";
			}
			if(@URLParser::v("addmenuitemafter")){
				$addmenutype = "addmenuitemafter";
			}
			Session::set("menuEditing","1");
			
			if($addmenutype){
				Session::set("addmenutype",$addmenutype);
				Session::set("addmenuvalue",URLParser::v($addmenutype));
			}
			
				$form = array(
					 "table" => "menu",
					 "col" => array(
					 array("name"=>"Path","form"=>array("type"=>"textbox"),"data"=>array("col"=>"path","dictionary"=>true),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe")),
					 array("name"=>"Text","form"=>array("type"=>"textbox"),"data"=>array("col"=>"text","dictionary"=>true),"usage"=>array("MFi","MFu","MFd")),
					 array("name"=>"FA Icon","form"=>array("type"=>"textbox"),"data"=>array("col"=>"fa"),"usage"=>array("MFi","MFu","MFd")),
					 array("name"=>"ID3","form"=>array("type"=>"textbox"),"data"=>array("col"=>"id3"),"usage"=>array("MFu","MFd")),
					 array(
						"name"=>"Parent",
						"form"=>array("type"=>"selectDB"),
						"data"=>array(
						    "col"=>"parent",
							"allowNull"=>true,
							"fromTable"=>"menu",
							"fromColumn"=>"path",
							"dictionary"=>true,
							"where"=>array("page"=>MainMenu::$PAGE),
						),
						"texts"=>array(
							"nullValue"=>"Vyber","default"=>"PHP::\AsyncWeb\Menu\DBMenu5::getUpperCategory()"
						),
						"usage"=>array("MFi","MFu","MFd")),

					array("name"=>"Order","form"=>array("type"=>"textbox"),"texts"=>array("default"=>"PHP::\AsyncWeb\Menu\DBMenu5::getInsertDefaultOrder()"),"data"=>array("col"=>"order","datatype"=>"number","minnum"=>-100000,"maxnum"=>10000000),"filter"=>array("type"=>"number_format","decimal"=>"0","desat_oddelocac"=>",","oddelovac_tisicov"=>"."),"usage"=>array("MFi","MFu","MFd")),
					array("name"=>"Visible","form"=>array("type"=>"checkbox"),"texts"=>array("default"=>"1"),"data"=>array("col"=>"visible","dictionary"=>true),"usage"=>array("MFi","MFu","MFd")),
					array("name"=>"Style","form"=>array("type"=>"textbox"),"texts"=>array("default"=>"standard"),"data"=>array("col"=>"style"),"usage"=>array("MFu","MFd")),
					array("name"=>"Title","form"=>array("type"=>"textbox"),"data"=>array("col"=>"title","dictionary"=>true),"usage"=>array("MFi","MFu","MFd")),
					array("name"=>"Description","form"=>array("type"=>"textbox"),"data"=>array("col"=>"description","dictionary"=>true),"usage"=>array("MFi","MFu","MFd")),
					array("name"=>"Keywords","form"=>array("type"=>"textbox"),"data"=>array("col"=>"keywords","dictionary"=>true),"usage"=>array("MFi","MFu","MFd")),
					array("name"=>"Type","form"=>array("type"=>"select"),"data"=>array("col"=>"type","datatype"=>"enum"),"filter"=>array("type"=>"option",
						"option"=>array(
							"category"=>Language::get("Category"),
							"image"=>Language::get("Picture"),
							"src"=>Language::get("Link"),
							"text"=>Language::get("Text"))),"usage"=>array("MFu","MFd")),
					array("name"=>"Path to picture","form"=>array("type"=>"textbox"),"data"=>array("col"=>"img"),"usage"=>array("MFu","MFd")),
					array("name"=>"Alt. Text","form"=>array("type"=>"textbox"),"data"=>array("col"=>"imgalt","dictionary"=>true),"usage"=>array("MFu","MFd")),
					array("name"=>"Picture height","form"=>array("type"=>"textbox"),"data"=>array("col"=>"imgheight"),"usage"=>array("MFu","MFd")),
					array("name"=>"Picture width","form"=>array("type"=>"textbox"),"data"=>array("col"=>"imgwidth"),"usage"=>array("MFu","MFd")),
					array("name"=>"Class","form"=>array("type"=>"textbox"),"data"=>array("col"=>"class"),"usage"=>array("MFu","MFd")),
					array("name"=>"Logintype","form"=>array("type"=>"select"),"data"=>array("col"=>"logintype","datatype"=>"enum"),"filter"=>array("type"=>"option","option"=>array("all"=>"Everyone see the category","notlogged"=>"Category is visible only to Unauthenticated","logged"=>"Category is visible only to Authenticated")),"usage"=>array("MFi","MFu","MFd")),
					array(
						"name"=>"Group",
						"form"=>array("type"=>"selectDB"),
						"data"=>array(
							"dictionary"=>true,
						    "col"=>"group",
							"allowNull"=>true,
							"fromTable"=>"groups",
							"fromColumn"=>"name",
						),
						"texts"=>array(
							"nullValue"=>"Select",
						),
						"usage"=>array("MFi","MFu","MFd")),
					 array("form"=>array("type"=>"value"),"data"=>array("col"=>"page"),"texts"=>array("text"=>"PHP::\AsyncWeb\Menu\DBMenu5::getPage()"),"usage"=>array("MFi","MFu","MFd")),
					 array("form"=>array("type"=>"value"),"data"=>array("col"=>"type"),"texts"=>array("value"=>"category"),"usage"=>array("MFi",)),
					 
					),
					"where"=>array("page"=>MainMenu::$PAGE),
					"order" => array(
					 "od"=>"asc",
					 ),
					
					 "uid"=>"dbmenu5_edit",

					 "show_export"=>true,"show_filter"=>true,

					 "allowInsert"=>true,"allowUpdate"=>true,"allowDelete"=>true,"useForms"=>true,
					 
					 "rights"=>array("insert"=>"MenuEditor","update"=>"MenuEditor","delete"=>"MenuEditor",),
					 
					 "execute"=>array(
					  "onInsert"=>"PHP::\AsyncWeb\Menu\DBMenu5::onInsert",
					  "onUpdate"=>"PHP::\AsyncWeb\Menu\DBMenu5::onUpdate",
					  "onDelete"=>"PHP::\AsyncWeb\Menu\DBMenu5::onDelete",
					 ),
					 
					 "iter"=>array("per_page"=>"20"),
					 
				);
				$row = DB::gr("menu",array("page"=>MainMenu::$PAGE));
				if($row["type"] != "image"){
					foreach($form["cols"] as $k=>$col){
						if(isset($col["data"]["col"])){
							switch($col["data"]["col"]){
								case "img":
								case "imgalt":
								case "imgheight":
								case "imgwidth":
								unset($form["cols"][$k]);
								break;
							}
						}
					}
				}
			
			$form = new \AsyncWeb\View\MakeForm($form);
			

			$ret = '<h1>'.Language::get("Menu editor").'</h1><div><a href="'.Path::make(array("closeMenuEditor"=>"1")).'">'.Language::get("Finish editing menu").'</a></div>';
			
			$ret .=$form->show_results();
			$ret .=$form->show("ALL");
			/*
			require_once("modules/Template.php");
			echo Template::setTemplate(array("body"=>$ret));exit;/**/
			
			if($block = \AsyncWeb\Frontend\BlockManagement::get("Cat")){
				$block->setTemplate($ret);
			}
			
		}
	}
	public static function getPage(){
		return MainMenu::$PAGE;
	}
	public static function getUpperCategory(){
		switch(Session::get("addmenutype")){
			case "addmenuitemsub": return Session::get("addmenuvalue");
			case "addmenuitembefore": 
			case "addmenuitemafter":
				$menuv = Session::get("addmenuvalue");
				$row = DB::gr("menu",$menuv);
				return $row["parent"];
		}
		return null;
	}
	public static function getInsertDefaultOrder(){
		switch(Session::get("addmenutype")){
			case "addmenuitemsub": return 10;
			case "addmenuitembefore":
				$menuv = Session::get("addmenuvalue");
				$row = DB::gr("menu",$menuv);
				if(!$row) return 10;
				return $row["order"];
			case "addmenuitemafter":
				$menuv = Session::get("addmenuvalue");
				$row = DB::gr("menu",$menuv);
				return $row["order"]+1;
		}
		return 10;
	}
	public static function onInsert($r){
		Session::set("menuEditing","0");
		$row = $r["row"];
		$parent = DB::myAddSlashes($row["parent"]);
		$order  = DB::myAddSlashes($row["order"]);
		$id  = DB::myAddSlashes($row["id"]);
		$p = "`parent` = '$parent' ";
		if(!$parent){
			$p = "`parent` is null "; 
		}
		DB::query($q="update menu set `order` = `order` + 10 where $p and `order` >= '$order' and do = 0 and id != '$id'");
		
		Cache::invalidate("menu");
		\AsyncWeb\HTTP\Header::s("reload",array("closeMenuEditor"=>null,"addmenuitemsub"=>null,"addmenuitembefore"=>null,"addmenuitemafter"=>null,"insert_data_dbmenu5_edit"=>null,"dbmenu5_edit___INSERT"=>null));
		exit;
	}
	public static function onUpdate($r){
		Session::set("menuEditing","0");
		Cache::invalidate("menu");
		$path = Language::get($r["new"]["path"]);
		if(substr($path,0,1) != "/") $path = "/".$path;
		if(substr($path,-1) != "/") $path = $path."/";
		\AsyncWeb\HTTP\Header::s("reload",array("closeMenuEditor"=>null,"addmenuitemsub"=>null,"addmenuitembefore"=>null,"addmenuitemafter"=>null,"dbmenu5_edit___ID"=>null,"dbmenu5_edit___UPDATE1"=>null,"dbmenu5_edit___UPDATE2"=>null,"editmenu"=>null));
		exit;
	}
	public static function onDelete($r){
		Cache::invalidate("menu");
		Session::set("menuEditing","0");
		\AsyncWeb\HTTP\Header::s("reload",array("closeMenuEditor"=>null,"addmenuitemsub"=>null,"addmenuitembefore"=>null,"addmenuitemafter"=>null,"deletemenu"=>null));
		exit;
	}
	public function export($item = null){
		if($item == null){
			$res = DB::qb("menu",array("where"=>array(array("col"=>"parent","op"=>"is","value"=>null),"page"=>MainMenu::$PAGE)));
		}else{
			$res = DB::qb("menu",array("where"=>array("parent"=>$item,"page"=>MainMenu::$PAGE)));
		}
//		var_dump($item);
//		vaR_dump(DB::num_rows($res));
		$ret = "";
		
		while($row = DB::f($res)){
			$ret.='<menu>'."\n";
			foreach($row as $k=>$v){
				if($k == "id") continue;
//				if($k == "id2") continue;
				if($k == "od") continue;
				if($k == "do") continue;
				if($k == "lchange") continue;
				$k = htmlspecialchars($k);
				
				switch($k){
					case "id":
					case "od":
					case "do":
					case "edited_by":
					case "lchange":
					break;
					case "path":
					case "text":
					case "title":
					case "description":
					case "keywords":
					case "imagealt":
						$ret.="	<$k id=\"".htmlspecialchars($v)."\">"."\n";
						foreach(MainMenu::getLangs() as $lang=>$s){
							$ret.="		<lang xml:lang=\"$lang\">".htmlspecialchars(Language::get($v,array(),$lang))."</lang>\n";
						}
						$ret.="	</$k>\n";
						
					break;
					default:
						$ret.="	<$k>".htmlspecialchars($v)."</$k>\n";
					break;
				}
			}
			$ret.='</menu>'."\n";
			$ret.=$this->export($row["id2"]);
		}
		return $ret;
	}
	public static function import($xml){
		$dom = new DomDocument();
		$dom->loadXML($xml);
		$xpath=new DomXpath($dom);
		foreach($xpath->query("//menu") as $node){
			$upd = array();
			
			foreach($xpath->query("node()",$node) as $node2){
				if($node2->nodeName == "#text") continue;
				$k = trim($node2->nodeName);
				$v = trim($node2->nodeValue);
				if(!$v) $v = null;
				$l = 0;
				foreach($xpath->query("lang",$node2) as $node3){$l++;
					$v2 = trim($node3->nodeValue);
					$id = $node2->getAttribute("id");
					$lang = $node3->getAttribute("xml:lang");
					if(!$id) $id = "DBL_".md5(uniqid());
					
					$upd[$k] = $id;
					//echo "saving: $id,$v2,$lang\n";
					Language::set($id,$v2,$lang);
				}
				if(!$l){
					$upd[$k] = $v;
				}
			}
			if(MainMenu::$PAGE) $upd["page"] = MainMenu::$PAGE;
			
			if(isset($upd["id2"]) && $upd["id2"]){
				$id2 = $upd["id2"];
				$r = DB::gr("menu",$id2);
				if($r){
					foreach($upd as $k=>$v){
						if($r[$k] === $v){
							unset($upd[$k]);
						}
					}
				}
				if($upd){
					DB::u("menu",$id2,$upd);
				}
			}else{
				DB::u("menu",md5(uniqid()),$upd);
			}
		}
	}

	public function installDefaultValues(){
			$path = "Main";$id2 = md5(MainMenu::$PAGE."-".$path);Language::set($k=md5(uniqid()),$path);$path=$k;
			Language::set($kt=md5(uniqid()),"Main page");
			Language::set($kd=md5(uniqid()),"");
			Language::set($kk=md5(uniqid()),"");
			Language::set($kv=md5(uniqid()),"1");
			Language::set($ktext=md5(uniqid()),"Main page");
			\AsyncWeb\DB\DB::u("menu",$id2,array("page"=>MainMenu::$PAGE,"path"=>$path,"parent"=>null,"order"=>"1000","text"=>$ktext,"domain"=>MainMenu::getDomain(),"visible"=>$kv,"style"=>"standard","type"=>"category","logintype"=>"all","title"=>$kt,"description"=>$kd,"keywords"=>$kk));
			\AsyncWeb\DB\DB::u("articles",md5(uniqid()),array("text"=>Language::set("{{{Main}}}"),"type"=>"html","category"=>$id2,"published"=>\AsyncWeb\Date\Time::get(),"created"=>\AsyncWeb\Date\Time::get(),"logintype"=>"all","group"=>null,));
			
				
	}
}

