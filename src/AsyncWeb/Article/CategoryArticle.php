<?php

namespace AsyncWeb\Article;
use AsyncWeb\Menu\MainMenu;
use AsyncWeb\Security\Auth;
use AsyncWeb\Storage\Log;
use AsyncWeb\DB\DB;
use AsyncWeb\System\Language;
use AsyncWeb\Objects\Group;
use AsyncWeb\Cache\Cache;

class CategoryArticle{
	private static $listenerCheck = array();
	public static function make($cat){
		if(!$cat){
			$cat = MainMenu::getCurrent();
		}
		if($cat === "0"){
			
			$data = \AsyncWeb\Frontend\URLParser::parse();
			if($data && $data["var"]["installmenu"]=="1"){
				MainMenu::installMenu();
			}else{
				return 'menu not installed! <a href="'.\AsyncWeb\System\Path::make(array("installmenu"=>1)).'">Install</a>';
			}
		}
		if(!$cat){
			$block = new \AsyncWeb\DefaultBlocks\Errors\E404();
			return $block->getTemplate();
			
		}
		
		if(!($check = MainMenu::checkAuth($cat))){
			
			if(Auth::userId()){
				// doslo k zobrazeniu stranky bez opravneni s prihlasenym uzivatelom
				// zaloguj to a presmeruj ho
 				Log::log("CategoryArticle","HCK: the category was displayed with logged in user without permissions to display this category!\n".print_r($cat,true),ML__TOP_PRIORITY);
				\AsyncWeb\HTTP\Header::s("location","/");
				exit;
			}else{
				//throw new \Exception("requires auth user!");
				return \AsyncWeb\Security\Auth::loginForm();
				Login::requiredLoggedIn2();
				exit;
			}
		}

		if(isset($cat["run"]) && $cat["run"]){
			  \AsyncWeb\System\Execute::run($cat["run"]);
		}
		
		CategoryArticle::check();
		
		$catId = $cat["id2"];
		$res = DB::g("articles",array("category"=>$catId),$offset=null,$count=null,$order=array("published"=>"desc","created"=>"desc"));
		$ret = "";
		if(isset($_REQUEST["RSS"]) && $_REQUEST["RSS"] = 1){
			$name = rawurldecode(str_replace("RSS=1","",$_SERVER["REQUEST_URI"]));
			$path = str_replace("RSS=1","",$_SERVER["REQUEST_URI"]);
			if(substr($name,-1)=="?") $name = substr($name,0,-1);
			if(substr($path,-1)=="?") $path = substr($path,0,-1);
			$ret = '<?xml version="1.0"?>
<rss version="2.0">
<channel>
 <title>RSS - '.$_SERVER["HTTP_HOST"].$name.'</title>
 <link>http://'.$_SERVER["HTTP_HOST"].$path.'</link>
 <description></description>
 <language>'.Language::getLang().'</language>
 <pubDate>'.date("r").'</pubDate>
';
			while($articlerow=DB::f($res)){
				//$ret.='<x>'.print_r($articlerow,true).'</x>';
				$ret.=CategoryArticle::makeArticleRSS($articlerow);
			}
			$ret.= '</channel>
</rss>';
			header("Content-Type: text/xml");
			echo $ret;
			
			exit;

		}
		
		$ret .= CategoryArticle::checkForms();
		$ret .= $forms = CategoryArticle::showForms();
		if($forms){
			$ret.= '<div><a href="?finishArticleEditing=1">'.Language::get('Koniec úpravy článkov').'</a></div>';
		}else{
			while($articlerow=DB::f($res)){
		
				$ret.=CategoryArticle::makeArticle($articlerow);
			}
		}
		if(isset(CategoryArticle::$articles[$catId]))
		foreach(CategoryArticle::$articles[$catId] as $article){
			$ret.=CategoryArticle::makeArticle($article);
		}
		
		
		if(!$ret){
			$ret = MainMenu::generateSubmenuArticle($cat);
		}
		if(!$ret){
			$ret.= Language::get('Kategória neobsahuje žiadny článok');
		}
		return $ret;
	}
	public static function makeArticle(&$articlerow){
		if(!$articlerow) return;
		if(is_a($articlerow,'\AsyncWeb\Article\ArticleInstance')){
			$obj = $articlerow;
			$articlerow = $obj->getRow();
		}
		if(@$articlerow["group"]){
			if(!Group::isInGroupId($articlerow["group"])) return;
		}
		switch($articlerow["logintype"]){
			case "logged": 
				if(!Auth::userId()) return false;
			break;
			case "notlogged": 
				if(Auth::userId()) return false;
			break;
			case "all": 
			break;
		}
		foreach(CategoryArticle::$listenerCheck as $t=>$obj){
			if($t==$articlerow["type"]) return $obj->makeArticle($articlerow);
		}
		$c1 = new \AsyncWeb\HTML\Container("article");
		$c1->setBody(Language::get($articlerow["text"]));
		return $c1->show();
	}
	
	public static function makeArticleRSS(&$articlerow){
		if(!$articlerow) return;
		if(is_a($articlerow,'\AsyncWeb\Article\ArticleInstance')){
			$obj = $articlerow;
			$articlerow = $obj->getRow();
		}
		if(@$articlerow["group"]){
			if(!Group::isInGroupId($articlerow["group"])) return;
		}
		switch($articlerow["logintype"]){
			case "logged": 
				if(!Auth::userId()) return false;
			break;
			case "notlogged": 
				if(Auth::userId()) return false;
			break;
			case "all": 
			break;
		}
		foreach(CategoryArticle::$listenerCheck as $t=>$obj){
			if($t==$articlerow["type"] && is_a($obj,'\AsyncWeb\Article\ArticleV2')) return $obj->makeArticleRSS($articlerow);
		}
		return Language::get($articlerow["text"]);
		
	}
	public static function check(){
	
		HTMLArticle::init();
		PHPArticle::init();
		
		if(isset($_REQUEST["finishArticleEditing"])) {\AsyncWeb\HTTP\Header::s("reload",array("finishArticleEditing"=>""));exit;}
	}
	public static function addListener(&$obj,$type){
		if(is_a($obj,'\AsyncWeb\Article\Article') || is_a($obj,'\AsyncWeb\Article\ArticleV2')){
			if(!isset(CategoryArticle::$listenerCheck[$type])){
				CategoryArticle::$listenerCheck[$type] = $obj;
				return true;
			}
		}
		echo "notok $type";exit;
		return false;
	}
	public static function checkForms(){
		$ret = "";
		foreach(CategoryArticle::$listenerCheck as $obj){
			$ret.=$obj->check();
		}
		return $ret;
	}
	public static function showForms(){
		$ret = "";
		foreach(CategoryArticle::$listenerCheck as $obj){
			$ret.=$obj->showForm();
		}
		return $ret;
	}
	private static $articles=array();
	public static function fillArticleBuffer($array){
		foreach($array as $obj){
			if(is_a($obj,'\AsyncWeb\Article\ArticleInstance')){
				CategoryArticle::$articles[$obj->category()][] = $obj;
			}
		}
	}
	public static function cacheArticleBuffer(){
		$k1 = "ArticlesBuffer:".Language::getLang()."_u:".Auth::userId();
		Cache::set($k1,"articles",CategoryArticle::$articles);
	}
	public static function loadArticleBuffer(){
		$k1 = "ArticlesBuffer:".Language::getLang()."_u:".Auth::userId();
		
		if($art = Cache::get($k1,"articles")){
			if(is_array($art)){
				CategoryArticle::$articles = $art;
			}
		}
		
	}
}