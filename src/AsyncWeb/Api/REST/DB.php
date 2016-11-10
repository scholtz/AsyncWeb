<?php

namespace AsyncWeb\Api\REST;

class DB extends \AsyncWeb\DB\DBServer {
	public $Server = "localhost";
	public $ApiKey = null;
	public $ApiPass = null;
	public $Session = null;
	/**
	 * Constructor for API DB engine
	 * 
	 * Calls Service/Connect to get the session if session has not been provided.
	 *
	 * @return \AsyncWeb\Api\JSON\DB Instance of API DB engine.
	 */
	public function __construct($Server="",$ApiKey="",$ApiPass="",$Session=""){
		
		$this->Server = $Server;
		$this->ApiKey = $ApiKey;
		$this->ApiPass = $ApiPass;
		
		if($Session) {
			$this->Session = $Session;
		}else{
			if($LatestSessionRow = \AsyncWeb\DB\DB::gr("rest_session",array("id2"=>md5($Server."-".$ApiKey."-".$ApiPass)))){
				$data = array("ApiKeySession"=>$LatestSessionRow["session"]);
				$data["CRC"] = Client::MakeCRC($data,$this->ApiPass);
				try{
					if($validate = Client::Call($Server."/Service/Validate",$data)){
						$this->Session = $LatestSessionRow["session"];
					}
				}catch(\Exception $exc){
					// session is not valid .. try to connect again
				}
			}

			if(!$this->Session){
				$data = array("ApiKey"=>$ApiKey,"Language"=>\AsyncWeb\System\Language::get());
				$data["CRC"] = Client::MakeCRC($data,$this->ApiPass);
				try{
					$this->Session = Client::Call($Server."/Service/Connect",$data);
					$r = \AsyncWeb\DB\DB::u("rest_session",md5($Server."-".$ApiKey."-".$ApiPass),array("session"=>$this->Session));
					
				}catch(\Exception $exc){
					$this->lastError = $exc->getMessage();
					throw $exc;
				}
			}
		}
		
		
		if(!$this->Session){
			throw new \Exception("Server has not provided the API session!");
		}
		
	}
	public function query($query,$link = null,$params = array()){
		throw new \Exception("Queries are not supported in JSON APIs.");
	}
	public function fetch_assoc($res){
		if(!$res) return false;
		return $res->getNext();
	}
	public function f($res){
		return $this->fetch_assoc($res);
	}
	public function fetch_array($res){
		return $this->fetch_assoc($res);
	}
	public function fetch_object($res){
		return $this->fetch_assoc($res);
	}
	public function num_rows($res){
		if(!$res) return 0;
		return $res->Count();
	}
	private $afrows = 0;
	public function affected_rows(){
		return $this->afrows;
	}
	protected $lastError = null;
	public function error(){
		return $this->lastError;
	}
	public function fetch_assoc_q($query,$link=null){
		throw new \Exception("Queries are not supported");
	}
	public function free($res){
		$res->Free();
	}
	protected $lastInsertedId = null;
	public function insert_id(){
		return $this->lastInsertedId;
	}
	public function updateSimple($table,$id,$data,$conf=array(),$insert_new = false){
		throw new \Exception("This function is not supported");
	}
	public function u($table,$id2,$data=array(),$config=false,$insert_new=true,$useOdDoSystem=true){
		return $this->update($table,$id2,$data,$config,$insert_new,$useOdDoSystem);
	}
	public function uall($table,$id2,$data=array(),$config=false){
		$this->afrows = 0;

		$res = DB::g($table,$id2);
		while($row=DB::f($res)){
			DB::u($table,$row["ID"],$data,$config);
			$afrows += $this->afrows;
		}
		$this->afrows = $afrows;

		return true;
	}
	private $spracovanet2 = array();
	public function update($table,$id2,$data2=array(),$config=array(),$insert_new=false,$useOdDoSystem=true){
		$this->afrows = 0;
		if(!$id2){
			$id2 = md5(uniqid());
		}else{
			$ID = $id2;
			if(!is_array($ID)){
				$ID = array("ID"=>$id2);
			}
			$row = $this->gr($table,$ID,array(),$cols);
			//throw new \Exception(var_export($id2,true));
			if(!$row){
				if(!$insert_new){
					return false;
				}else{
					if(!isset($data2["ID"])){
						$data2 = array_merge(array("ID"=>$id2),$data2);
						$data2["ID"] = $id2;
					}
					return $this->insert($table,$data2,$config);
				}
			}
		}
		
		$data = array("ID"=>$id2);
		foreach($data2 as $k=>$v){
			$data[$k] = $v;
		}
		$data["ApiKeySession"]=$this->Session;
		$data["CRC"] = Client::MakeCRC($data,$this->ApiPass);

		try{
			$results = Client::Call($this->Server."/".$table."/Update",$data);
			if(is_numeric($results)) $this->afrows += $results;
			return $results;
		}catch(\Exception $exc){
			$this->lastError = $exc->getMessage();
			return false;
		}		
	}
	public function deleteAll($table,$where){
		$res = DB::g($table,$where);
		while($row=DB::f($res)){
			
			DB::delete($table,$row["ID"]);
		}
		return true;
	}
	public function delete($table,$id2){
		$this->afrows = 0;
		if(is_array($id2)){
			return $this->deleteAll($table,$id2);
		}
		$data = array("ID"=>$id2);
		$data["ApiKeySession"]=$this->Session;
		$data["CRC"] = Client::MakeCRC($data,$this->ApiPass);
		try{
			$results = Client::Call($this->Server."/".$table."/Delete",$data);
			$this->afrows += $results;
			return $results;
		}catch(\Exception $exc){
			$this->lastError = $exc->getMessage();
			
			return false;
		}
	}
	private $spracovanet = array();
	public function insert($table,$data=array(),$config=array(),$create=true){
		$this->lastInsertedId = null;
		try{
			$this->afrows = 0;
			$data["ApiKeySession"]=$this->Session;
			$data["CRC"] = Client::MakeCRC($data,$this->ApiPass);
			
			$results = Client::Call($this->Server."/".$table."/Create",$data);
			
			$this->lastInsertedId = $results;
			
			if($results) $this->afrows++;
			return $results;
		}catch(\Exception $exc){
			$this->lastError = $exc->getMessage();
			if(strpos($this->lastError,"CRC does not match")){		
				$add = \AsyncWeb\Api\REST\Client::MakeHashString($data);
				$add .= " CRC: ".Client::MakeCRC($data,$this->ApiPass);

				$this->lastError.="<br>\n".$add;
			}
			return false;
		}
	}
	/**
	 get row from table
	 */
	public function gr($table,$where=array(),$order=array(),$cols=array(),$groupby=array(),$having=array(),$offset=0,$time=null){
		$ret = $this->f($this->g($table,$where,$offset,1,$order,$cols,$groupby,$having,false,$time,true));
		if(!$ret) $ret = $this->f($this->g($table,$where,$offset,1,$order,$cols,$groupby,$having,false,$time,false));
		return $ret;
	}
	/**
		Returns one row according to the query
		
		@return APIResult Result of the query
	*/
	public function getRow($table,$where=array(),$time=null,$offset=0,$od="od",$do="do",$id2="ID"){
		if(is_array($time)){
			$res = $this->get($table,$where,$offset,1,null,$time,$od,$do,$id2);
		}else{
			$res = $this->get($table,$where,$offset,1,$time,array(),$od,$do,$id2);
		}
		return $this->fetch_assoc($res);
	}
	/**
	 get result
	 */
	public function g($table,$where=array(),$offset=null,$count=null,$order=array(),$cols=array(),$groupby=array(),$having=array(),$distinct=false,$time=null,$fast=false){
		return $this->get($table,$where,$offset,$count,$time,$order,"ValidFrom","ValidUntil","ID",$cols,$groupby,$having,$distinct,$fast);
	}
	/**
		Returns one row from Query Builder
	*/
	public function qbr($table,$mixed=array()){
		$mixed["Limit"] = 1;
		$res = $this->qb($table,$mixed);
		while($row=DB::f($res)){
			return $row;
		}
		return false;
	}	
	/**
		Creates resource object for Query Builder
	*/
	public function qb($table,$mixed=array()){
		$where=array();
		$offset=null;
		$count=null;
		$order=array();
		$cols=array();
		$groupby=array();
		$having=array();
		$distinct=false;
		$time=null;
		if(!$table) throw new \AsyncWeb\Exceptions\DBException("DB Error: Table name required! (0x0029249148)");
		if(isset($mixed["Where"])) $where = $mixed["Where"];
		if(isset($mixed["Offset"])) $offset = $mixed["Offset"];
		if(isset($mixed["Count"])) $count = $mixed["Count"];
		if(isset($mixed["Limit"])) $count = $mixed["Limit"];
		if(isset($mixed["Sort"])) $order = $mixed["Order"];
		if(isset($mixed["Cols"])) $cols = $mixed["Cols"];
		if(isset($mixed["GroupBy"])) $groupby = $mixed["GroupBy"];
		if(isset($mixed["Having"])) $having = $mixed["Having"];
		if(isset($mixed["Distinct"])) $distinct = $mixed["Distinct"];
		if(isset($mixed["Time"])) $time = $mixed["Time"];

		if(isset($mixed["where"])) $where = $mixed["where"];
		if(isset($mixed["offset"])) $offset = $mixed["offset"];
		if(isset($mixed["count"])) $count = $mixed["count"];
		if(isset($mixed["limit"])) $count = $mixed["limit"];
		if(isset($mixed["order"])) $order = $mixed["order"];
		if(isset($mixed["cols"])) $cols = $mixed["cols"];
		if(isset($mixed["groupby"])) $groupby = $mixed["groupby"];
		if(isset($mixed["having"])) $having = $mixed["having"];
		if(isset($mixed["distinct"])) $distinct = $mixed["distinct"];
		if(isset($mixed["time"])) $time = $mixed["time"];
		
		return $this->g($table,$where,$offset,$count,$order,$cols,$groupby,$having,$distinct,$time);
	}
	
