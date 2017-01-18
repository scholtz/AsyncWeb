<?php
use AsyncWeb\Frontend\Block;
namespace AsyncWeb\Frontend;

class BlockManagement{
	protected static $defaultBlock = null;
	public static function setDefaultBlock(Block $default){
		BlockManagement::$defaultBlock = $default;
	}
	protected static $needToRerender = true;
	public static function rerender(){
		self::$needToRerender = true;
	}
	public static function renderWeb(){
		if(\AsyncWeb\System\Router::run()) return;
		if(\AsyncWeb\Frontend\Block::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "Rendering has started: "."\n";
		}
		if(BlockManagement::$defaultBlock == null){
			$index = "Index";
			$url = URLParser::parse();
			if(isset($url["tmpl"]["Index"])){
				$index = $url["tmpl"]["Index"];
			}
			
			if(Block::exists($index)){
				if(\AsyncWeb\Frontend\Block::$DEBUG_TIME){
					echo \AsyncWeb\Date\Timer1::show(); echo "BlockManagement:Render before creating default block "."\n";
				}

				$def = Block::create($index);
				if(\AsyncWeb\Frontend\Block::$DEBUG_TIME){
					echo \AsyncWeb\Date\Timer1::show(); echo "BlockManagement:Render after creating default block "."\n";
				}
				BlockManagement::setDefaultBlock($def);
				if(\AsyncWeb\Frontend\Block::$DEBUG_TIME){
					echo \AsyncWeb\Date\Timer1::show(); echo "BlockManagement:Render after setting default block "."\n";
				}
			}else{
				throw new Exception(Language::get("Please set up the default block in the settings file or create index block!"));
			}
		}
		
		$namespace = "";
		try{
			if(\AsyncWeb\Frontend\Block::$DEBUG_TIME){
				echo \AsyncWeb\Date\Timer1::show(); echo "BlockManagement:Render before Auth::check()"."\n";
			}
			\AsyncWeb\Security\Auth::check();
			if(\AsyncWeb\Frontend\Block::$DEBUG_TIME){
				echo \AsyncWeb\Date\Timer1::show(); echo "BlockManagement:Render after Auth::check()"."\n";
			}
		}catch(\Exception $exc){
			\AsyncWeb\Text\Msg::err($exc->getMessage());
		}
		if($usr = \AsyncWeb\Security\Auth::userId(true)){
			$namespace = $usr;
		}
		while(self::$needToRerender){
			self::$needToRerender = false;
			$ret=BlockManagement::$defaultBlock->get($namespace);
		}
		if(\AsyncWeb\Frontend\Block::$DEBUG_TIME){
			echo \AsyncWeb\Date\Timer1::show(); echo "Render has finished: "."\n";
		}

		echo $ret;
		exit;
		
	}
	
	protected static $blocks = array();
	protected static $personalized = array();
	protected static $instances = array();
	public static function getTid($blockname){
		$block = BlockManagement::get($blockname);
		if(!$block){
			throw new \Exception("Block not found: $blockname");
		}
		return $tid = URLParser::selectParameters($block->getUsesParams());
	}
	public static function get($name,$tid=""){
		try{
			$name = Block::normalizeName($name);
			if(isset(BlockManagement::$instances[$name][$tid])){
				return BlockManagement::$instances[$name][$tid];
			}else{
				if(BlockManagement::$instances[$name][$tid] = Block::create($name,$tid)){
					return BlockManagement::$instances[$name][$tid];
				}
			}
			
		}catch(\Exception $exc){
			throw $exc; 
		}
	}
	public static function subscribe(Block $block,Bool $personalized){
		BlockManagement::$blocks[$block->name()] = $block;
		BlockManagement::$personalized[$block->name()] = $personalized;
	}
	public static function setTemplate(String $BlockClass,String $template){
		BlockManagement::$blocks[$BlockClass]->setTemplate($template);
		foreach(BlockManagement::$instances as $usrsdata){
			foreach($usrsdata as $usr=>$class){
				$class->setTemplate($template);
			}
		}
	}
	public static function setData(String $BlockClass,String $UserId, Array $data){
		if(!$UserId){
			BlockManagement::$blocks[$BlockClass] = new $BlockClass;
		}else{
			BlockManagement::$instances[$BlockClass][$UserId]= new $BlockClass;
		}
	}
	
}