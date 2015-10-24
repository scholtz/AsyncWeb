<?php
/**
 Class WebMining

 @author Ludovit Scholtz <ludovit@scholtz.sk>
 
*/
namespace AsyncWeb\DataMining;
use AsyncWeb\Date\Time;
use AsyncWeb\DB\DB;
use AsyncWeb\System\Execute;

class WebMining{
	private $doc = null;
	private $xpath = null;
	private $text = null;
	private $config = null;
	private $time = null;
	public $showtexts = false;
	public function spracuj($config,$text,$time){
		if(isset($config["from_encoding"])){
			$from_enc = "ISO-8859-2";
			if($config["from_encoding"]) $from_enc = $config["from_encoding"];
			if($from_enc != "utf-8"){
				//$text = '<meta http-equiv="content-type" content="text/html; charset=utf-8"/>'.$text;
				$text = str_replace($from_enc,"utf-8",$text);
				$this->text = iconv($from_enc."//IGNORE","utf-8",$text);
				
			}
		}
		if(!$this->text) $this->text =$text;
		$this->config = $config;
		
		$this->time = Time::get($time);
		$id2 = $this->getFilteredValue($config["id"]);
		if(!$id2) return false;
		$row = DB::qbr($config["table"],array("where"=>array("id2"=>$id2),"cols"=>array("od")));
		if($row){
	 	 if(Time::get($time) <= Time::get($row["od"])){
	 		if($this->showtexts) echo "stary udaj\n";return false;
		 }
		}

		$time = Time::get();

		$data = array();
		foreach ($this->config["cols"] as $col=>$value){
			$data[$col] = $this->getFilteredValue($col);
		}

		$data["id2"] = $data[$this->config["id"]];
		if(@$this->config["requires"]){
		 foreach($this->config["requires"] as $item){
			if(!$data[$item]) return false;
		 }
		}
		

		if($data["id2"]){
			return $this->checkAndUpdate($data);
		}

		return false;
		
	}
	private function checkAndUpdate($data){
		$table = $this->config["table"];
		$id2 = $data["id2"];
		
			$config = array();
			foreach ($this->config["cols"] as $col=>$value){
				if(@$value["datatype"]){
					$config["cols"][$col] = $value["datatype"];
				}
			}


		if(isset($this->config["postprocessing"])){
			$data = Execute::run($this->config["postprocessing"],array("data"=>$data));
		}
		
		$row = DB::gr($table,$id2);
		if(!$row){
			if($this->showtexts) echo "vkladam $id2\n";
			DB::insert($table,$data,$config);
			return true;
		}
		
		if($this->time <= Time::get($row["od"])){
			if($this->showtexts) echo "stary udaj\n";return false;
		}
		$changed_list = array();
		foreach ($data as $key=>$value){
			if(@$row[$key] != $value) $changed_list[$key] = $value;
		}
		
		if(count($changed_list) > 0){
			if($this->showtexts) echo "aktualizujem $id2\n";
			if(isset($this->config["tracktable"])) $config["tracktable"] = $this->config["tracktable"];
			DB::u($table,$id2,$changed_list,$config);
			return true;
		}
		if($this->showtexts) echo "nevkladam, neaktualizujem  $id2\n";
		return false;
	}
	
	
	private function getFilteredValue($col){
		$data = $this->getValue($col);
		if(@$this->config["cols"][$col]["filter"]){
			$data = $this->filter($data,$this->config["cols"][$col]["filter"]);
			if(@$this->config["cols"][$col]["datatype"]["type"] == "int"){
			 if($data === "") $data = null;
			}
		}
		return $data;
	}
	private function getValue($col){
		if(@$this->config["cols"][$col]["xpath"]){
			return $this->getXpathValue($this->config["cols"][$col]["xpath"]);
		}
		if(@$this->config["cols"][$col]["contains"]){
			return $this->getContainsValue($this->config["cols"][$col]["contains"]);
		}
		if(@$this->config["cols"][$col]["value"]){
			return $this->config["cols"][$col]["value"];
		}
		
	}
	
	private function getContainsValue($cont){
		foreach ($cont as $key=>$value){
			if(stripos($this->text,$key)){
				return $value;
			}
		}
	}
	
	private function getXpathValue($query){
		if(!$this->doc){
			$this->doc = @\DOMDocument::loadHtml($this->text);
			
			if(!$this->doc) return false;
		}
		if(!$this->xpath){
			$this->xpath = new \DOMXPath($this->doc);
		}
		
		$node = $this->xpath->query($query)->item(0);

		if($node){
			return $node->nodeValue;
		}
		return false;
	}
	
	private function filter($data,$filter){
		if(!$data) return $data;
		if(@$filter["str_replace"]){
			return $this->filter_str_replace($data,$filter);
		}
		if(@$filter["explode"]){
			return $this->filter_explode($data,$filter);
		}
		if(@$filter["trim"]){
			return $this->filter_trim($data,$filter);
		}
		if(@$filter["php"]){
			return Execute::run($filter["function"],array("data"=>$data));
			if(@$filter["filter"]){
				$data = $this->filter($data,$filter["filter"]);
			}
		}
	}
	private function filter_str_replace($data,$filter){
		if(is_array($filter["str_replace"])){
			foreach ($filter["str_replace"] as $find=>$repl){
				$data = str_replace($find,$repl,$data);
			}
		}
		if(@$filter["filter"]){
			$data = $this->filter($data,$filter["filter"]);
		}
		return $data;
	}
	private function filter_explode($data,$filter){
		
		$expl_a = explode($filter["explode"],$data);
		$data = @$expl_a[$filter["explode_iter"]];
		if(@$filter["filter"]){
			$data = $this->filter($data,$filter["filter"]);
		}
		return $data;
	}
	private function filter_trim($data,$filter){
		
		
		$data = trim($data);
		
		if(@$filter["filter"]){
			$data = $this->filter($data,$filter["filter"]);
		}
		return $data;
	}
}

?>