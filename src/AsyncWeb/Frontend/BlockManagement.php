<?php
use AsyncWeb\Frontend\Block;
namespace AsyncWeb\Frontend;

class BlockManagement{
	protected static $defaultBlock = null;
	public static function setDefaultBlock(Block $default){
		BlockManagement::$defaultBlock = $default;
	}
	public static function renderWeb(){
		if(BlockManagement::$defaultBlock == null){
			if(Block::exists("index")){
				$def = Block::create("index");
				BlockManagement::setDefaultBlock($def);
			}else{
				echo "Please set up the default block in the settings file or create index block!";exit;
			}
		}
		
		$namespace = "";
		\AsyncWeb\Security\Auth::check();
		if($usr = \AsyncWeb\Security\Auth::userId()){
			$namespace = $usr;
		}
		
		echo BlockManagement::$defaultBlock->get($namespace);exit;
	}
	
	protected static $blocks = array();
	protected static $personalized = array();
	protected static $instances = array();
	public static function getTid($blockname){
		$block = BlockManagement::get($blockname, "");
		return $tid = URLParser::selectParameters($block->getUsesParams());
	}
	public static function get($name,$tid){
		//var_dump("BlockManagement::get:$name;$tid");
		try{
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