	/**
		Returns the resource object for the result. Please use fetch method to get the result for each row.
		
		@return APIResult Result of the query
	*/
	public function get($table,$where=array(),$offset=null,$count=null,$time = null,$order=array(),$od="ValidFrom",$do="ValidUntil",$id2="ID",$cols=array(),$groupby=array(),$having=array(),$distinct=false,$fast=false){
		
		$data = array("QueryBuilder"=>array("Where"=>$where,"Offset"=>$offset,"Limit"=>$count,"Time"=>$time,"Sort"=>$order,"Cols"=>$cols,"GroupBy"=>$groupby,"Having"=>$having,"Distinct"=>$distinct));
		$data["ApiKeySession"]=$this->Session;
		$data["CRC"] = Client::MakeCRC($data,$this->ApiPass);
		try{
			$results = Client::Call($this->Server."/".$table."/Request",$data);
			return new APIResult($results);
		}catch(\Exception $exc){
			$this->lastError = $exc->getMessage();
			if(strpos($this->lastError,"CRC does not match")){		
				$add = "CLIENT: ";
				$add .= \AsyncWeb\Api\REST\Client::MakeHashString($data);
				$add .= " CRC: ".Client::MakeCRC($data,$this->ApiPass);
				$this->lastError.="<br>\n".$add;
			}

			return false;
		}
	}
	public function SortFields($data=array(),$api_keys_order=array()){
		$new = array();
		foreach($api_keys_order as $item){
			if(isset($data[$item])) $new[$item] = $data[$item];
		}
		return $new;
	}
	
}
