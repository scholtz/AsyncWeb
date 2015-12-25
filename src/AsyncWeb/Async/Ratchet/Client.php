<?php
namespace AsyncWeb\Async\Ratchet;
use AsyncWeb\Frontend\BlockManagement;
use AsyncWeb\Frontend\Block;
use AsyncWeb\Frontend\URLParser;

class Clients{
	public static $heartbeattime = false;
	public static $heartbeats = array();
	protected static $data = array();
	public static function set($clientid,$id,$value){
		if(!isset(Clients::$data[$clientid][$id])) Clients::$data[$clientid][$id] = array();
		Clients::$data[$clientid][$id] = array($value);
	}
	
	
	protected static $subscriptions = array();
	public static function add($client,$id,$value){
		//echo "resourceid:";var_dump($client->resourceId);
		if(isset(Clients::$subscriptions[$client->resourceId]) && Clients::$subscriptions[$client->resourceId] == $value["name"]) return false;
		// unsubscribe old url
		Clients::unsubscribe($client);
	
		
		Clients::$subscriptions[$client->resourceId] = $value["name"]; // new url
		$clientid = $client->resourceId;
		if(!isset(Clients::$data[$clientid][$id])) Clients::$data[$clientid][$id] = array();
		Clients::$data[$clientid][$id][] = $value;
		
		
		$urla = URLParser::parse($value["name"]);
		//var_dump($urla);
		if(isset($urla["tmpl"]))
		foreach($urla["tmpl"] as $tmpl=>$new){
			$tid=BlockManagement::getTid($new);
			$block = BlockManagement::get($new,$tid);
			echo "Subscribing ".$client->resourceId." to ".$block->name()."\n";
			$block->subscribe($client,"");
			$list = $block->getInnerBlocks();
			while($block=array_pop($list)){
				if(isset($urla["tmpl"][$block->name()])) continue;
				echo "Subscribing ".$client->resourceId." to ".$block->name()."\n";
				$block->subscribe($client,"");
				foreach($block->getInnerBlocks() as $b){$list[]=$b;}
			}
		}
		
	}
	public static function unsubscribe($client){
		if(isset(Clients::$subscriptions[$client->resourceId])){
			$urla = URLParser::parse(Clients::$subscriptions[$client->resourceId]);
			//var_dump($urla);
			if(isset($urla["tmpl"]))
			foreach($urla["tmpl"] as $tmpl=>$new){
				$tid=BlockManagement::getTid($new);
				$block = BlockManagement::get($new,$tid);
				echo "UnSubscribing ".$client->resourceId." to ".$block->name()."\n";
				$block->unsubscribe($client,"");
				$list = $block->getInnerBlocks();
				while($block=array_pop($list)){
					if(isset($urla["tmpl"][$block->name()])) continue;
					echo "UnSubscribing ".$client->resourceId." to ".$block->name()."\n";
					$block->unsubscribe($client,"");
					foreach($block->getInnerBlocks() as $b){$list[]=$b;}
				}
			}
		}
	}
	public static function remove($clientid,$id,$value){
		if(!isset(Clients::$data[$clientid][$id])) Clients::$data[$clientid][$id] = array();
		foreach(Clients::$data[$clientid][$id] as $k=>$v){
			if(is_array($v)){
				if(!is_array($value)) return false;
				$equal = true;
				foreach($value as $kk=>$vv){
					if(!$equal) continue;
					if(!isset($v[$kk]) || $vv != $v[$kk]){
						$equal = false;
					}
				}
				// note that reverse check is time consuming, so it is not on
				// also in value might be only identifier
				
				if(!$equal){
					unset(Clients::$data[$clientid][$id][$k]);
					return true;
				}
			}else{
				if($v===$value){// datatype sensitive
					unset(Clients::$data[$clientid][$id][$k]);
					return true;
				}
			}
		}
		return false;
	}
	public static function get($clientid,$id){
		return Clients::$data[$clientid][$id];
	}
}