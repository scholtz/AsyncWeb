<?php
namespace AsyncWeb\Api\REST;

use AsyncWeb\System\Language;

class Service{
	/**
		Convers query builder between API DB form and Native DB form
	*/
	public static function ConvertQuery($QueryBuilder = array(),$CONVERT = array(),$DB_DICT_COLS=array()){
		
		//throw new \Exception($DB_DICT_COLS);exit;
		
		$qb = array();
		if(isset($QueryBuilder["Where"])){
			$where = array();
			if(!is_array($QueryBuilder["Where"])){
				throw new \Exception("Where condition in your query must be an array!");
			}
			foreach($QueryBuilder["Where"] as $k=>$v){
				if(isset($CONVERT[$k])){
					if(isset($DB_DICT_COLS[$k])){
						$phrases = Language::db_dict_find_by_value($v,$lang=false,$exact = true);
						$where[] = array("col"=>"-(");
						if(!$phrases){
							$where[] =array("col"=>$CONVERT[$k],"op"=>"eq","value"=>$v);
						}else{
							$i = 0;
							foreach($phrases as $key){$i++;
								if($i > 1)
									$where[] = array("col"=>"-or");
								$where[] = array("col"=>$CONVERT[$k],"op"=>"eq","value"=>$key);
							}
						}
						$where[] = array("col"=>"-)");
					}else{
						$where[$CONVERT[$k]] = $v;
					}
				}elseif(is_array($v)){
					if(isset($CONVERT[$v["col"]])){
						if(isset($DB_DICT_COLS[$v["col"]])){
							$exact = true;
							if($v["col"] == "like"){
								$exact = false;
							}
							$phrases = Language::db_dict_find_by_value($v,$lang=false,$exact);
							$where[] = array("col"=>"-(");
							if(!$phrases){
								$v["col"] = $CONVERT[$v["col"]];
								$where[$k] = $v;
							}else{
								$i = 0;
								foreach($phrases as $key){$i++;
									if($i > 1)
										$where[] = array("col"=>"-or");
									$where[] = array("col"=>$CONVERT[$v["col"]],"op"=>"eq","value"=>$key);
								}
							}
							$where[] = array("col"=>"-)");
						}else{
							$v["col"] = $CONVERT[$v["col"]];
							$where[$k] = $v;
						}
					}else{
						$where[$k] = $v;
					}
				}
			}
			$qb["where"] = $where;
		}
		if(isset($QueryBuilder["Sort"])){
			if(is_array($QueryBuilder["Sort"])){
				foreach($QueryBuilder["Sort"] as $k=>$v){
					if(isset($CONVERT[$k])){
						$qb["order"][$CONVERT[$k]] = $v;
					}else{
						$qb["order"][$k] = $v;
					}
				}
			}else{
				$qb["order"] = $CONVERT[$QueryBuilder["Sort"]];
			}
		}
		if(isset($QueryBuilder["Cols"])){
			if(is_array($QueryBuilder["Cols"])){
				foreach($QueryBuilder["Cols"] as $k=>$v){
					if(isset($CONVERT[$k])){
						$qb["cols"][$CONVERT[$k]] = $v;
					}elseif(isset($CONVERT[$v])){
						$qb["cols"][$k] = $CONVERT[$v];
					}
				}
			}else{
				$qb["cols"] = $CONVERT[$QueryBuilder["Cols"]];
			}
		}

		if(isset($QueryBuilder["GroupBy"])) $qb["groupby"] = $QueryBuilder["GroupBy"];
		if(isset($QueryBuilder["Having"])) $qb["having"] = $QueryBuilder["Having"];
		if(isset($QueryBuilder["Offset"])) $qb["offset"] = $QueryBuilder["Offset"];
		if(isset($QueryBuilder["Time"])) $qb["time"] = $QueryBuilder["Time"];
		
		return $qb;
		 
	}
	
}