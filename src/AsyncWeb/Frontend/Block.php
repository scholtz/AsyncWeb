<?php
namespace AsyncWeb\Frontend;

class Block{
	public static $TEMPLATES_PATH = "../templates/";
	public static $BLOCK_PATH = "../templates/";
	public static $DICTIONARY = array();
	protected static $i = 1;
	protected static $MustacheEngine = null;
	protected $template = "";
	protected $data = array();
	protected $tid = "";
	protected $clients = array();
	protected $usesparams = array();
	public function getUsesParams(){
		return $this->usesparams;
	}
	
	public static $BLOCKS_PATHS = array("\\AsyncWeb\\DefaultBlocks\\"=>true);
	public static function registerBlockPath($namespace){
		Block::$BLOCKS_PATHS[$namespace] = true;
	}
	public static function removeBlockPath($namespace){
		if(isset(Block::$BLOCKS_PATHS[$namespace])) unset(Block::$BLOCKS_PATHS[$namespace]);
	}
	public static function exists($name,$checkBlockOnly=false){
		
		$BLOCK_PATH = Block::$BLOCK_PATH;
		if(substr($BLOCK_PATH,-1)!="/") $BLOCK_PATH.="/";
		
		if($blockready = \AsyncWeb\IO\File::exists($f = $BLOCK_PATH.$name.".php")){
			return $blockready;
		}
		foreach(Block::$BLOCKS_PATHS as $namespace=>$t){
			if (class_exists($n=$namespace.$name)){
				return true;
			}
			
		}
		/*
		
		
		if(\AsyncWeb\DefaultBlocks\Settings::$USE_DEFAULT_BLOCKS){
			if (class_exists($n="\\AsyncWeb\\DefaultBlocks\\".$name)){
				if(\AsyncWeb\DefaultBlocks\Settings::$USE_DEFAULT_BLOCKS && $n::$USE_BLOCK){
					return true;
				}
			}
		}
		/**/
		if($checkBlockOnly){
			return false;
		}
		$TEMPLATES_PATH = Block::$TEMPLATES_PATH;
		if(substr($TEMPLATES_PATH,-1)!="/") $TEMPLATES_PATH.="/";
		return \AsyncWeb\IO\File::exists($f = $TEMPLATES_PATH."/".$name.".html") || $blockready;
	}
	public static function create($name = "", $tid = "", $template=""){
		if($file = Block::exists($name,true)){
			if($file === true){
				foreach(Block::$BLOCKS_PATHS as $namespace=>$t){
					if (class_exists($n=$namespace.$name)){
						$name = $n;
						break;
					}
				}
			}else{
				include_once($file);
			}
			return new $name($name,$tid,$template);
		}
		return new Block($name,$tid,$tmplate);
	}
	public function __construct($name = "", $tid = "", $template=""){
		
		$this->template = $template;
		$this->tid = $tid;
		
		$this->initTemplate();
		if(!$name) $name = get_class($this);
		$this->name = $name;
		$this->data = array(""=>array());
		if(!$this->template){
			if(!\AsyncWeb\IO\File::exists($f = Block::$TEMPLATES_PATH."/".$name.".html")){
				echo "Template ".$name." not found!\n";
				throw new \Exception("Template ".$name." not found!");
			}
			
			$this->template = file_get_contents($f,true);
		}
		
		$this->init();
	}
	protected function init(){
	
	}
	protected function initTemplate(){
	
	}
	protected $name = "";
	public function name(){
		return $this->name;
	}
	public function setTemplate($template){
		$this->template = $template;
		//$this->notify();
	}
	public function getTemplate(){
		return $this->template;	
	}
	public function setData(Array $data, $namespace=""){
		$this->data[$namespace] = $data;
		$this->notify($namespace);
	}
	public function getData($namespace=""){
		return $this->data[$namespace];	
	}
	public function getInnerBlocks($namespace=""){
		$ret=array();
		$pos = 0;
		while(($pos = strpos($this->template,"{{{",$pos)) !== false){
			$pos+=3;
			$pos2 = strpos($this->template,"}}}",$pos);
			$item = trim(substr($this->template,$pos,$pos2-$pos));
			if(substr($item,0,4)=="url:"){
				
			}else if(!isset($this->data[$namespace][$item])){ // only if we do not use the variable with the same name, try to load template
				$templateid = URLParser::get($item);
				try{
					$tid = BlockManagement::getTid($templateid);
					if($itemcl = BlockManagement::get($templateid,$tid)){
						$ret[] = $itemcl;
					}
				}catch(Exception $exc){
				}
			}
		}
		return $ret;
	}
	public function get($namespace=""){
		if(Block::$MustacheEngine == null) 
			Block::$MustacheEngine = new \Mustache_Engine();
		
		if(!isset($this->data[$namespace])){
			$this->data[$namespace] = array();
		};
		
		$pos = 0;
		while(($pos = strpos($this->template,"{{{",$pos)) !== false){
			$pos+=3;
			$pos2 = strpos($this->template,"}}}",$pos);
			$item = trim(substr($this->template,$pos,$pos2-$pos));
			if(substr($item,0,4)=="url:"){
				$this->data[$namespace][$item] = URLParser::add(substr($item,4));
			}else if(!isset($this->data[$namespace][$item])){ // only if we do not use the variable with the same name, try to load template
				
				
				$templateid = URLParser::get($item);
				try{
					$tid = BlockManagement::getTid($templateid);
					if($itemcl = BlockManagement::get($templateid,$tid)){
						$itemid = $item;
						if($p = strpos($item,":")){
							$itemid = substr($item,0,$p);
						}
						$this->data[$namespace][$item] = '<span id="T_'.$itemid.'">'.$itemcl->get().'</span>';
					}
				}catch(Exception $exc){
					
				}
				
			}
		}
		
		if(isset(static::$DICTIONARY[\AsyncWeb\System\Language::get()])){
			foreach(static::$DICTIONARY[\AsyncWeb\System\Language::get()] as $k=>$v){
				$this->data[$namespace][$k] = $v;
			}
		}
		
		if($this->data[$namespace]["USER_ID"] = \AsyncWeb\Security\Auth::userId()){
			
			if(\AsyncWeb\Security\Auth::checkControllers() === true){
				$this->data[$namespace]["UNAUTH"] = false;
				$this->data[$namespace]["AUTH"] = true;
				$this->data[$namespace]["PREAUTH"] = false;
			}else{
				$this->data[$namespace]["UNAUTH"] = true;
				$this->data[$namespace]["AUTH"] = false;
				$this->data[$namespace]["PREAUTH"] = true;
			}
		}else{
			$this->data[$namespace]["UNAUTH"] = true;
			$this->data[$namespace]["AUTH"] = false;
			$this->data[$namespace]["PREAUTH"] = false;
		}
		
		return Block::$MustacheEngine->render($this->template,$this->data[$namespace]);
	}
	public function notify($namespace=""){
		//$this->init();
		$R = array("msg"=>"changed","id"=>Block::$i++,"changed"=>array("template"=>$this->name(),"tid"=>$this->tid,"data"=>$this->getData($namespace)));
		$data = json_encode($R);
		
		if(isset($this->clients[$namespace]))
		foreach($this->clients[$namespace] as $client){
			echo "notify: $data\n";
			if($client){
				$r = $client->send($data);
				//echo "result from sending:";
				//var_dump($r);
				//exit;
			}
		}
	}
	public function subscribe($client,$namespace){
		$this->clients[$namespace][] = $client;
		return true;
	}
	public function unsubscribe($client,$namespace){
		foreach($this->clients[$namespace] as $k=>$cl){
			if($cl->resourceId == $client->resourceId){
				unset($this->clients[$namespace][$k]);
				return true;
			}
		}
		return false;
	}
}