<?php
namespace AsyncWeb\Frontend;

class Block{
	public static $TEMPLATES_PATH = "../templates/";
	public static $BLOCK_PATH = "../templates/";
	public static $DICTIONARY = array();
	public static $DEBUG_TIME = false;
	public $blockElement = "div";
	public $blockAttrs = array();
	protected static $i = 1;
	protected static $MustacheEngine = null;
	protected $template = "";
	protected $data = array();
	protected $renderedTemplates = array();
	protected $tid = "";
	protected $clients = array();
	protected $usesparams = array();
	protected $name = "";
	
	
	public function getUsesParams(){
		return $this->usesparams;
	}
	public function overRideOuterBlock(){
		return false;
	}
	public static $BLOCKS_PATHS = array("\\AsyncWeb\\DefaultBlocks\\"=>10001,""=>10000);
	public static function registerBlockPath($namespace,$level=1){
		Block::$BLOCKS_PATHS[$namespace] = $level;
		asort(Block::$BLOCKS_PATHS);
	}
	public static function removeBlockPath($namespace){
		if(isset(Block::$BLOCKS_PATHS[$namespace])) unset(Block::$BLOCKS_PATHS[$namespace]);
	}
	
	public static $TEMPLATE_PATHS = array();
	protected static $TEMPLATE_PATH_INITIALIZED = false;
	public static function registerTemplatePath($dir,$level=1){
		if(!$dir || !is_dir($dir)){
			throw new \Exception("Directory for templates does not exists!");
		}
		Block::$TEMPLATE_PATHS[$dir] = $level;
		asort(Block::$TEMPLATE_PATHS);
	}
	public static function removeTemplatePath($dir){
		if(isset(Block::$TEMPLATE_PATHS[$dir])) unset(Block::$TEMPLATE_PATHS[$dir]);
	}
	
	
	public static function normalizeName($name){
		return str_replace("_",'\\',$name);
	}
	public static function normalizeTemplatePath($name){
		$name = Block::normalizeName($name);
		$path = str_replace("\\","/",$name);
		$path = trim($path,"/");
		return $path;
	}
	public static function templateHasPriorityOverBlock($name){
		$name = Block::normalizeName($name);
		$merged = array_merge(Block::$BLOCKS_PATHS,Block::$TEMPLATE_PATHS);
		asort($merged);
		foreach($merged as $namespace=>$t){
			if(!$namespace){
				if(isset(Block::$BLOCK_PATH)){
					if($blockready = \AsyncWeb\IO\File::exists($f = Block::$BLOCK_PATH.str_replace('\\',DIRECTORY_SEPARATOR,$name).".php")){
						return false;
					}
				}
				if(isset(Block::$TEMPLATES_PATH)){
					$n = Block::NormalizeTemplatePath($name);	
					if ($file = \AsyncWeb\IO\File::exists($f = Block::$TEMPLATES_PATH."/".$n.".html")){
						return $file;
					}
				}
			}else{
			
				if(isset(Block::$BLOCKS_PATHS[$namespace])){
					if (class_exists($n=$namespace.$name)){
						return false;
					}
				}
				if(isset(Block::$TEMPLATE_PATHS[$namespace])){
					$n = Block::normalizeTemplatePath($name);	
					if ($file = \AsyncWeb\IO\File::exists($f = $namespace."/".$n.".html")){
						return $file;
					}
				}
			}
			
		}
	}
	protected static function initTemplatePath(){
		if(!Block::$TEMPLATE_PATH_INITIALIZED){
			$dir=realpath($p1= (__DIR__ . "/../DefaultTemplates"));
			if(is_dir($dir)){
				Block::$TEMPLATE_PATHS[$dir]=10000;
			}
			Block::$TEMPLATE_PATH_INITIALIZED = true;
		}
	}
	public static function exists($name,$checkBlockOnly=false){
		Block::initTemplatePath();
		$name = Block::normalizeName($name);
		
		$BLOCK_PATH = Block::$BLOCK_PATH;
		if(substr($BLOCK_PATH,-1)!="/") $BLOCK_PATH.="/";

		if($blockready = \AsyncWeb\IO\File::exists($f = $BLOCK_PATH.str_replace('\\',DIRECTORY_SEPARATOR,$name).".php")){
			return $blockready;
		}
		foreach(Block::$BLOCKS_PATHS as $namespace=>$t){
			
			if(!$namespace){
				if(isset(Block::$BLOCK_PATH)){
					if($blockready = \AsyncWeb\IO\File::exists($f = Block::$BLOCK_PATH.str_replace('\\',DIRECTORY_SEPARATOR,$name).".php")){
						return $file;
					}
				}
			}else{
				if (class_exists($n=$namespace.$name)){
					return true;
				}
			}
		}
		if($checkBlockOnly){
			return false;
		}
		
		
		foreach(Block::$TEMPLATE_PATHS as $dir=>$t){
			$n = Block::normalizeTemplatePath($name);
			if ($file = \AsyncWeb\IO\File::exists($f = $dir."/".$n.".html")){
				return $file;
			}
		}
		$TEMPLATES_PATH = Block::$TEMPLATES_PATH;
		if(substr($TEMPLATES_PATH,-1)!="/") $TEMPLATES_PATH.="/";
		$n = Block::normalizeTemplatePath($name);
		return \AsyncWeb\IO\File::exists($f = $TEMPLATES_PATH."/".$n.".html") || $blockready;
	}
	public static function create($name = "", $tid = "", $template=null){
		try{
			if(self::$DEBUG_TIME){
				$debug_iter = 0;
				echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK CREATE ".($debug_iter++)." $name $tid\n";
			}
			
			Block::initTemplatePath();
			if(self::$DEBUG_TIME){
				echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK CREATE ".($debug_iter++)." $name $tid\n";
			}
			$name = Block::normalizeName($name);
			if(self::$DEBUG_TIME){
				echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK CREATE ".($debug_iter++)." $name $tid\n";
			}
			if(substr($name,0,1) != "\\" && !class_exists($name) && class_exists("\\".$name)){
				$name = "\\".$name;
			}
			if(self::$DEBUG_TIME){
				echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK CREATE ".($debug_iter++)." $name $tid\n";
			}
			if($file = Block::exists($name,true) && !Block::templateHasPriorityOverBlock($name)){
				
				if(self::$DEBUG_TIME){
					echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK CREATE ".($debug_iter++)." $name $tid\n";
				}
				if($file === true){
					foreach(Block::$BLOCKS_PATHS as $namespace=>$t){
						if(!$namespace){
							if(!$namespace){
								if(isset(Block::$BLOCK_PATH)){
									if($blockready = \AsyncWeb\IO\File::exists($f = Block::$BLOCK_PATH.str_replace('\\',DIRECTORY_SEPARATOR,$name).".php")){
										include_once($blockready);
										if(substr($name,0,1) != "\\"){
											$name = "\\".$name;
										}
										break;
									}
								}
							}
						}else{
							if (class_exists($n=$namespace.$name)){
								$name = $n;
								break;
							}
						}
					}
				}else{
					include_once($file);
				}
				
				if(self::$DEBUG_TIME){
					echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK CREATE ".($debug_iter++)." $name $tid\n";
				}
				
				if(!class_exists($name)){
					throw new \Exception(\AsyncWeb\System\Language::get("Block %name% does not exists!",array("%name%"=>$name)));
				}
				if(self::$DEBUG_TIME){
					echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK CREATE ".($debug_iter++)." $name $tid\n";
				}
				return new $name($name,$tid,$template);
			}
			if(self::$DEBUG_TIME){
				echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK CREATE before new block".($debug_iter++)." $name $tid\n";
			}
			return new Block($name,$tid,$template);
		}catch(\Exception $exc){
			throw $exc;
			//\AsyncWeb\Text\Msg::err($exc->getMessage());
		}
		return null;
	}
	public function __construct($name = "", $tid = "", $template=null){
		if(self::$DEBUG_TIME){
			$debug_iter = 0;
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK __construct ".($debug_iter++)." $name $tid"."\n";
		}
		$name = Block::normalizeName($name);
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK __construct ".($debug_iter++)." $name $tid"."\n";
		}
		$this->template = $template;
		$this->tid = $tid;
		try{
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK __construct ".($debug_iter++)." $name $tid"."\n";
		}
			$this->initTemplate();
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK __construct ".($debug_iter++)." $name $tid"."\n";
		}
		}catch(\Exception $exc){
			$this->template = "";
			\AsyncWeb\Text\Msg::err($exc->getMessage());
		}
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK __construct ".($debug_iter++)." $name $tid"."\n";
		}
		if(!$name) $name = get_class($this);
		$this->name = $name;
		$this->data = array(""=>array());
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK __construct ".($debug_iter++)." $name $tid"."\n";
		}
		
		$n = Block::normalizeTemplatePath($name);
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK __construct ".($debug_iter++)." $name $tid"."\n";
		}
		if($this->template === null){
			foreach(Block::$TEMPLATE_PATHS as $dir=>$t){
				if ($this->template === null && $file = \AsyncWeb\IO\File::exists($f = $dir."/".$n.".html")){
					$this->template = file_get_contents($f,true);
				}elseif ($this->template === null){
					$nparent = Block::normalizeTemplatePath(get_parent_class($this));
					$file = \AsyncWeb\IO\File::exists($f = $dir."/".$nparent.".html");
					if($file){
						$this->template = file_get_contents($f,true);
					}
				}
				
			}
		}
		
		
		if($this->template === null){
			if(self::$DEBUG_TIME){
				echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK __construct before template loading ".($debug_iter++)." $name $tid"."\n";
			}
			if(!\AsyncWeb\IO\File::exists($f = Block::$TEMPLATES_PATH."/".$n.".html")){
		
				$nparent = Block::normalizeTemplatePath(get_parent_class($this));
				if(!\AsyncWeb\IO\File::exists($f = Block::$TEMPLATES_PATH."/".$nparent.".html")){
					//echo "Template ".$n." not found!\n";
					throw new \Exception("Template ".$n." not found!");
				}else{
					$this->template = file_get_contents($f,true);
				}
			}else{
				$this->template = file_get_contents($f,true);
			}
		}
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK __construct before init ".($debug_iter++)." $name $tid"."\n";
		}
		$this->init();
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK __construct after init ".($debug_iter++)." $name $tid"."\n";
		}
	}
	protected function init(){
	
	}
	protected function initTemplate(){
	
	}
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
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK setData $name $tid"."\n";
		}
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
				}catch(\Exception $exc){
					\AsyncWeb\Text\Msg::err($exc->getMessage());
				}
			}
		}
		return $ret;
	}
	/**
	 $requiresAuthenticatedUser true|false Current block requires logged in user 
	*/
	protected $requiresAuthenticatedUser = false;
	/**
	 $requiresAnyGroup Array List of group id3. If any matches Block can be displayed. If none match, block will not be shown.
	*/
	protected $requiresAnyGroup = array();
	/**
	 $requiresAllGroups Array List of group id3. All must match in order for block to be displayed.
	*/
	protected $requiresAllGroups = array();
	protected $firstRun = true;
	public function get($namespace=""){
		if(self::$DEBUG_TIME){
			$debug_iter = 0;
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK get ".($debug_iter++)." ".$this->name." ".$this->tid."\n";
		}
		if(Block::$MustacheEngine == null){
			Block::$MustacheEngine = new \Mustache_Engine();
		}
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK get ".($debug_iter++)." ".$this->name." ".$this->tid."\n";
		}
		
		$dataToRender = array();
		if(isset($this->data[$namespace])) $dataToRender = $this->data[$namespace];
		
		if(isset(static::$DICTIONARY[\AsyncWeb\System\Language::$DEFAULT_LANGUAGE])){
			foreach(static::$DICTIONARY[\AsyncWeb\System\Language::$DEFAULT_LANGUAGE] as $k=>$v){
				$dataToRender[$k] = $v;
			}
		}
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK get ".($debug_iter++)." ".$this->name." ".$this->tid."\n";
		}
		if(isset(static::$DICTIONARY[\AsyncWeb\System\Language::get()])){
			foreach(static::$DICTIONARY[\AsyncWeb\System\Language::get()] as $k=>$v){
				$dataToRender[$k] = $v;
			}
		}
			
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK get ".($debug_iter++)." ".$this->name." ".$this->tid."\n";
		}
		if($this->requiresAuthenticatedUser || $this->requiresAnyGroup || $this->requiresAllGroups){
			if(self::$DEBUG_TIME){
				echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK get before Auth::check() ".$this->name." ".$this->tid."\n";
			}
			
			if(!\AsyncWeb\Security\Auth::check()){
				if(\AsyncWeb\Security\Auth::userId()){
					$controller = \AsyncWeb\Security\Auth::checkControllers();
					if(self::exists($controller)){
						$this->template = '{{{'.$controller.'}}}';
					}else{
						$this->template = ''.\AsyncWeb\Security\Auth::showControllerForm().'';
					}
				}else{
					$this->template = '{{{LoginForm}}}';
				}
			}
			if(self::$DEBUG_TIME){
				echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK get after Auth::check() ".$this->name." ".$this->tid."\n";
			}
		}
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK get ".($debug_iter++)." ".$this->name." ".$this->tid."\n";
		}
		if(is_array($this->requiresAnyGroup) && count($this->requiresAnyGroup) > 0){
			$show = false;
			foreach($this->requiresAnyGroup as $group){
				if(\AsyncWeb\Objects\Group::is_in_group($group)){
					$show = true;
					break;
				}
			}
			if(!$show){
				return '';
			}
		}
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK get ".($debug_iter++)." ".$this->name." ".$this->tid."\n";
		}
		if(is_array($this->requiresAllGroups) && count($this->requiresAllGroups) > 0){
			$show = true;
			foreach($this->requiresAllGroups as $group){
				if(!\AsyncWeb\Objects\Group::is_in_group($group)){
					$show = false;
					break;
				}
			}
			if(!$show){
				return '';
			}
		}
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK get ".($debug_iter++)." before inclusion of other templates ".$this->name." ".$this->tid."\n";
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
							$dataToRender[$item] = '<'.$itemcl->blockElement.' id="T_'.$itemid.'"';
							foreach($itemcl->blockAttrs as $k=>$v){
								$dataToRender[$item] .=' '.$k.'="'.$v.'"';	
							}
							$dataToRender[$item] .='>'.$itemcl->get().'</'.$itemcl->blockElement.'>';
						}
					}
				}catch(\Exception $exc){
					if($this->firstRun){
						\AsyncWeb\Text\Msg::err($exc->getMessage());
					}
					$this->firstRun = false;
					
				}
				
			}
		}
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK get ".($debug_iter++)." ".$this->name." ".$this->tid."\n";
		}
		
		$dataToRender["LANG"] = \AsyncWeb\System\Language::getLang();
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK get ".($debug_iter++)." before filling default variables ".$this->name." ".$this->tid."\n";
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
		
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK get ".($debug_iter++)." ".$this->name." ".$this->tid."\n";
		}
		
		$pos = 0;
		while(($pos = strpos($this->template,"{{",$pos)) !== false){
			$pos+=2;
			$pos2 = strpos($this->template,"}}",$pos);
			$item = trim(substr($this->template,$pos,$pos2-$pos));
			
			if(substr($item,0,1)=="{") continue;
			if(isset($dataToRender[$item])) continue;
			
			if(\AsyncWeb\System\Language::is_set($item)){
				$dataToRender[$item] = \AsyncWeb\System\Language::get($item);
			}
		}
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK get ".($debug_iter++)." ".$this->name." ".$this->tid."\n";
		}
		$this->rendered = true;
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK get before render".($debug_iter++)." ".$this->name." ".$this->tid."\n";
		}
		$ret= Block::$MustacheEngine->render($this->template,$dataToRender);
		if(self::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "BLOCK get after render".($debug_iter++)." ".$this->name." ".$this->tid."\n";
		}
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
