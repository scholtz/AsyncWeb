<?php
/**
 * MakeDBViewDirect
 * 
 *@author Ludovit Scholtz ludovit@scholtz.sk
 *@version 2.0.1
 * 
 * 8.7.2012  BugFix -> Export did not support DB selection
 *
 * 1.4.2012  BugFix -> sort is working now
 *
 * 21.11.2011 MakeDBView -> MakeDBViewDirect
 *             zrusena podpora tabuliek ktore nie su od-do
 *
 * 17.1.2010 Pridana podpora pre kontrolu struktury db MakeDBViewDirect::tryRepair($data);
 *    if(!MakeDBViewDirect::$repair) return; 
 *
 * Pridana podpora pre 
 *
 *   pridana podpora pre od do
 * 
 *  	15.11.07 "brokerska_spolocnost"=>array("name"=>"Number","table"=>"brokerska_spolocnost","refalias"=>"br0","refid"=>"id2","refcol"=>array(
 *		  array("type"=>"col","value"=>"kod_brokerskej_spol","od"=>"od","do"=>"do"),
 *		)),
 *
 * 11.7.2011, pridany filter pre DB: "filter"=>array("type"=>"db","table"=>"mytable","conds"=>array(),"where"=>"mycol","col"=>"select");
 * 11.7.2011, pridany filter pre or: "filter"=>array("type"=>"or","filters"=>array(..));
 *
 * 
 */
use AsyncWeb\System\Path;
use AsyncWeb\DB\DB;
use AsyncWeb\System\Language;
namespace AsyncWeb\View;

