<?php
/**
 Class WebMining

 @author Ludovit Scholtz <ludovit@scholtz.sk>
 
*/

namespace AsyncWeb\DataMining;
use AsyncWeb\Date\Time;
use AsyncWeb\DB\DB;

class WebMining2{
	protected $doc = null;
	protected $xpath = null;
	protected $text = null;
	protected $config = null;
	protected $time = null;
	public $showtexts = false;
	public $error = "";
	public function spracuj($config,$text,$time){
		$this->error = "";
		
		$text = '<meta http-equiv="content-type" content="text/html; charset=utf-8"/>'.$text;
		$from_enc = "ISO-8859-2";
		if($config["from_encoding"]) $from_enc = $config["from_encoding"];
		
		$text = str_replace($from_enc,"utf-8",$text);
		$this->text = iconv($from_enc,"utf-8",$text);
		$this->config = $config;
		$this->time = Time::get($time);
		
		
		$data = $this->createData();
		
		if(!$data){$this->error .= "NO DATA!\n";return false;}
		//if(!@$data[$this->config["id"]]) return false;
		return $this->checkAndUpdate($data);
		
	}
	protected $currentNode = null;
	protected function createData(){
		//var_dump("wm cdata");
		$dataCol = array();
		$si = 0;
		foreach ($this->config["iter"] as $col=>$settings){$si++;
			if(!$this->doc){
				$this->doc = @\DOMDocument::loadHtml($this->text);
				if(!$this->doc) return false;
			}
			if(!$this->xpath){
				$this->xpath = new \DOMXPath($this->doc);
			}
			$i = 0;
			foreach ($this->xpath->query($settings["xpath"]) as $node){$i++;
				echo "$i";
				$this->currentNode = $node;
				$data = array();
				foreach ($this->config["cols"] as $col2=>$value){
					if($this->config["id"] == $col2) {
						$id = $this->getFilteredValue($col2);
						$data["id2"] = $id;
						
//						continue;
					}
					$data[$col2] = $this->getFilteredValue($col2);
				}
				if(@$settings["filter"]){
					$data[$col] = $this->filter($node->nodeValue,$settings["filter"]);
				}else{
					
					$data[$col] = $node->nodeValue;
				}
//				$id = $data[$this->config["id"]];
				$dataCol[$id][] = $data;
			}
			if(!$i){
				$this->error .= "no itterations\n";
			}
		}
		if(!$si){
				$this->error .= "do no have cols to itterate?\n";
		}
		return $dataCol;
	}
	
	protected function checkAndUpdate($data){
		// skontroluj ci su vsetky hodnoty tam
		// skontroluj ci nie je nieco navyse
		
		$table = $this->config["table"];
		foreach ($data as $id=>$arrv){
			$res = DB::qb($table,array("where"=>array($this->config["id"] => $id)));//,"cols"=>array("od")
			$data2 = array();
			$ret = false;
			while($row=DB::f($res)){
				$data2[] = $row;
				if($this->time <= Time::get($row["od"])){// ak lubovolny od je novsi ako dat subor, tak to nerob
					if($this->showtexts) echo "stary udaj\n";
					return ;
				}
			}
			foreach ($arrv as $iter=>$row2){
				// hladame zhodny row s row2
				$indb = false;
				foreach ($data2 as $row){
					$rovnaky = true;
					foreach ($row2 as $key=>$value){
						if($row[$key] != $value) $rovnaky = false;
					}
					
					if($rovnaky) $indb = true;
				}
				if(!$indb){
					if($this->showtexts) echo "vkladam $id\n";
					
					$config = array();
					foreach ($this->config["cols"] as $col=>$value){
						if(@$value["datatype"]){
							$config["cols"][$col] = $value["datatype"];
						}
					}
					if(@$this->config["iter"])
					foreach ($this->config["iter"] as $col=>$value){
						if(@$value["datatype"]){
							$config["cols"][$col] = $value["datatype"];
						}
					}
					$config["keys"] = array($this->config["id"]);
					$row2["id2"]=md5($id.@$row2["i"]);
					if(isset($this->config["tracktable"])) $config["tracktable"] = $this->config["tracktable"];
					DB::u($table,md5(uniqid()),$row2,$config);
				}
			}
			
			foreach ($data2 as $row){
				$inp = false;
				foreach ($arrv as $row2){
					$rovnaky = true;
					foreach ($row2 as $key=>$value){
						if($row[$key] != $value) $rovnaky = false;
					}
					if($rovnaky){ $inp = true;}
					
				}
				if(!$inp){
					$id = $row["id"];
					if($this->showtexts) echo "mazem $id\n";
					DB::delete($table,$row["id2"]);
				}
			}
			if($data) return true;
			return false;
		}
	}
	
	
	protected function getFilteredValue($col){
		$data = $this->getValue($col);
		if(isset($this->config["cols"][$col]["filter"]) && $this->config["cols"][$col]["filter"]){
			$data = $this->filter($data,$this->config["cols"][$col]["filter"]);
		}
		return $data;
	}
	protected function getValue($col){
		if(isset($this->config["cols"][$col]["xpath"]) && $this->config["cols"][$col]["xpath"]){
			if(@$this->config["cols"][$col]["xpath_rel"]){
				return $this->getXpathValue($this->config["cols"][$col]["xpath"],true);
			}else{
				return $this->getXpathValue($this->config["cols"][$col]["xpath"]);
			}
		}
		if(isset($this->config["cols"][$col]["contains"]) && $this->config["cols"][$col]["contains"]){
			return $this->getContainsValue($this->config["cols"][$col]["contains"]);
		}
		if(isset($this->config["cols"][$col]["value"]) && $this->config["cols"][$col]["value"]){
			return $this->config["cols"][$col]["value"];
		}
		
	}
	
	protected function getContainsValue($cont){
		foreach ($cont as $key=>$value){
			if(stripos($this->text,$key)){
				return $value;
			}
		}
	}
	
	protected function getXpathValue($query,$relative=false){
//		file_put_contents("test003.html",$this->text);
		if(!$this->doc){
			$this->doc = @\DOMDocument::loadHtml($this->text);
			
			if(!$this->doc) return false;
		}
//		$this->doc->save("test002.html");
		if(!$this->xpath){
			$this->xpath = new \DOMXPath($this->doc);
		}
		if($relative && $this->currentNode){
			$node = $this->xpath->query($query,$this->currentNode)->item(0);
		}else{
			$node = $this->xpath->query($query)->item(0);
		}
//		var_dump($node);
		if($node){
			return $node->nodeValue;
		}
		return false;
	}
	
	protected function filter($data,$filter){
		if(@$filter["str_replace"]){
			return $this->filter_str_replace($data,$filter);
		}
		if(@$filter["explode"]){
			return $this->filter_explode($data,$filter);
		}
		if(@$filter["trim"]){
			return $this->filter_trim($data,$filter);
		}
	}
	protected function filter_str_replace($data,$filter){
		if(!isset($filter["str_replace"])) return $data;
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
	protected function filter_explode($data,$filter){
		
		$expl_a = explode($filter["explode"],$data);
		$data = @$expl_a[$filter["explode_iter"]];
//		if(!array_key_exists($filter["explode_iter"],$expl_a)){
//			echo "asi chyba: $data;";
//			file_put_contents("test004.html",$this->text);
//		}
		if(@$filter["filter"]){
			$data = $this->filter($data,$filter["filter"]);
		}
		return $data;
	}
	protected function filter_trim($data,$filter){
		
		
		$data = trim($data);
		
		if(@$filter["filter"]){
			$data = $this->filter($data,$filter["filter"]);
		}
		return $data;
	}
}

?>