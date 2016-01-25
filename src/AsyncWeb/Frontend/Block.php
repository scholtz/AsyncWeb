<?php
namespace AsyncWeb\Frontend;

class Block{
	public static $TEMPLATES_PATH = "../templates/";
	public static $BLOCK_PATH = "../templates/";
	public static $DICTIONARY = array();
	public $blockElement = "div";
	protected static $i = 1;
	protected static $MustacheEngine = null;
	protected $template = "";
	protected $data = array();
	protected $renderedTemplates = array();
	protected $tid = "";
	protected $clients = array();
	protected $usesparams = array();
	
	public function getUsesParams(){
		return $this->usesparams;
	}
	public function overRideOuterBlock(){
		return false;
	}
	public static $BLOCKS_PATHS = array("\\AsyncWeb\\DefaultBlocks\\"=>true);
	public static function registerBlockPath($namespace){
		Block::$BLOCKS_PATHS[$namespace] = true;
	}
	public static function removeBlockPath($namespace){
		if(isset(Block::$BLOCKS_PATHS[$namespace])) unset(Block::$BLOCKS_PATHS[$namespace]);
	}
	public static function normalizeName($name){
		return str_replace("_",'\\',$name);
	}
	public static function exists($name,$checkBlockOnly=false){
		$name = Block::normalizeName($name);
		
		$BLOCK_PATH = Block::$BLOCK_PATH;
		if(substr($BLOCK_PATH,-1)!="/") $BLOCK_PATH.="/";

		if($blockready = \AsyncWeb\IO\File::exists($f = $BLOCK_PATH.str_replace('\\',DIRECTORY_SEPARATOR,$name).".php")){
			return $blockready;
		}
		foreach(Block::$BLOCKS_PATHS as $namespace=>$t){
			if (class_exists($n=$namespace.$name)){
				return true;
			}
		}
		if($checkBlockOnly){
			return false;
		}
		$TEMPLATES_PATH = Block::$TEMPLATES_PATH;
		if(substr($TEMPLATES_PATH,-1)!="/") $TEMPLATES_PATH.="/";
		return \AsyncWeb\IO\File::exists($f = $TEMPLATES_PATH."/".$name.".html") || $blockready;
	}
	public static function create($name = "", $tid = "", $template=""){
		$name = Block::normalizeName($name);
		if(substr($name,0,1) != "\\" && !class_exists($name) && class_exists("\\".$name)){
			$name = "\\".$name;
		}
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
			
			
			if(!class_exists($name)){
				throw new Exception(Language::get("Block %name% does not exists!",array("%name%"=>$name)));
			}
			return new $name($name,$tid,$template);
		}
		return new Block($name,$tid,$tmplate);
	}
	public function __construct($name = "", $tid = "", $template=""){
		$name = Block::normalizeName($name);

		$this->template = $template;
		$this->tid = $tid;
		
		$this->initTemplate();
		if(!$name) $name = get_class($this);
		$this->name = $name;
		$this->data = array(""=>array());
		if($this->template === null){
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
		return Block::normalizeName($this->name);
	}
	protected $rendered = false;
	public function isRendered(){
		return $this->rendered;
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
	public function changeData(Array $data,$namespace=""){
		$changed = false;
		foreach($data as $k=>$v){
			if($v!=$this->data[$namespace][$k]){$this->data[$namespace][$k] = $v;$changed = true;}
		}
		if($changed){
			$this->notify($namespace);
		}
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
		if(Block::$MustacheEngine == null){
			Block::$MustacheEngine = new \Mustache_Engine();
		}
		
		$dataToRender = array();
		if(isset($this->data[$namespace])) $dataToRender = $this->data[$namespace];
		
		if(isset(static::$DICTIONARY[\AsyncWeb\System\Language::get()])){
			foreach(static::$DICTIONARY[\AsyncWeb\System\Language::get()] as $k=>$v){
				$dataToRender[$k] = $v;
			}
		}
		

		$pos = 0;
		while(($pos = strpos($this->template,"{{{",$pos)) !== false){
			$pos+=3;
			$pos2 = strpos($this->template,"}}}",$pos);
			$item = trim(substr($this->template,$pos,$pos2-$pos));
			if(substr($item,0,4)=="url:"){
				$dataToRender[$item] = URLParser::add(substr($item,4));
			}else if(!isset($dataToRender[$item])){ // only if we do not use the variable with the same name, try to load template
				
				
				$templateid = URLParser::get($item);
				try{

					$tid = BlockManagement::getTid($templateid);
					if($itemcl = BlockManagement::get($templateid,$tid)){
						$itemid = $item;
						if($p = strpos($item,":")){
							$itemid = substr($item,0,$p);
						}
						if($itemcl->overRideOuterBlock()){
							$dataToRender[$item] = $itemcl->get();
						}else{
							$dataToRender[$item] = '<'.$this->blockElement.' id="T_'.$itemid.'">'.$itemcl->get().'</'.$this->blockElement.'>';
						}
					}
				}catch(Exception $exc){
					
				}
				
			}
		}
		
		
		if($dataToRender["USER_ID"] = \AsyncWeb\Security\Auth::userId()){
			
			if(\AsyncWeb\Security\Auth::checkControllers() === true){
				$dataToRender["UNAUTH"] = false;
				$dataToRender["AUTH"] = true;
				$dataToRender["PREAUTH"] = false;
			}else{
				$dataToRender["UNAUTH"] = true;
				$dataToRender["AUTH"] = false;
				$dataToRender["PREAUTH"] = true;
			}
		}else{
			$dataToRender["UNAUTH"] = true;
			$dataToRender["AUTH"] = false;
			$dataToRender["PREAUTH"] = false;
		}
		$dataToRender["TEMPLATE_START_DELIMITER"] = "{{{";
		$this->rendered = true;
		$ret= Block::$MustacheEngine->render($this->template,$dataToRender);
		return $ret;
	}
	public function notify($namespace=""){
		//$this->init();
		if($this->isRendered()){
			\AsyncWeb\Frontend\BlockManagement::rerender();
		}
		
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