class MakeDBViewDirect{
	private static function makeCols(&$data,$filterS="DBVs"){
		$cols = array("id"=>"id","id2"=>"id2","od"=>"od","do"=>"do");
		foreach($data["col"] as $col=>$info){
			if(isset($info["virtual"]) && $info["virtual"]) continue;
			if(!is_numeric($col)){
				$data["col"][$col]["usage"][$filterS] = true;
				$info["usage"][$filterS] = true;
			}
			if(isset($info["data"]["col"])) $col = $info["data"]["col"];
			$usg=$filterS;
			
			if(isset($info["usage"]) && ((isset($info["usage"][$usg]) && $info["usage"][$usg]) || in_array($usg,$info["usage"]))){}else{continue;}
			
			$cols[$col] = $col;
		}
		if(isset($data["usecols"]))
		foreach($data["usecols"] as $col){
			$cols[$col] = $col;
		}
		return $cols;
	}
	private static function makeWhere(&$data,$filterS="DBVs"){	
	    $where = array();
		if(isset($data["where"])) $where = $data["where"];
		
		$filter = \AsyncWeb\Storage\Session::get("MDBV_filter_".$data["uid"]);
		if(!$filter) $filter=array();
		foreach ($filter as $key=>$info2){
			$checkOk = false;
			if(!isset($info2["col"])) continue;
			
			foreach($data["col"] as $col=>$info){
				if(isset($info["data"]["col"])) $col = $info["data"]["col"];
				$usg=$filterS;if(isset($info["usage"]) && ((isset($info["usage"][$usg]) && $info["usage"][$usg]) || in_array($usg,$info["usage"]))){}else{continue;}
				if($info2["col"] == $col) $checkOk = true;
			}
			if(!$checkOk) continue;
			$where[] = $info2;
		}
		return $where;
	}
	private static function makeOrder(&$data){
		$ret = array();
		if(isset($data["order"])) $ret=$data["order"];
		
		$prefix = @$data["prefix"];
		if(!$prefix) $prefix = "_";
		$uid = $data["uid"];
		if(!@$data["disable_order"]){
			if(array_key_exists("sort",$_REQUEST) && array_key_exists("col",$_REQUEST)){
				// najdi stlpec podla coho triedime
				$sorta = array();
				$savetosess = false;
				foreach ($data["col"] as $col=>$info){
					if(isset($info["data"]["col"])) $col = $info["data"]["col"];
					if(!array_key_exists("sort_id",$info)){
						$info["sort_id"] = $col;
					}
					
					$sort = "data_table_sort_${uid}_${prefix}_".$info["sort_id"];
					if($_REQUEST["col"] == $sort){
						$sorta[$uid][$prefix]["sort"] = $col;
						$type = "A";
						if($_REQUEST["sort"] == "D") $type = "D";
						$sorta[$uid][$prefix]["sort_type"] = $type;
						$savetosess = true;
					}
				}
				if($savetosess) \AsyncWeb\Storage\Session::set("__DATA_TABLE_Sort",$sorta);
				
				/* vynulovanie iteratora na zaciatok ked menime poradie*/
				$per_page=10;
				$start_from_zero = true;
			
				if(@$data["iter"]["per_page"]) $per_page = $data["iter"]["per_page"];
				$iter = \AsyncWeb\View\Iterator::getIterator("data_table_${prefix}_${uid}",$per_page);
				$iter->reset();
			}
			$sorta = \AsyncWeb\Storage\Session::get("__DATA_TABLE_Sort");
			if($sorta){
				$ret = array();
			}
			if(@$sorta[$uid][$prefix]["sort"]){
				$col = DB::myAddSlashes($sorta[$uid][$prefix]["sort"]);
				if($sorta[$uid][$prefix]["sort_type"] == "A"){
					$type = "asc";
				}else{
					$type = "desc";
				}
				$ret[$col]=$type;
			}
		}
		return $ret;
	}
	private static $form = array();
	public static function getMFResults($data=array()){
		if(@$data["useForms"] && (!MakeDBViewDirect::$form || !@MakeDBViewDirect::$form[$data["uid"]])){
			MakeDBViewDirect::generateMakeFormFile($data);
			MakeDBViewDirect::$form[$data["uid"]] = new \AsyncWeb\View\MakeForm(file_get_contents(MakeDBViewDirect::getMakeFormFile($data)));
		}
		if(@MakeDBViewDirect::$form[$data["uid"]]){
			return MakeDBViewDirect::$form[$data["uid"]]->show_results();
		}
	}
	public static $forcever = null;
	public static function make($data,$form=null){
		if(!$data) return;
		
		if(!@$data["vertical"]){
			if((isset($data["MakeDVView"]) && $data["MakeDVView"] == 5 ) || MakeDBViewDirect::$forcever == 5){
				return \AsyncWeb\View\MakeDBView::make($data,$form);
			}
		}
		if(isset($data["rights_display"]) && $data["rights_display"]){
		 if(!\AsyncWeb\Objects\Group::isInGroupId($data["rights_display"])) return false;
		}
		if($form){
			MakeDBViewDirect::$form[$data["uid"]] = $form;
		}
		if(isset($data["iter_per_page"])) $data["iter"]["per_page"] = $data["iter_per_page"];

		if(@$data["useForms"] && (!MakeDBViewDirect::$form || !@MakeDBViewDirect::$form[$data["uid"]])){
			MakeDBViewDirect::$form[$data["uid"]] = new \AsyncWeb\View\MakeForm($data);
		}
		
		if(@$data["useForms"] && @$data["allowInsert"] && @$_REQUEST["insert_data_".$data["uid"]]){
			
			$r = "";
			  $r.= MakeDBViewDirect::$form[$data["uid"]]->show_results();
		      $r.= MakeDBViewDirect::$form[$data["uid"]]->show("INSERT");
			  return $r;

		}
		if(@$data["useForms"] && @$data["allowUpdate"] && @$_REQUEST[$data["uid"]."___UPDATE1"]){
			
			$r = "";
			  $r.= MakeDBViewDirect::$form[$data["uid"]]->show_results();
		      $r.= MakeDBViewDirect::$form[$data["uid"]]->show("UPDATE2");
			  return $r;

		}
		
		MakeDBViewDirect::showFilterForm($data);
		MakeDBViewDirect::makeExport($data);
		if(@$data["vertical"]){
			return MakeDBViewDirect::makeVerticalTableView($data);
		}
		
		return MakeDBViewDirect::makeTableView($data);
	}
	private static function makeExport($data){
		if(@$_REQUEST["export"] == $data["uid"]){
			if($_REQUEST["export_type"] == "CSV"){
				MakeDBViewDirect::makeExportCSV($data);
			}
			if($_REQUEST["export_type"] == "HTML"){
				MakeDBViewDirect::makeExportHTML($data);
			}
			if($_REQUEST["export_type"] == "XML"){
				MakeDBViewDirect::makeExportXML($data);
			}
		}
	}
	private static function checkCSVInput($input){
		$outEncoding= "windows-1250";
		$input = iconv("utf-8",$outEncoding,$input);
		$input = str_replace("\n","",$input);
		$input = strip_tags($input);
		$input = addslashes($input);
		return $input;
	}
	private static function makeExportCSV($data){
		ob_clean();
		\AsyncWeb\HTTP\Header::send("Content-Type: text/csv");
		// It will be called downloaded.pdf
		if(@$data["export_file"]){
			\AsyncWeb\HTTP\Header::send('Content-Disposition: attachment; filename="'.$data["export_file"].'.csv"');
		}else{
			\AsyncWeb\HTTP\Header::send('Content-Disposition: attachment; filename="'.$data["uid"]."-".date("Ymd").'.csv"');
		}
		$sep1 = '"';
		$sep2 = ';';
		$line = "\n";
		
		// na prvy riadok daj mena stlpcov
		foreach ($data["col"] as $col=>$info){
			if(isset($info["data"]["col"])) $col = $info["data"]["col"];
			$usg="DBVs";if(isset($info["usage"]) && ((isset($info["usage"][$usg]) && $info["usage"][$usg]) || in_array($usg,$info["usage"]))){}else{continue;}

			if(@$info["function"]){continue;}
			if(@$info["virtual"] && isset($info["show"]) && !$info["show"]) continue;
			if(@$info["show"] === false) continue;
			if(@$info["do_not_export"] === true) continue;
			$info["name"] = Language::get($info["name"]);
			echo $sep1.MakeDBViewDirect::checkCSVInput($info["name"]).$sep1.$sep2;
		}
		echo $line;
		flush();
		
		// a potom dupmni db
		$cols = MakeDBViewDirect::makeCols($data,"DBVe");
		$where = MakeDBViewDirect::makeWhere($data,"DBVe");
		$order = MakeDBViewDirect::makeOrder($data);
		$res = DB::g($data["table"],$where,$offset=null,$count=null,$order,$cols);

		if(!$res){
			return "nastala chyba 0x00023847";
		}
		while($row = DB::fetch_assoc($res)){
			if(isset($data["rowwhere"])){
				$skip = false;
				foreach($data["rowwhere"] as $func){
					$eret = \AsyncWeb\System\\AsyncWeb\System\Execute::run($func,$row);
					if($eret===true){}else{$skip=true;}
				}
				if($skip) continue;
			}
			if(MakeDBViewDirect::checkDistinct($row,$data)) continue;
			
			foreach ($data["col"] as $col=>$prop){
				$usg="DBVs";if(isset($prop["usage"]) && ((isset($prop["usage"][$usg]) && $prop["usage"][$usg]) || in_array($usg,$prop["usage"]))){}else{continue;}
				if(isset($prop["data"]["col"])) $col = $prop["data"]["col"];
				if(@$prop["filter_export"])$prop["filter"] = $prop["filter_export"];
				if(@$prop["function"]){continue;}
				if(@$prop["virtual"] && isset($prop["show"]) && !$prop["show"]) continue;
				if(@$prop["show"] === false) continue;
				if(@$prop["do_not_export"] === true) continue;
				
				
				if(isset($prop["virtual"]) && $prop["virtual"]){
					$value = null;
					if(isset($prop["texts"]["default"])) $value = $prop["texts"]["default"];
					if(strpos($value,"PHP::")!==false){
						$value = \AsyncWeb\System\\AsyncWeb\System\Execute::run($value,$row);
					}
					
				}else{
					$value = $row[$col];
				}
						
				if(isset($prop["form"]["type"]) && $prop["form"]["type"] == "selectDB" && $prop["data"]["fromColumn"]){
					 $refcol = "id2";
					 if(isset($prop["data"]["refCol"])) $refcol = $prop["data"]["refCol"];
					 $r = DB::gr($prop["data"]["fromTable"],array($refcol=>$row[$col]));
					 if($r){
					  $value = MakeDBViewDirect::getInnerDBColConfig($r,$prop["data"]["fromColumn"]);
					 }
				}
				if(isset($prop["data"]["dictionary"]) && $prop["data"]["dictionary"]) $row[$col] = Language::get($value);
				echo $sep1.MakeDBViewDirect::checkCSVInput(MakeDBViewDirect::filter($value,@$prop["filter"],$row)).$sep1.$sep2;
			}
			echo $line;
			flush();
		}
		exit;
	}
	private static function makeExportXML($data){
		ob_clean();
		if(@$data["export_file"]){
			\AsyncWeb\HTTP\Header::send('Content-Disposition: attachment; filename="'.$data["export_file"].'.xml"');
		}else{
			\AsyncWeb\HTTP\Header::send('Content-Disposition: attachment; filename="'.$data["uid"]."-".date("Ymd").'.xml"');
		}

		
		$sep1 = '"';
		$sep2 = ';';
		$line = "\n";
		echo "<data>\n";
		flush();
		
		$cols = MakeDBViewDirect::makeCols($data,"DBVe");
		$where = MakeDBViewDirect::makeWhere($data,"DBVe");
		$order = MakeDBViewDirect::makeOrder($data);
		$res = DB::g($data["table"],$where,$offset=null,$count=null,$order,$cols);
		if(!$res){
			return "nastala chyba 0x00023847";
		}
		while($row = DB::fetch_assoc($res)){
			if(isset($data["rowwhere"])){
				$skip = false;
				foreach($data["rowwhere"] as $func){
					$eret = \AsyncWeb\System\\AsyncWeb\System\Execute::run($func,$row);
					if($eret===true){}else{$skip=true;}
				}
				if($skip) continue;
			}
			if(MakeDBViewDirect::checkDistinct($row,$data)) continue;
			
			echo "<item>";
			foreach ($data["col"] as $col=>$prop){			
				$usg="DBVs";if(isset($prop["usage"]) && ((isset($prop["usage"][$usg]) && $prop["usage"][$usg]) || in_array($usg,$prop["usage"]))){}else{continue;}

				if(isset($prop["data"]["col"])) $col = $prop["data"]["col"];
				if(@$prop["filter_export"])$prop["filter"] = $prop["filter_export"];
				if(@$prop["function"]){continue;}
				if(@$prop["virtual"] && isset($prop["show"]) && !$prop["show"]) continue;
				if(@$prop["show"] === false) continue;
				if(@$prop["do_not_export"] === true) continue;
//				$name = @$prop["name"];
//				if(!$name) $name = $col;
				
				$name = \AsyncWeb\Text\Texts::clear_($col);
				
				
				if(isset($prop["virtual"]) && $prop["virtual"]){
					$name = \AsyncWeb\Text\Texts::clear_($prop["name"]);
					$value = null;
					if(isset($prop["texts"]["default"])) $value = $prop["texts"]["default"];
					if(strpos($value,"PHP::")!==false){
						$value = \AsyncWeb\System\Execute::run($value,$row);
					}
					
				}else{
					$value = $row[$col];
				}
				
				if(isset($prop["form"]["type"]) && $prop["form"]["type"] == "selectDB" && $prop["data"]["fromColumn"]){
					 $refcol = "id2";
					 if(isset($prop["data"]["refCol"])) $refcol = $prop["data"]["refCol"];
					 $r = DB::gr($prop["data"]["fromTable"],array($refcol=>$row[$col]));
					 if($r){
					  $value = MakeDBViewDirect::getInnerDBColConfig($r,$prop["data"]["fromColumn"]);
					 }
				}
				
				if(isset($prop["data"]["dictionary"]) && $prop["data"]["dictionary"]) $value = Language::get($value);
				echo "<$name>".MakeDBViewDirect::checkCSVInput(MakeDBViewDirect::filter($value,@$prop["filter"],$row))."</$name>";
			}
			echo "</item>";
			echo $line;
			flush();
		}
		echo "</data>\n";

		exit;
	}
	private static function makeExportHTML($data){
		ob_clean();

		if(@$data["export_file"]){
			\AsyncWeb\HTTP\Header::send('Content-Disposition: attachment; filename="'.$data["export_file"].'.html"');
		}else{
			\AsyncWeb\HTTP\Header::send('Content-Disposition: attachment; filename="'.$data["uid"]."-".date("Ymd").'.html"');
		}

		
		if(isset($data["prefix"])){
			$prefix = $data["prefix"];
		}else{
			$prefix = "_";
		}
		$uid = $data["uid"];


		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.Language::getLang().'">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title>'.$uid.'</title>
</head>
<body><table id="data_table_'.$data["uid"].'" class="data_table_'.$prefix;
if(isset($data["bootstrap"])){echo " table";}
echo '">';
		
		echo '<thead><tr>';
		$j = 0;
		foreach ($data["col"] as $col=>$info){
			if(isset($info["data"]["col"])) $col = $info["data"]["col"];
			$usg="DBVs";if(isset($info["usage"]) && ((isset($info["usage"][$usg]) && $info["usage"][$usg]) || in_array($usg,$info["usage"]))){}else{continue;}
			if(@$info["function"]){continue;}
			if(@$info["virtual"] && isset($info["show"]) && !$info["show"]) continue;
			if(@$info["show"] === false) continue;
			if(@$info["do_not_export"] === true) continue;
			$j++;
				
			$info["name"] = Language::get($info["name"]);
			echo '<th class="data_table_'.$prefix.'_c'.$j.'" id="data_table_'.$prefix."_".$uid.'_'.$j.'">'.$info["name"].' </th>';
		}
		echo '</tr></thead><tbody>';
		
		flush();
		
		// a potom dupmni db
		$cols = MakeDBViewDirect::makeCols($data,"DBVe");
		$where = MakeDBViewDirect::makeWhere($data,"DBVe");
		$order = MakeDBViewDirect::makeOrder($data);
		$res = DB::g($data["table"],$where,$offset=null,$count=null,$order,$cols);

		if(!$res){
			return "nastala chyba 0x00020439";
		}
		while($row = DB::fetch_assoc($res)){
			if(isset($data["rowwhere"])){
				$skip = false;
				foreach($data["rowwhere"] as $func){
					$eret = \AsyncWeb\System\Execute::run($func,$row);
					if($eret===true){}else{$skip=true;}
				}
				if($skip) continue;
			}
			if(MakeDBViewDirect::checkDistinct($row,$data)) continue;
			
			$j++;
			echo '<tr id="row_'.$row["id"].'" class="data_table_'.$prefix.'_r'.($j%2).'">';
			$i = 0;
			foreach ($data["col"] as $col=>$prop){
			$usg="DBVs";if(isset($prop["usage"]) && ((isset($prop["usage"][$usg]) && $prop["usage"][$usg]) || in_array($usg,$prop["usage"]))){}else{continue;}

			if(isset($prop["data"]["col"])) $col = $prop["data"]["col"];
			if(@$prop["function"]){continue;}
			if(@$prop["virtual"] && isset($prop["show"]) && !$prop["show"]) continue;
			if(@$prop["show"] === false) continue;
			if(@$prop["do_not_export"] === true) continue;
				$i++;
				
				if(isset($prop["virtual"]) && $prop["virtual"]){
					$value = null;
					
					if(isset($prop["texts"]["default"])) $value = $prop["texts"]["default"];
					if(strpos($value,"PHP::")!==false){
						$value = \AsyncWeb\System\Execute::run($value,$row);
					}
					
				}else{
					$value = $row[$col];
				}
			
				if(isset($prop["form"]["type"]) && $prop["form"]["type"] == "selectDB" && $prop["data"]["fromColumn"]){
					 $refcol = "id2";
					 if(isset($prop["data"]["refCol"])) $refcol = $prop["data"]["refCol"];
					 $r = DB::gr($prop["data"]["fromTable"],array($refcol=>$row[$col]));
					 if($r){
					  $value = MakeDBViewDirect::getInnerDBColConfig($r,$prop["data"]["fromColumn"]);
					 }
				}
			
				if(isset($prop["data"]["dictionary"]) && $prop["data"]["dictionary"]) $row[$col] = Language::get($value);
				echo '<td class="data_table_'.$prefix.'_c'.$i.'" headers="data_table_'.$prefix."_".$uid.'_'.$i.'" title="'.strip_tags(Language::get($prop["name"])).'">'.MakeDBViewDirect::filter($value,@$prop["filter"],$row).'</td>';
			}
			echo '</tr>';
		}
		if($j==0){
			echo '<tr><td colspan="'.count($data["col"]).'">'.Language::get($data["no_data"])."</td></tr>";
		}
		
		echo '</tbody></table></body></html>';
		exit;
	}
	private static function checkFilterFormChange($data){
		
		if(array_key_exists("zrus_filter_".$data["uid"],$_REQUEST)){
			$key = $_REQUEST["zrus_filter_".$data["uid"]];
			$filter = \AsyncWeb\Storage\Session::get("MDBV_filter_".$data["uid"]);
			if(@$filter[$key]){
				unset($filter[$key]);
				\AsyncWeb\Storage\Session::set("MDBV_filter_".$data["uid"],$filter);
			}
			\AsyncWeb\HTTP\Header::s("location",array("show_filter_form_".$data["uid"]=>"1"));
			exit;
		}
		
		if(@$_REQUEST["filter_".$data["uid"]]){
			
			$filter = array();
			foreach ($_REQUEST["col"] as $key=>$value){
				if(!@$_REQUEST["value"][$key]) continue;
				
				$filter[$key] = array(
					"col"=>@$_REQUEST["col"][$key],
					"operator"=>@$_REQUEST["operator"][$key],
					"value"=>@$_REQUEST["value"][$key],
					"operator2"=>@$_REQUEST["operator2"][$key],
				);
				
			}
			\AsyncWeb\Storage\Session::set("MDBV_filter_".$data["uid"],$filter);
			if(@$_REQUEST["closeFilter"] != "on"){
			 \AsyncWeb\HTTP\Header::s("location",array("show_filter_form_".$data["uid"]=>"1"));
			}else{
			 echo '
			<html><head>
			<script>
			window.opener.location.reload();
			window.close();
/*			window.opener.reload();/**/
			</script>
			</head><body>Close this window</body></html>';
			}
			exit;
			
		}
	}
	public static function showFilterForm($data){
		
		MakeDBViewDirect::checkFilterFormChange($data);
		if(!@$_REQUEST["show_filter_form_".$data["uid"]]) return ;
		ob_clean();
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="sk">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Filter form '.$data["uid"].'</title>
</head>
<body onload="">
';
		echo '<form method="post" action="'.\AsyncWeb\System\Path::make(array('filter_'.$data["uid"]=>1,"AJAX"=>"")).'">';
		echo '<table><thead><tr><td>'.Language::get("column").'</td><td>'.Language::get("operator").'</td><td>'.Language::get("condition").'</td><td>'.Language::get("bindingconditions").'</td></tr></thead><tbody>';
		$filter = array();
		$filter = \AsyncWeb\Storage\Session::get("MDBV_filter_".$data["uid"]);
		if(!$filter) $filter = array();
		foreach ($filter as $key=>$value){
			echo '<tr><td>'.MakeDBViewDirect::makeColSelect($data,$value["col"]).'</td><td>'.MakeDBViewDirect::makeOpSelect($value["operator"]).'</td><td><input name="value[]" value="'.stripslashes($value["value"]).'"/></td></td><td>'.MakeDBViewDirect::makeOp2Select($value["operator2"]).'</td><td><a href="'.\AsyncWeb\System\Path::make(array('zrus_filter_'.$data["uid"]=>$key,"AJAX"=>"")).'">x</a></td></tr>';
		}
		echo '<tr><td>'.MakeDBViewDirect::makeColSelect($data).'</td><td>'.MakeDBViewDirect::makeOpSelect().'</td><td><input name="value[]"/></td><td></td></tr>';
		echo '<tr><td colspan="4"><input type="checkbox" checked="checked" name="closeFilter" id="closeFilter" /> <label for="closeFilter">'.Language::get("closefilter").'</label></td></tr>';
		echo '<tr><td colspan="4"><input type="submit" value="'.Language::get("continue").'"/></td></tr>';
		echo '</tbody></table>';
		echo '</form>';
		echo '</body></html>';
		
		exit;
	}
	private static function filterOn($data){
		$filter = array();
		$filter = \AsyncWeb\Storage\Session::get("MDBV_filter_".$data["uid"]);
		return (count($filter) > 0);
	}
	private static function makeColSelect($data,$default=""){
		$ret = "";
		$ret.='<select name="col[]">';
		foreach ($data["col"] as $col=>$info){
			if(isset($info["data"]["col"])) $col = $info["data"]["col"];
			$usg="DBVs";if(isset($info["usage"]) && ((isset($info["usage"][$usg]) && $info["usage"][$usg]) || in_array($usg,$info["usage"]))){}else{continue;}

			if(@$info["function"]){continue;}
			if(@$info["virtual"]) continue;
			$ret.='<option value="'.$col.'"';if($default==$col){$ret.=' selected="selected"';}$ret.='>'.$info["name"].'</option>';
		}
		$ret.='</select>';
		return $ret;
	}
	private static function makeOpSelect($default="like"){
		$ret = "";
		$ret.='<select name="operator[]">
		<option value="eq"';		if($default=="eq"){		$ret.=' selected="selected"';}$ret.='>=</option>
		<option value="lteq"';		if($default=="lteq"){	$ret.=' selected="selected"';}$ret.='>&lt;=</option>
		<option value="lt"';		if($default=="lt"){		$ret.=' selected="selected"';}$ret.='>&lt;</option>
		<option value="gteq"';		if($default=="gteq"){	$ret.=' selected="selected"';}$ret.='>&gt;=</option>
		<option value="gt"';		if($default=="gt"){		$ret.=' selected="selected"';}$ret.='>&gt;</option>
		<option value="like"';		if($default=="like"){	$ret.=' selected="selected"';}$ret.='>'.Language::get("contains").'</option>
		<option value="notlike"';	if($default=="notlike"){$ret.=' selected="selected"';}$ret.='>'.Language::get("deosnotcontains").'</option>
		<option value="null"';		if($default=="null"){$ret.=' selected="selected"';}$ret.='>'.Language::get("isempty").'</option>
		</select>';
		return $ret;
	}
	private static function makeOp2Select($default="and"){
		$ret = "";
		$ret.='<select name="operator2[]">
		<option value="and"';		if($default=="and"){$ret.=' selected="selected"';}$ret.='>'.Language::get("And").'</option>
		<option value="or"';	if($default=="or"){$ret.=' selected="selected"';}$ret.='>'.Language::get("Or").'</option>
		<option value="andlz"';		if($default=="andlz"){$ret.=' selected="selected"';}$ret.='>'.Language::get("And").' (</option>
		<option value="pzand"';		if($default=="pzand"){$ret.=' selected="selected"';}$ret.='>) '.Language::get("And").'</option>
		<option value="orlz"';	if($default=="orlz"){$ret.=' selected="selected"';}$ret.='>'.Language::get("Or").' (</option>
		<option value="pzor"';	if($default=="pzor"){$ret.=' selected="selected"';}$ret.='>) '.Language::get("Or").'</option>
		<option value="not"';	if($default=="not"){$ret.=' selected="selected"';}$ret.='> </option>

		</select>';
		//		<option value="pz"';		if($default=="pz"){$ret.=' selected="selected"';}$ret.='>)</option>
		return $ret;
	}
	private static function getInnerDBColConfig(&$row,&$colsettings){
		if(is_array($colsettings)){
			$ret = "";
			foreach($colsettings as $setting){
				if(is_array($setting)){
					switch($setting["type"]){
						case "data":
							$ret.= $setting["value"];
						break;
						case "col":
							$ret.= $row[$setting["value"]];
						break;
					}
				}else{
					$ret.=$row[$setting];
				}
			}
			return $ret;
		}else{
			return $row[$colsettings];
		}
		
	}
	private static $distinctvals = array();
	private static function checkDistinct(&$row,&$data){
		// return false if row is unique
		// return true if row is repeating
		if(!isset($data["distinct"])) return false;
		$val = "";
		foreach($data["distinct"] as $col){
			$val.=md5($row[$col])."-";
		}
		
		if(isset(MakeDBViewDirect::$distinctvals[$data["uid"]][$val])){ 
			return true; 
		}
		MakeDBViewDirect::$distinctvals[$data["uid"]][$val] = true;
		return false;
	}
	private static function makeTableView($data){
		$ret = "";
		$cols = MakeDBViewDirect::makeCols($data);
		$where = MakeDBViewDirect::makeWhere($data);
		$order = MakeDBViewDirect::makeOrder($data);

		$menu =  '';
	
		if(isset($data["prefix"])){
			$prefix = $data["prefix"];
		}else{
			$prefix = "_";
		}
		$uid = $data["uid"];
		
		if(!isset($data["rowwhere"])){
			if(isset($data["iter"]["per_page"])){
				$per_page = $data["iter"]["per_page"];
			}else{
				$per_page = 30;
			}
			$iter = \AsyncWeb\View\Iterator::getIterator("data_table_${prefix}_${uid}",$per_page);
		}
		
		$res = DB::g($data["table"],$where,null,null,$order,$cols);
		

		$count = 0;
		$count = DB::num_rows($res);
		if(!isset($data["rowwhere"])){
			$start = $iter->getStart();
			$perpage = $iter->getPerPage();
		}else{
			$start = null;
			$perpage = null;
		}
		$res = DB::g($data["table"],$where,$start,$perpage,$order,$cols);
		$reserr = DB::error();
		$per_page=10;
 		$start_from_zero = true;
 		if(isset($data["name"]) && $data["name"]) $nadpis = Language::get($data["name"]);
		if(array_key_exists("nadpis",$data))
		$ret .= '<div class="data_table_'.$prefix.'_header">'.$data["nadpis"].'</div>';
		
		if(!isset($data["rowwhere"])){
			if($iter->getPagesCount($count)>2)
			$ret .= $iter->show_bar($count,5,array(),"top_iter");
		}
		
		$ret .= '<table id="data_table_'.$data["uid"].'" class="data_table_'.$prefix;
		if(isset($data["bootstrap"])) $ret.=" table";
		$ret .= '">';
		
		$ret.= '<thead>';
		if(isset($data["popis"])) $ret.='<tr class="thead1"><th colspan="100" class="data_table_'.$prefix.'_description">'.Language::get($data["popis"]).'</th></tr>';
		if(@$data["show_export"] || @$data["show_filter"]){
		 $add = "";
		 
		
		$showinsert=true;
		if(isset($data["rights_insert"])) $data["rights"]["insert"] = $data["rights_insert"];
		
		if(isset($data["rights"])){
		
					  if(isset($data["rights"]["insert"])){                 // ak sa vyzaduju prava na vkladanie, tak ich over
					   if(\AsyncWeb\Objects\Group::exists($data["rights"]["insert"])){// ak existuje dane id skupiny
						
						if(!\AsyncWeb\Objects\Group::isInGroupId($data["rights"]["insert"])){$showinsert=false;}
					   }else{// inak existuje nazov skupiny
						if(!\AsyncWeb\Objects\Group::userInGroup($data["rights"]["insert"])) $showinsert=false;
					   }
					  }else{
					   $showinsert=false;
					  }
					  }
		 if(@$data["useForms"] && @$data["allowInsert"] && $showinsert){
		  $menu.= '<div class="menuitem"><a href="'.\AsyncWeb\System\Path::make(array('insert_data_'.$data["uid"]=>1,"AJAX"=>"")).'">'.Language::get("New item").'</a></div>';
		 }
		 
		 
		 if(@$data["show_export"]){
		  
		  $add = \AsyncWeb\System\Path::getMovingParams();
		  $menu .= '<div class="menuitem"><a href="'.\AsyncWeb\System\Path::make(array("export"=>$uid,"export_type"=>'CSV',"AJAX"=>"")).'">'.Language::get('Export to Excel').'</a></div>';
		  $menu .= '<div class="menuitem"><a href="'.\AsyncWeb\System\Path::make(array("export"=>$uid,"export_type"=>'HTML',"AJAX"=>"")).'">'.Language::get('Export to HTML').'</a></div>';
		  $menu .= '<div class="menuitem"><a href="'.\AsyncWeb\System\Path::make(array("export"=>$uid,"export_type"=>'XML',"AJAX"=>"")).'">'.Language::get('Export to XML').'</a></div>';

		 }
		 if(@$data["show_filter"]){
		  if(MakeDBViewDirect::filterOn($data)){
		   $menu.= '<div class="menuitem"><a href="'.\AsyncWeb\System\Path::make(array('show_filter_form_'.$data["uid"]=>1,"AJAX"=>"")).'" target="filter_win_'.$data["uid"].'" onclick="win=window.open(\'?show_filter_form_'.$data["uid"].'=1&amp;'.$add.'\',\'filter_win_'.$data["uid"].'\',\'width=650,height=300,resizable=yes,toolbar=no,menubar=no\');win.focus();return false;">'.Language::get("Edit filter").'</a></div>';
		  }else{
		   $menu.= '<div class="menuitem"><a href="'.\AsyncWeb\System\Path::make(array('show_filter_form_'.$data["uid"]=>1,"AJAX"=>"")).'" target="filter_win_'.$data["uid"].'" onclick="win=window.open(\'?show_filter_form_'.$data["uid"].'=1&amp;'.$add.'\',\'filter_win_'.$data["uid"].'\',\'width=650,height=300,resizable=yes,toolbar=no,menubar=no\');win.focus();return false;">'.Language::get("Create filter").'</a></div>';
		  }
		 }
		
		}
		
		if($menu) $menu = '<div class="table_menu" onmouseout="'.\AsyncWeb\Text\Texts::clear_($data["uid"]).'_Hide();" onmouseover="'.\AsyncWeb\Text\Texts::clear_($data["uid"]).'_Show();" style="position:absolute; background:#eeeeee; padding:3px; border: 1px solid black;display:none" id="'.\AsyncWeb\Text\Texts::clear_($data["uid"])."_menu".'" >'.$menu.'</div>';
		if($menu && \AsyncWeb\IO\File::exists("img/icons/folder.png")){
		 $menu = '<img src="/img/icons/folder.png" id="'.\AsyncWeb\Text\Texts::clear_($data["uid"]).'_control" onmouseout="'.\AsyncWeb\Text\Texts::clear_($data["uid"]).'_Hide();" onmouseover="'.\AsyncWeb\Text\Texts::clear_($data["uid"]).'_Show();" class="icon" width="20" height="20" alt="Menu" />'.$menu;
		 $menu .= '<script type="text/javascript">
		 var '.\AsyncWeb\Text\Texts::clear_($data["uid"]).'_hide_var = false;
		 $("#'.\AsyncWeb\Text\Texts::clear_($data["uid"]).'_menu").show();
		 function '.\AsyncWeb\Text\Texts::clear_($data["uid"]).'_Hide(){
		  '.\AsyncWeb\Text\Texts::clear_($data["uid"]).'_hide_var = false;
		  $("#'.\AsyncWeb\Text\Texts::clear_($data["uid"]).'_menu").hide();
		 }
		 function '.\AsyncWeb\Text\Texts::clear_($data["uid"]).'_Show(){
		  '.\AsyncWeb\Text\Texts::clear_($data["uid"]).'_hide_var = true;
		  $("#'.\AsyncWeb\Text\Texts::clear_($data["uid"]).'_menu").show();
		  
		 }
		 '.\AsyncWeb\Text\Texts::clear_($data["uid"]).'_Hide();
		 $("#'.\AsyncWeb\Text\Texts::clear_($data["uid"]).'_control").click(function() {
			if('.\AsyncWeb\Text\Texts::clear_($data["uid"]).'_hide_var == false){
				'.\AsyncWeb\Text\Texts::clear_($data["uid"]).'_Show();
			}else{
				'.\AsyncWeb\Text\Texts::clear_($data["uid"]).'_Hide();
			}
		 });
		 
		 </script>';
		 
		}
		
		if(@!$data["dont_show_data"]){
		$ret.='
		<tr>';
		$j = 0;
		foreach ($data["col"] as $col=>$info){
			if(isset($info["data"]["col"])) $col = $info["data"]["col"];
			$usg="DBVs";if(isset($info["usage"]) && ((isset($info["usage"][$usg]) && $info["usage"][$usg]) || in_array($usg,$info["usage"]))){}else{continue;}

			//if(@$info["function"]){continue;}
			if(@$info["virtual"] && isset($info["show"]) && !$info["show"]) continue;
			
			if(@$info["show"] === false) continue;
			$info["name"] = Language::get($info["name"]);
			$j++;
			$ret .= '<th class="data_table_'.$prefix.'_c'.$j.'" id="data_table_'.$prefix."_".$uid.'_'.$j.'">'.$info["name"].' ';
			if(!array_key_exists("sort_id",$info) && !is_numeric($col)){
				$info["sort_id"] = $col;
			}
			if(@$info["sort_id"]){
				$sort = "data_table_sort_${uid}_${prefix}_".$info["sort_id"];
				if(@!$info["virtual"] && @!$info["do_not_sort"] && @!$info["function"] &&!@$data["disable_order"]){
					$ret .= '<a href="'.\AsyncWeb\System\Path::make(array('sort'=>"A","col"=>$sort,"AJAX"=>"")).'" title="Zorad vzostupne">▲</a>';
					$ret .= '<a href="'.\AsyncWeb\System\Path::make(array('sort'=>"D","col"=>$sort,"AJAX"=>"")).'" title="Zorad zostupne">▼</a>';
				}
			}
			$ret .= '</th>';
		}
		if($menu){
		 $ret.='<th>'.$menu.'</th>';
		}
		$ret .= '</tr></thead><tbody>';

		
		if(!$res){
			$err= $reserr;
			if(MakeDBViewDirect::tryRepair($data)){
				$res = DB::g($data["table"],$where,$start,$perpage,$order,$cols);
			}
			if(!$res){
				\AsyncWeb\Storage\Log::log("MakeDBView Error",$err);
				return "nastala chyba 0x00010457";
			}
		}
	
		$j = 0;
		while($row = DB::fetch_assoc($res)){
			if(isset($data["rowwhere"])){
				$skip = false;
				foreach($data["rowwhere"] as $func){
					$eret = \AsyncWeb\System\Execute::run($func,$row);
					if($eret===true){}else{$skip=true;}
				}
				if($skip) continue;
			}
			
			if(MakeDBViewDirect::checkDistinct($row,$data)) continue;
			
			$j++;
			$ret .= '<tr id="row_'.$row["id"].'" class="data_table_'.$prefix.'_r'.($j%2).'">';
			$i = 0;
			$update=false;
			$delete=false;
			foreach ($data["col"] as $col=>$prop){
				$usg="DBVs";if(isset($prop["usage"]) && ((isset($prop["usage"][$usg]) && $prop["usage"][$usg]) || in_array($usg,$prop["usage"]))){}else{continue;}

				if(isset($prop["data"]["col"])) $col = $prop["data"]["col"];
				//if(@$prop["function"]) continue;
				if(@$prop["virtual"] && isset($prop["show"]) && !$prop["show"]) continue;
				if(@$prop["show"] === false) continue;
				$i++;
				switch(@$prop["function"]){
					case 'update':
					
			if(isset($data["rights_update"])) $data["rights"]["update"] = $data["rights_update"];

			if(isset($data["rights"])){
					  if(isset($data["rights"]["update"])){                 // ak sa vyzaduju prava na vkladanie, tak ich over
					   if(\AsyncWeb\Objects\Group::exists($data["rights"]["update"])){// ak existuje dane id skupiny
						if(!\AsyncWeb\Objects\Group::isInGroupId($data["rights"]["update"])) continue;
					   }else{// inak existuje nazov skupiny
						if(!\AsyncWeb\Objects\Group::userInGroup($data["rights"]["update"])) continue;
					   }
					  }else{
					   continue;
					  }
					  }
					
						$update=true;
						$ret.= '<td class="data_table_'.$prefix.'_c'.$i.'" headers="data_table_'.$prefix."_".$uid.'_'.$i.'" title="'.strip_tags(Language::get($prop["name"])).'"><a href="'.\AsyncWeb\System\Path::make(array($data["uid"]."___ID"=>$row["id"],$data["uid"]."___UPDATE1"=>"1","AJAX"=>"")).'"><img src="/img/update.png" width="20" height="20" alt="Update"></a></td>';
					break;
					case 'delete':
					
			if(isset($data["rights_delete"])) $data["rights"]["delete"] = $data["rights_delete"];

					if(isset($data["rights"])){
					  if(isset($data["rights"]["delete"])){                 // ak sa vyzaduju prava na vkladanie, tak ich over
					   if(\AsyncWeb\Objects\Group::exists($data["rights"]["delete"])){// ak existuje dane id skupiny
						if(!\AsyncWeb\Objects\Group::isInGroupId($data["rights"]["delete"])) continue;
					   }else{// inak existuje nazov skupiny
						if(!\AsyncWeb\Objects\Group::userInGroup($data["rights"]["delete"])) continue;
					   }
					  }else{
					   continue;
					  }
					  }
					
						$delete=true;
						$confirm_text = Language::get("confirm delete");
						$ret.= '<td class="data_table_'.$prefix.'_c'.$i.'" headers="data_table_'.$prefix."_".$uid.'_'.$i.'" title="'.strip_tags(Language::get($prop["name"])).'"><a onclick="confirm(\''.$confirm_text.'\')?ret=true:ret=false;return ret;" href="'.\AsyncWeb\System\Path::make(array($data["uid"]."___ID"=>$row["id"],$data["uid"]."___DELETE"=>"1","AJAX"=>"")).'"><img src="/img/delete.png" width="20" height="20" alt="Delete"></a></td>';
					break;
					default:
					 if($p = strpos($col,".")){
					  $col = substr($col,$p+1);
					 }
					 
					
					
				if(isset($prop["virtual"]) && $prop["virtual"]){
					$text = null;
					if(isset($prop["texts"]["default"])) $text = $prop["texts"]["default"];
					if(strpos($text,"PHP::")!==false){
						$text = \AsyncWeb\System\Execute::run($text,$row);
					}
					
				}else{
					$text = $row[$col];
				}
					
					if(isset($prop["form"]["type"]) && $prop["form"]["type"] == "selectDB" && $prop["data"]["fromColumn"]){
					 $refcol = "id2";
					 if(isset($prop["data"]["refCol"])) $refcol = $prop["data"]["refCol"];
					 $r = DB::gr($prop["data"]["fromTable"],array($refcol=>$row[$col]));
					 if($r){
					  $text = MakeDBViewDirect::getInnerDBColConfig($r,$prop["data"]["fromColumn"]);
					 }
					}
					
					
				
				
					if(isset($prop["data"]["dictionary"]) && $prop["data"]["dictionary"]) $text = Language::get($text);
					$ret.= '<td class="data_table_'.$prefix.'_c'.$i.'';
					
					if(@$prop["data"]["type"]) $ret.= " ".$prop["data"]["type"];
					if(@$prop["data"]["datatype"]) $ret.= " ".$prop["data"]["datatype"];
					
					$ret.='" headers="data_table_'.$prefix."_".$uid.'_'.$i.'" title="'.strip_tags(Language::get($prop["name"])).'">'.MakeDBViewDirect::filter($text,@$prop["filter"],$row).'</td>';

				}
			}
			
			
					if(isset($data["rights"])){
					  if(isset($data["rights"]["update"])){                 // ak sa vyzaduju prava na vkladanie, tak ich over
					   if(\AsyncWeb\Objects\Group::exists($data["rights"]["update"])){// ak existuje dane id skupiny
						if(!\AsyncWeb\Objects\Group::isInGroupId($data["rights"]["update"])) $update = true;
					   }else{// inak existuje nazov skupiny
						if(!\AsyncWeb\Objects\Group::userInGroup($data["rights"]["update"]))  $update = true;
					   }
					  }else{
					    $update = true;
					  }
					  }
					if(isset($data["rights"])){
					  if(isset($data["rights"]["delete"])){                 // ak sa vyzaduju prava na vkladanie, tak ich over
					   if(\AsyncWeb\Objects\Group::exists($data["rights"]["delete"])){// ak existuje dane id skupiny
						if(!\AsyncWeb\Objects\Group::isInGroupId($data["rights"]["delete"])) $delete = true;
					   }else{// inak existuje nazov skupiny
						if(!\AsyncWeb\Objects\Group::userInGroup($data["rights"]["delete"]))  $delete = true;
					   }
					  }else{
					    $delete = true;
					  }
					  }
			
			if(isset($data["allowUpdate"]) && $data["allowUpdate"] && !$update){
				$ret.= '<td class="data_table_'.$prefix.'_c'.$i.'" headers="data_table_'.$prefix."_".$uid.'_'.$i.'" title="'.strip_tags(Language::get("update")).'"><a href="'.\AsyncWeb\System\Path::make(array($data["uid"]."___ID"=>$row["id"],$data["uid"]."___UPDATE1"=>"1","AJAX"=>"")).'">';
				if(\AsyncWeb\IO\File::exists("img/update.png")){$ret.='<img src="/img/update.png" width="20" height="20" alt="'.Language::get("update").'" />';}else{$ret.=Language::get("update");}$ret.='</a></td>';
			}
			if(isset($data["allowDelete"]) && $data["allowDelete"] && !$delete){
				$confirm_text = Language::get("confirm delete");
				$ret.= '<td class="data_table_'.$prefix.'_c'.$i.'" headers="data_table_'.$prefix."_".$uid.'_'.$i.'" title="'.strip_tags(Language::get("delete")).'"><a onclick="confirm(\''.$confirm_text.'\')?ret=true:ret=false;return ret;" href="'.\AsyncWeb\System\Path::make(array($data["uid"]."___ID"=>$row["id"],$data["uid"]."___DELETE"=>"1","AJAX"=>"")).'">';
				if(\AsyncWeb\IO\File::exists("img/delete.png")){$ret.='<img src="/img/delete.png" width="20" height="20" alt="'.Language::get("delete").'" />';}else{$ret.=Language::get("delete");}$ret.='</a></td>';
			}
			$ret .= '</tr>';
		}
		if($j==0){
			if(!isset($data["no_data"])) $data["no_data"] = "-";
			if(!isset($data["rowwhere"])){
				$iter->reset();
			}
			if(@$data["no_data_col_count"]){
				$ret.='<tr class="data_table_'.$prefix.'_r1"><td class="no_data" colspan="'.$data["no_data_col_count"].'">'.Language::get($data["no_data"])."</td></tr>";
			}else{
				$ret.='<tr class="data_table_'.$prefix.'_r1"><td class="no_data" colspan="'.count($data["col"]).'">'.Language::get($data["no_data"])."</td></tr>";
			}
		}
		}
		
		$ret .= '</tbody></table>';
		if(!isset($data["rowwhere"])){
			if($iter->getPagesCount($count)>2)
			$ret .= $iter->show_bar($count,5,array(),"bottom_iter");
		}
		return $ret;
	}
	private static function makeVerticalTableView($data){
		$ret = "";
		$cols = MakeDBViewDirect::makeCols($data);
		$where = MakeDBViewDirect::makeWhere($data);
		$order = MakeDBViewDirect::makeOrder($data);

		$prefix = @$data["prefix"];
		if(!$prefix) $prefix = "_";
		$uid = $data["uid"];

		
  		if(isset($data["name"]) && $data["name"]) $nadpis = Language::get($data["name"]);
		
		if(array_key_exists("nadpis",$data) && $data["nadpis"])
		$ret .= '<div class="data_table_'.$prefix.'_header">'.$data["nadpis"].'</div>';
		if(array_key_exists("popis",$data) && $data["popis"])
		$ret .= '<div class="data_table_'.$prefix.'_description">'.Language::get($data["popis"]).'</div>';
		
		$ret .= '<table id="data_table_'.$data["uid"].'" class="data_table_'.$prefix;
		if(isset($data["bootstrap"])) $ret.= " table"; 
		$ret .= '">';
		
		$ret .= '<tbody>';

		$j = 0;
		$row = DB::gr($data["table"],$where,$order,$cols);
		if(!$row){
			$err = DB::error();
			\AsyncWeb\Storage\Log::log("MakeDBView Error","No row for vertical view: ".$err);
			return "ERROR 0x00009234 (No row selected for vertical view)";
		}
		$i = 0;
		foreach ($data["col"] as $col=>$prop){$i++;
			
			$usg="DBVs";if(isset($prop["usage"]) && ((isset($prop["usage"][$usg]) && $prop["usage"][$usg]) || in_array($usg,$prop["usage"]))){}else{continue;}
			if(isset($prop["data"]["col"])) $col = $prop["data"]["col"];
			$ret .= '<tr class="data_table_'.$prefix.'_r'.($i%2).'">';
			$ret .= '<td class="data_table_'.$prefix.'_i'.$i.' col-lg-2"><label class="control-label">'.Language::get($prop["name"]).'</label></td>';
			
			$text = $row[$col];
								
			if(isset($prop["form"]["type"]) && $prop["form"]["type"] == "selectDB" && $prop["data"]["fromColumn"]){
					 $refcol = "id2";
					 if(isset($prop["data"]["refCol"])) $refcol = $prop["data"]["refCol"];
					 $r = DB::gr($prop["data"]["fromTable"],array($refcol=>$row[$col]));
					 if($r){
					  $text = MakeDBViewDirect::getInnerDBColConfig($r,$prop["data"]["fromColumn"]);
					 }
			}
			if(isset($prop["data"]["dictionary"]) && $prop["data"]["dictionary"]) $text = Language::get($text);
			
			if(isset($prop["texts"]["perpend"]) && $prop["texts"]["perpend"]) $text = Language::get($prop["texts"]["prepend"])." ".$text;
			if(isset($prop["texts"]["after"]) && $prop["texts"]["after"]) $text.= " ".Language::get($prop["texts"]["after"]);
			
 		    $ret .= '<td class="data_table_'.$prefix.'_c'.$i.'" headers="data_table_'.$prefix."_".$uid.'_'.$i.'" title="'.strip_tags(Language::get($prop["name"])).'">'.MakeDBViewDirect::filter($text,@$prop["filter"],$row).'</td>';
			$ret .= '</tr>';
		}
		
		
		$ret .= '</tbody></table>';


		return $ret;
	}
	private static function execute($function,$params=null){
	  return \AsyncWeb\System\Execute::run($function,$params);
	}
	private static function filter($data,$filter,$row = array() ){
		$ret = "";

		switch (@$filter["type"]){
			case "php":
				if(!isset($filter["params"]) || !is_array($filter["params"])) $filter["params"] = array();
				$filter["params"]["row"] = $row;
				$ret = MakeDBViewDirect::execute($filter["function"],$filter["params"]);
			break;
			case "db":
				/**
					"filter"=>array("type"=>"db","table"=>"mytable","conds"=>array(),"where"=>"mycol","col"=>"select");
				*/
				$filter["conds"][$filter["where"]] = $data;
				$row2 = DB::gr($filter["table"],$filter["conds"]);
				
				if($row2){
					$ret = $row2[$filter["col"]];
				}
			break;
			case "or":
				/**
					"filter"=>array("type"=>"or","filters"=>array(
						array("type"=>"db","table"=>"mytable","conds"=>array(),"where"=>"mycol","col"=>"select"),
						array("type"=>"db","table"=>"mytable","conds"=>array(),"where"=>"mycol","col"=>"select"),
					));
				*/
				foreach($filter["filters"] as $f){
					$r = MakeDBViewDirect::filter($data,$f,$row);
					if($r){$ret=$r;break;}
				}
			break;
			case 'date':
				if($data){
					$ret.= date($filter["format"],\AsyncWeb\Date\Time::getUnix($data));
				}else{
					$ret.= "-";
				}
				break;
			case 'sprintf':
				$ret.=sprintf($filter["format"],$data);
				break;
			case 'number_format':
				$ret.= number_format($data,$filter["decimal"],$filter["desat_oddelocac"],$filter["oddelovac_tisicov"]);
				break;
			case 'path':
				if(!$data)$data = "-";
				$ret.= '<a href="'.$filter["src"].'">'.$data.'</a>';
				break;
			case 'href':
				if(!$data)$data = "-";
				$ret.= '<a href="'.$filter["src"].$row["id"].'">'.$data.'</a>';
				break;
			case 'hrefID2':
				if(!$data)$data = "-";
				if(!($col = @$filter["col"])){
					$col = "id2";
				}
				$ret.= '<a href="'.$filter["src"].$row[$col].'">'.$data.'</a>';
				break;
			case 'hrefID3':
				if(!$data)$data = "-";
				if(!($col = @$filter["col"])){
					$col = "id2";
				}
				$ret.= '<a href="'.$filter["src"].$row[$col].'/">'.$data.'</a>';
				break;
			case 'text':
				$ret.= nl2br($data);
				break;
			case 'extra_htmlspecialchars':
				$ret.= htmlspecialchars($data);
				break;
			case 'option':
				if(@$filter["option"][$data]){
					$ret.= @$filter["option"][$data];
				}else{
					$ret.= $data;
				}
				break;
			case 'add_after':
				$ret.= $data.$filter["data"];
				break;
			case 'add_before':
				$ret.= $filter["data"].$data;
				break;
			default:
				$ret.= $data;
		}
		if(@$filter["filter"]){
			$ret = MakeDBViewDirect::filter($ret,$filter["filter"],$row);
		}
		return $ret;

	}
	public static $repair = false;
	public static function tryRepair(&$data){
		if(!MakeDBViewDirect::$repair) return;
		$update = array();
		$cols = array();
		foreach($data["col"] as $col=>$arr){
			if(@$arr["function"]) continue;
			if(isset($arr["data"]["col"])) $col = $arr["data"]["col"];
			if(is_numeric($col)) continue;
			$update[$col] = "0";
		}
		if(isset($data["where"]))
		foreach(@$data["where"] as $k=>$col){
			if(is_numeric($k)) continue;
			if(is_array($col)){
				if(@$col["col"][0] == "-") continue;
				$update[$col["col"]] = "0";
			}else{
				$update[$k] = "0";
			}
		}
		$update["id2"] = "__TEST__";
		$update["od"] = \AsyncWeb\Date\Time::get();
		$update["do"] = 0;
		$update["edited_by"] = "__TEST__";
		$ret = DB::u($data["table"],$update["id2"],$update,$cols);
		if($ret){
			$table = DB::myAddSlashes($data["table"]);
			DB::query($q = "delete from `$table` where id2='__TEST__'");
		}
		return $ret;
	}
}

