<?php

namespace AsyncWeb\Article;
use AsyncWeb\Objects\Group;
use AsyncWeb\HTML\Container;
use AsyncWeb\System\Language;
use AsyncWeb\Storage\Session;
use AsyncWeb\HTTP\Header;
use AsyncWeb\View\MakeForm;
use AsyncWeb\Date\Time;
use AsyncWeb\Menu\MainMenu;
use AsyncWeb\IO\File;
use AsyncWeb\Storage\Log;


class PHPArticle implements ArticleV2{
	private $form = null;
	private $show = false;
	private $editor = false;
	private function __construct(){		
		CategoryArticle::addListener($this,"php");
		if(Group::is_in_group("PHPEditor")){
			
			if(isset($_REQUEST["newphparticle"])) {Session::set("newphparticle","1");Header::s("reload",array("newphparticle"=>""));exit;}
			if(isset($_REQUEST["finishArticleEditing"])) {Session::set("newphparticle","0");}
			$this->show = Session::get("newphparticle");
			if($this->show){
				$form = array(
					 "table" => "articles",
					 "col" => array(
					 array("name"=>"Time","form"=>array("type"=>"textbox"),"data"=>array("col"=>"od","datatype"=>"date"),"filter"=>array("type"=>"date","format"=>"d.m.Y H:i:s"),"usage"=>array("DBVs","DBVe")),
					 array("name"=>"Text","form"=>array("type"=>"textbox"),"data"=>array("col"=>"text","dictionary"=>true),"usage"=>array("MFi","MFu","MFd")),
					 array("form"=>array("type"=>"value"),"data"=>array("col"=>"type"),"texts"=>array("text"=>"php"),"usage"=>array("MFi","MFu","MFd")),
					 array("form"=>array("type"=>"value"),"data"=>array("col"=>"category"),"texts"=>array("text"=>"PHP::MainMenu::getCurrentId()"),"usage"=>array("MFi","MFu","MFd")),
					 array("form"=>array("type"=>"value"),"data"=>array("col"=>"created"),"texts"=>array("text"=>"PHP::Time::get()"),"usage"=>array("MFi","MFu","MFd")),
					 array("name"=>"Logintype","form"=>array("type"=>"select"),"data"=>array("col"=>"logintype","datatype"=>"enum"),"filter"=>array("type"=>"option","option"=>array("all"=>"Všetci vidia obsah","notlogged"=>"Iba neprihlásení","logged"=>"Prihlásení")),"usage"=>array("MFi","MFu","MFd")),
					 array(
						"name"=>"Group",
						"form"=>array("type"=>"selectDB"),
						"data"=>array(
						    "col"=>"group",
							"allowNull"=>true,
							"fromTable"=>"groups",
							"fromColumn"=>"name",
							"dictionary"=>true,
						),
						"texts"=>array(
							"nullValue"=>"Vyber",
						),
						"usage"=>array("MFi","MFu","MFd")),
					 
					),
					"where"=>array(
					 "type"=>"php",
					 "category"=>"PHP::MainMenu::getCurrentId()",
					),

					"order" => array(
					 "od"=>"asc",
					 ),

					 "uid"=>"articles_php",

					 "show_export"=>true,"show_filter"=>true,

					 "allowInsert"=>true,"allowUpdate"=>true,"allowDelete"=>true,"useForms"=>true,
					 
					 "rights"=>array("insert"=>"PHPEditor","update"=>"PHPEditor","delete"=>"PHPEditor",),
					 
					 "execute"=>array(
					  "onInsert"=>"PHP::PHPArticle::onInsert",
					  "onUpdate"=>"PHP::PHPArticle::onUpdate",
					  "onDelete"=>"PHP::PHPArticle::onDelete",
					 ),
					 
					 "iter"=>array("per_page"=>"20"),
					 
				);
				
			
				$this->form = new MakeForm($form);
			}
			$this->editor = true;
		}
	}
	private static $inst = null;
	public static function init(){
		if(PHPArticle::$inst == null) PHPArticle::$inst = new PHPArticle();
	}
	public function check(){
		if(!$this->form) return false;
		return $this->form->show_results();
	}
	public function showForm(){
		if(!$this->form) return false;
		return $this->form->show("ALL");
	}
	public function makeArticleRSS(&$articlerow){
		
		return ' <item>
  <guid>'.md5($articlerow["id2"]."-ajfskajf").'</guid>
  <title>'.Language::get("Script article").'</title>
  <link>http://'.$_SERVER["HTTP_HOST"].str_replace("RSS=1","",$_SERVER["REQUEST_URI"]).'</link>
  <description>'.Language::get("Script article has been published. Content of the article is not available in RSS.").'</description>
  <pubDate>'.date("r",Time::getUnix($articlerow["created"])).'</pubDate>
 </item>
';
	}
	public function makeArticle(&$articlerow){
		$include_return = "";
		$ret = "";
		
		$file = "php/".Language::get($articlerow["text"]).".php";
		$file2 = "cebphp/".Language::get($articlerow["text"]).".php";
		if(is_file($file)){//najskor skontroluj lokalne
			include $file;
		}elseif(File::exists($file2)){// potom skontroluj standardny modul
			include $file2;
		}elseif(File::exists($file)){// potom skontroluj inde
			include $file;
		}else{
			Log::log("CatergoryArticle","PHP Article not found: ".$file,ML__HIGH_PRIORITY);
			$include_return = '<div class="error">'.Language::get('PHP Article error!').'</div>';
		}
		$include_return .= $ret;
		$c1 = new Container("article");
		if($include_return) $c1->setBody($include_return);
		
		if($this->editor && (MainMenu::$editingmenu || MainMenu::$editingart) && isset($articlerow["id"])){

		$c1->appendBody(
'<div class="editarticle">
<a href="?articles_php___UPDATE1=1&amp;articles_php___ID='.$articlerow["id"].'&newphparticle=1">'.Language::get("L__Edit_article").'</a>
|
<a onclick="confirm(\''.Language::get('L__Delete_article_confirm').'\')?ret=true:ret=false;return ret;" href="?articles_php___DELETE=1&amp;articles_php___ID='.$articlerow["id"].'&newphparticle=1">'.Language::get('L__Delete_article').'</a>
</div>');
		}
	return $c1->show();
	}
	public static function onInsert($articlerow){
		
		Session::set("newphparticle","0");
	}
	public static function onUpdate($r){
		
		Session::set("newphparticle","0");
	}
	public static function onDelete($r){
		
		Session::set("newphparticle","0");
	}
}


?>