<?php
////////////////////////////////////////////
//
// Created & designed by Ludovit Scholtz
// ludovit __ AT __ scholtz.sk
//
// 14.4.2015	{ is replaced by &#123; in textareas so that templates are not executed in forms 
//
// 23.2.2015 	Captcha moved to dependency recaptcha/php5-v2
//
// 23.2.2015	Namespace added
//
// 14.1.2015	Forked: MakeForm for AsyncWeb
//
// 20.10.2013	Added bootstrap functionality
//				Bugfix. Maxlength pri update
//
// 20.2.2013	table n2n support
//
// 31.8.2012	array("form"=>array("type"=>"value"),"data"=>array("col"=>"type"),"texts"=>array("value"=>"category"),"usage"=>array("MFi",)),
// 				$texts["value"] nie je modifikovaný na základe jazyka
//
// 1.4.2012		BugFix. opraveny datatype check, ktory teraz podporuje aj $item["data"]["type"]
//
// 16.3.2012	Captcha Support
//
// 13.11.2011	MakeForm v4
//				- jQuery support
//				- odstranenie zavislosti na XML súboroch
//				- zrusenie funkcnosti bez od-do
//
// edited 17.9.2011 Pridana podpora pre presmerovanie po vlozeni, editacii, alebo mazani na specialnu adresu  >> public static $redirectAfterSuccess = "?";
//                 Pouzitie: Make_Form::$redirectAfterSuccess = "?closeMenuEditor=1";
//
// edited 27.4.10 Bugfix, pri before update bol vykonany prilis neskoro
//
// edited 4.1.10 Pridana podpora pre vkladanie textu za textbox
//
// edited 4.1.10 Pridana podpora pre vkladanie defaultnych hodnot pri zobrazeni formularu z $_REUQUEST
//
// edited 3.1.10 Pridana podpora pre skonrolovanie udajov skriptom pred vlozenim polozky
//                  <execute> 
//  					<beforeInsert>PHP::Broker::beforeInsertNK</beforeInsert>
//  					<beforeUpdate>PHP::Broker::beforeUpdateNK</beforeUpdate>
//  					<beforeDelete>PHP::Broker::beforeDeleteNK</beforeDelete>
// 					</execute>
//    		funkcia ma vratit true, ak ma vratit "1" ak je uspesna, pripadne Exception("chyba"), pripadne "0" ak sa nema vykonat akcia
//
// edited 1.9.09 Upravene radio, tak aby options boli na jednom riadku
//
// edited 18.01.08 bugfix date_string
//
// edited 10.11.07 zmeneny sposob priradovania datumu
//						do teraz 2008-01-01 ( rano )
//						od teraz 2008-01-01 = 2008-01-01T23:59:59
//
// edited 9.11.07 pridana podpora pre vykonanie skriptu po vlozeni/update/zruseni polozky
//                  <execute> 
//  					<onInsert>PHP::Broker::onInsertNK</onInsert>
//  					<onUpdate>PHP::Broker::onUpdateNK</onUpdate>
//  					<onDelete>PHP::Broker::onDeleteNK</onDelete>
// 					</execute>
//
// pozor na PHP::\AsyncWeb\Date\Time::get() nie PHP::time
//
// edited 4.11.07 pridana podpora pre default hodnotu v selectDB
//
// edited 16.8.07 pridana podpora pre db/use_od_do>id2 
//
// edited 31.7.07 pridana podpora pre db/use_od_do
//
// edited 8.9.06 xhtml validny vystup
//               fixed bug pre select v editacii bol stale zapnuty disable
// 
// 3.9.07
//
// v 3.0
//  - zmenene extendovanie db
//  - zmenene overovanie prav
//  - pristupovanie do db
//  - zmenene logovanie
//  - zmenene zobrazovanie sprav go()
//
// 26.2.05
// v 2.2 (started 3.6.05)
// edited 23.7.05
// edited 31.7.05 bugfix checkbox pre update
//                pridana podpora pre datum .. <data><type>date</type><minlength>0</minlength><maxlength>200</maxlength><format>Y-m-d</format></data>
//                fixed bug pre mozzilu, na urobenie auto checkboxu (pridanie k _CHANGED aj id
//                fixed bug ked sa updatovalo, tak to nechcelo ked bol vybraty unique, a chcel zmenit na vlastnu hodnotu
// edited  6.8.05 Select pre update a delete bude mat omedzenu dlzku. Nastavenie stylov.
// edited  9.8.05 pridana hodnota pre funkciu show() ALL_UPDATE_FIRST .. zobrazi vsetky ponuky, ale update zobrazi pred insert
//                pridana podpora pre onSubmit
//                pridana podpora pre XStandardLite, ako htmlText
//                fixed bug. Pri update nezobrazovalo pri input maxlength attribut
// 
/**

Prava..
$data["expectRight"] == $data["insertRight"] || $data["updateRight"] || $data["deleteRight"];

ak $data["insertRight"] == true tak bude pokracovat vo vkladani ak 
 $this->rights[$this->data["insertRight"]] je true

..

$this->db->num_rows
Messages::getInstance()->mes

Validate::check_input
Validate::check_input


        <show>
            <data type="column" table="category" colId="id2">
                <column_name>category</column_name>
                <external_column_name>name</external_column_name>
                <where>
                    <col name="id">1</col>
                </where>
            </data>
            <data type="text"> :: </data>
            <data type="column" table="produkt_v_predaji" colId="id2">
                <data type="column" table="produkt" colId="id2">
                    <external_column_name>produkt</external_column_name>
                    <column_name>meno</column_name>
                    <where>
                        <col name="id">1</col>
                    </where>
                </data>
                <external_column_name>produkt</external_column_name>
                <column_name>object</column_name>
                <where>
                    <col name="id">1</col>
                </where>
            </data>
            <data type="column">
                <column_name>category</column_name>
            </data>
        </show>

*/
namespace AsyncWeb\View;
use AsyncWeb\DB\DB;
use AsyncWeb\Objects\Group;
use AsyncWeb\Storage\Log;
use AsyncWeb\Text\Messages;
use AsyncWeb\Text\Validate;
use AsyncWeb\Text\Texts;
use AsyncWeb\System\Language;
use AsyncWeb\System\Path;
use AsyncWeb\HTTP\Header;
use AsyncWeb\Frontend\URLParser;

class MakeForm{
 public static $redirectAfterSuccess = "?";
 public $merged = false;
 private $data = array();
 private $xmlData;
 public $exception;
 private $item = null;
 private $aditional_where = "1";
 private $where = array();
 private $lang;

 private $db = null;
 private $help_pic = "/img/help.gif";
 public static $captchaPublickey = "6Ld3BM8SAAAAAD_hNiRI8zGZ2x3dbsubJrQRw7He";
 public static $captchaPrivatekey = "6Ld3BM8SAAAAAG-eBDkl2cjuSR8Xsfm17xXlHI74";
 
 private $wait = 0;
 private static $addJQuery = false;
 private static $addJS = true;
 public static $N2NData = array();
 public function __construct($data=array(),$merged=null){
	if(!$data) {
		Messages::die_error("No data supplied to the form");
		exit;
	}
	$this->data = $data;
	$this->data["uid"] = Texts::clear_($this->data["uid"]);

	if(isset($this->data["tableN2N"]) && isset($this->data["tableN2Ncol"]) && isset($this->data["tableN2Nvalue"]) && isset($this->data["tableN2Ncol2"])){
		MakeForm::$N2NData = array(
		
		"table" => @$data["table"],
 		"tableN2N" => @$data["tableN2N"],
 		"tableN2Ncol" => @$data["tableN2Ncol"],
 		"tableN2Nvalue" => @$data["tableN2Nvalue"],
 		"tableN2Ncol2" => @$data["tableN2Ncol2"],
 		"tableN2Ncol2Textcol" => @$data["tableN2Ncol2Textcol"],
 		"tableN2Ncol2Where" => @$data["tableN2Ncol2Where"],

		);
	 	$row = DB::qbr($this->data["tableN2N"],array("where"=>array(array("col"=>$this->data["tableN2Ncol2"],"op"=>"is","value"=>null))));
		if($row){
			$this->insertingN2N = MakeForm::$N2NData["id2"] = $row["id2"];
			$this->data["execute"]["onInsert"] = "PHP::MakeForm::N2NOnInsert()";
			// musim vlozit novy zaznam
			if(isset($this->data["tableN2Ncol2Where"])){
				foreach($this->data["tableN2Ncol2Where"] as $k=>$v){
					$this->data["where"][] = array("col"=>$k,"op"=>"eq","value"=>$v);
				}
			}
		}else{
			$res = DB::qb($data["tableN2N"],array("where"=>array($this->data["tableN2Ncol"] => $this->data["tableN2Nvalue"]),"cols"=>$this->data["tableN2Ncol2"]));
			$i = 0;
			if(DB::num_rows($res) > 0){
				$this->data["where"][] = array("col"=>"-(");
				while($row=DB::f($res)){$i++;
					if($i>1) $this->data["where"][] = array("col"=>"-or");
					$this->data["where"][] = array("col"=>"id2","op"=>"eq","value"=>$row[$this->data["tableN2Ncol2"]]);
				}
				$this->data["where"][] = array("col"=>"-)");
			}else{
				$this->data["where"][] = array("col"=>"id2","op"=>"eq","value"=>md5(uniqid()));
			}
			$this->data["execute"]["beforeDelete"] = "PHP::MakeForm::N2NBeforeDelete()";
			
		}
				 
				 
		$this->data["col"][] = array("form"=>array("type"=>"submitResetCancel"),"texts"=>array("insert"=>"MF_insert","update"=>"MF_update","delete"=>"MF_delete","reset"=>"MF_reset"),"usage"=>array("MFi","MFu","MFd"));

		 
	}
	
	$this->merged=$merged;
	if(isset($this->data["where"])){
		foreach($this->data["where"] as $k=>$v){
			if(is_array($v)){
				if(isset($v["value"])){
					$this->data["where"][$k]["value"] = $this->getText($v["value"]);
				}
			}else{
					$this->data["where"][$k] = $this->getText($v);
			}
		}
	}
	foreach($this->data["col"] as $k=>$col){
		if(!isset($col["usage"])){
			if(is_numeric($k)){
				$this->data["col"][$k]["usage"] = array("MFi","MFu","MFd");
			}else{
				$this->data["col"][$k]["usage"] = array("MFi","MFu","MFd","DBVs","DBVe");
			}
		}
		
		if(!isset($col["data"]["col"]) && !is_numeric($k)) $this->data["col"][$k]["data"]["col"] = $k;
		if(!isset($col["form"]["type"])){
			if(isset($col["filter"]["type"]) && $col["filter"]["type"] == "option"){
				$this->data["col"][$k]["form"]["type"] = "select";
			}else{
				$this->data["col"][$k]["form"]["type"] = "textbox";
			}
		}else{
			if($col["form"]["type"] == "file"){
				$this->enctype = "multipart/form-data";
				$this->data["enctype"] = "multipart/form-data";
			}
		}
		
		// skontroluj, ci datatype date je prevzaty zo starej verzie
		if(!isset($col["data"]["datatype"]) && isset($col["filter"]["type"]) && $col["filter"]["type"] == "date"){
			$this->data["col"][$k]["data"]["datatype"] = "date";
		}
		
	}
	if(isset($this->data["rights_insert"])){
		if(!isset($this->data["rights"])) $this->data["rights"] = array();
		$this->data["rights"]["insert"] = $this->data["rights_insert"];
	}
	if(isset($this->data["rights_update"])){
		if(!isset($this->data["rights"])) $this->data["rights"] = array();
		$this->data["rights"]["update"] = $this->data["rights_update"];
	}
	if(isset($this->data["rights_delete"])){
		if(!isset($this->data["rights"])) $this->data["rights"] = array();
		$this->data["rights"]["delete"] = $this->data["rights_delete"];
	}
	if(isset($this->data["iter_per_page"])) $this->data["iter"]["per_page"] = $this->data["iter_per_page"];
	
	if(!$merged && isset($this->data["col"])){
		$submit = false;
		foreach($this->data["col"] as $col){
			if(!isset($col["form"])) continue;
			if($col["form"]["type"] == "submitReset" || $col["form"]["type"] == "submit" || $col["form"]["type"] == "submitResetCancel") $submit = true;
		}
		if(!$submit){
		 	$this->data["col"][] = array("form"=>array("type"=>"submitReset"),"texts"=>array("insert"=>"MF_insert","update"=>"MF_update","delete"=>"MF_delete","reset"=>"MF_reset"),"usage"=>array("MFi","MFu","MFd"));
		}
		
		
	}
	
	if((isset($this->data["allowInsert"]) && $this->data["allowInsert"]) 
	|| (isset($this->data["allowUpdate"]) && $this->data["allowUpdate"] )
	|| (isset($this->data["allowDelete"]) && $this->data["allowDelete"])){
		$this->data["useForms"] = true;
	}
	/*if(!$data["table"]){
		Messages::die_error("Configuration error 0x319487189.");exit;
	}/**/

	// zkontroluj ci bol odoslany formular
  	if(!$merged) $this->check_update();
    if(MakeForm::$addJQuery){
		\AsyncWeb\HTML\Headers::add_script(null,"/js/jquery.js");
	}
	if(MakeForm::$addJS){
		\AsyncWeb\HTML\Headers::add_script(null,"/js/date.js");
		\AsyncWeb\HTML\Headers::add_script(null,"/js/format_input.js");
	}
 }
 public static function N2NBeforeDelete($r){
	$row=$r["r"];
	if(isset(MakeForm::$N2NData["tableN2N"]) && isset(MakeForm::$N2NData["tableN2Ncol"]) && isset(MakeForm::$N2NData["tableN2Nvalue"]) && isset(MakeForm::$N2NData["tableN2Ncol2"])){
		// delete fron n2n table
		DB::delete(MakeForm::$N2NData["tableN2N"],array(MakeForm::$N2NData["tableN2Ncol"]=>MakeForm::$N2NData["tableN2Nvalue"],MakeForm::$N2NData["tableN2Ncol2"]=>$row["id2"]));
		// if it is last occurance in n2n table, delete from source table
		$nrow = DB::qbr(MakeForm::$N2NData["tableN2N"],array("cols"=>array("c"=>"count(`id2`)"),"where"=>array(MakeForm::$N2NData["tableN2Ncol2"]=>$row["id2"])));
		if(!$nrow || !$nrow["c"]){
			DB::delete(MakeForm::$N2NData["table"],$row["id2"]);
		}
		//do not process with delete
		\AsyncWeb\HTTP\Header::s("location",array("REMOVE_VARIABLES"=>"1"));exit;
		return false;
	}
 }
 public static function N2NOnInsert($r){
	$row=$r["row"];
	DB::u(MakeForm::$N2NData["tableN2N"],MakeForm::$N2NData["id2"],array(MakeForm::$N2NData["tableN2Ncol2"]=>$row["id2"]));
 }
 /**
  * Checks for insert update or delete
  */
 public function check_update(){
	try{
		if(isset($this->data["where"]) && is_array($this->data["where"])){
			foreach ($this->data["where"] as $k=>$v){
			  $this->where[$k] = $this->filters($v,null,false);
			}
		}
	   $doo = false;
	   
	   $doo = $doo || $this->checkCancel();
	   $doo = $doo || $this->checkInsert();
	   $doo = $doo || $this->checkUpdate();
	   $doo = $doo || $this->checkDelete();
	   return $doo;
	}catch(\Exception $e){
		$this->exception = $e;
	}
	return false;
 }
 public function performAction(){
	try{
		$doo = false;
		$doo = $doo || $this->doInsert();
		$doo = $doo || $this->doUpdate();
		$doo = $doo || $this->doDelete();
		return $doo;
	}catch(\Exception $e){
		$this->exception = $e;
	}
	return false;
 }
 /**
 * gets the text of the exception
 * 
 *@input $item Column data
 *@input $exception dataTypeException | minLengthException | maxLengthException | uniqueException | fileExistsException | errorWhileMovingFile | minNumException | maxNumException
 */
 private function getExceptionText(&$item,$exception){
  $text = "";
  
  switch($exception){
   case 'dataTypeException':
    if(isset($item["texts"]["exception-$exception"]) && $item["texts"]["exception-$exception"]) return $this->getText($item["texts"]["exception-$exception"]);
    if(isset($item["texts"]["exceptions"]["$exception"])) return $this->getText($item["texts"]["exceptions"]["$exception"]);
    if(isset($this->data["$exception"])) return $this->getText($this->data["$exception"]);
    if(!$text) $text = $this->getText("MF_$exception");
    if(!$text || $text == "MF_$exception") $text = Language::get("Wrong data type has been supplied");
   break;
   case 'minLengthException':
    if(isset($item["texts"]["exception-$exception"]) && $item["texts"]["exception-$exception"]) return $this->getText($item["texts"]["exception-$exception"]);
    if(isset($item["texts"]["exceptions"]["$exception"])) return $this->getText($item["texts"]["exceptions"]["$exception"]);
    if(isset($this->data["$exception"])) return $this->getText($this->data["$exception"]);
    if(!$text) $text = $this->getText("MF_$exception");
    if(!$text || $text=="MF_$exception") $text = Language::get("Input is too short");
   break;
   case 'maxLengthException':
    if(isset($item["texts"]["exception-$exception"]) && $item["texts"]["exception-$exception"]) return $this->getText($item["texts"]["exception-$exception"]);
    if(isset($item["texts"]["exceptions"]["$exception"])) return $this->getText($item["texts"]["exceptions"]["$exception"]);
    if(isset($this->data["$exception"])) return $this->getText($this->data["$exception"]);
    if(!$text) $text = $this->getText("MF_$exception");
    if(!$text || $text=="MF_$exception") $text = Language::get("Input is too long");
   break;
   case 'uniqueException':
    if(isset($item["texts"]["exception-$exception"]) && $item["texts"]["exception-$exception"]) return $this->getText($item["texts"]["exception-$exception"]);
    if(isset($item["texts"]["exceptions"]["$exception"])) return $this->getText($item["texts"]["exceptions"]["$exception"]);
    if(isset($this->data["$exception"])) return $this->getText($this->data["$exception"]);
    if(!$text) $text = $this->getText("MF_$exception");
    if(!$text || $text=="MF_$exception") $text = Language::get("Value must be unique. This value is already in the DB.");
   break;
   case 'fileExistsException':
    if(isset($item["texts"]["exception-$exception"]) && $item["texts"]["exception-$exception"]) return $this->getText($item["texts"]["exception-$exception"]);
    if(isset($item["texts"]["exceptions"]["$exception"])) return $this->getText($item["texts"]["exceptions"]["$exception"]);
    if(isset($this->data["$exception"])) return $this->getText($this->data["$exception"]);
    if(!$text) $text = $this->getText("MF_$exception");
    if(!$text || $text=="MF_$exception") $text = Language::get("File with the same name already exists");
   break;
   case 'fileNotAllowed':
    if(isset($item["texts"]["exception-$exception"]) && $item["texts"]["exception-$exception"]) return $this->getText($item["texts"]["exception-$exception"]);
    if(isset($item["texts"]["exceptions"]["$exception"])) return $this->getText($item["texts"]["exceptions"]["$exception"]);
    if(isset($this->data["$exception"])) return $this->getText($this->data["$exception"]);
    if(!$text) $text = $this->getText("MF_$exception");
    if(!$text || $text=="MF_$exception") $text = Language::get("File cannot be processed because of security reasons");
   break;
   case 'errorWhileMovingFile':
    if(isset($item["texts"]["exception-$exception"]) && $item["texts"]["exception-$exception"]) return $this->getText($item["texts"]["exception-$exception"]);
    if(isset($item["texts"]["exceptions"]["$exception"])) return $this->getText($item["texts"]["exceptions"]["$exception"]);
    if(isset($this->data["$exception"])) return $this->getText($this->data["$exception"]);
    if(!$text) $text = $this->getText("MF_$exception");
    if(!$text || $text=="MF_$exception") $text = Language::get("Error occured while copying file");
   break;
   case 'minNumException':
    if(isset($item["texts"]["exception-$exception"]) && $item["texts"]["exception-$exception"]) return $this->getText($item["texts"]["exception-$exception"]);
    if(isset($item["texts"]["exceptions"]["$exception"])) return $this->getText($item["texts"]["exceptions"]["$exception"]);
    if(isset($this->data["$exception"])) return $this->getText($this->data["$exception"]);
    if(!$text) $text = $this->getText("MF_$exception");
    if(!$text || $text=="MF_$exception") $text = Language::get("Number is too low");
   break;
   case 'maxNumException':
    if(isset($item["texts"]["exception-$exception"]) && $item["texts"]["exception-$exception"]) return $this->getText($item["texts"]["exception-$exception"]);
    if(isset($item["texts"]["exceptions"]["$exception"])) return $this->getText($item["texts"]["exceptions"]["$exception"]);
    if(isset($this->data["$exception"])) return $this->getText($this->data["$exception"]);
    if(!$text) $text = $this->getText("MF_$exception");
    if(!$text || $text=="MF_$exception") $text = Language::get("Number is too high");
   break;
   case "enumTypeException":
    if(isset($item["texts"]["exception-$exception"]) && $item["texts"]["exception-$exception"]) return $this->getText($item["texts"]["exception-$exception"]);
    if(isset($item["texts"]["exceptions"]["$exception"])) return $this->getText($item["texts"]["exceptions"]["$exception"]);
    if(isset($this->data["$exception"])) return $this->getText($this->data["$exception"]);
    if(!$text) $text = $this->getText("MF_$exception");
    if(!$text || $text=="MF_$exception") $text = Language::get("Wrong value has been entered");;
   break;
   case "captchaTypeException":
    if(isset($item["texts"]["exception-$exception"]) && $item["texts"]["exception-$exception"]) return $this->getText($item["texts"]["exception-$exception"]);
    if(isset($item["texts"]["exceptions"]["$exception"])) return $this->getText($item["texts"]["exceptions"]["$exception"]);
    if(isset($this->data["$exception"])) return $this->getText($this->data["$exception"]);
    if(!$text) $text = $this->getText("MF_$exception");
    if(!$text || $text=="MF_$exception") $text = Language::get("Captcha is not correct");;
   break;
  }
  return $text;
 }
 
 /**
 * Checks if data format in the form is correct
 *
 * On error throws an exception
 * 
 * $item["datatype"]  date | date_string | number | check_input::types
 */
 private function checkRightDataFormat(&$item,$name1,$update_ignore = false, $form_name=""){
	if(isset($item["data"]["datatype"])){
		$data_type = $item["data"]["datatype"];
	}elseif(isset($item["data"]["type"])){
		$data_type = $item["data"]["type"];
	}else{
		$data_type = "string";
	}
	$form_type = "";
	if(isset($item["form"]["type"])){
		$form_type = $item["form"]["type"];
	}
	if($form_type == "captcha"){
		$captcha = new \reCaptcha\Captcha();
		$captcha->setPrivateKey(MakeForm::$captchaPrivatekey);
		$captcha->setPublicKey(MakeForm::$captchaPublickey);
		$response = $captcha->check();
		
		if (!$response->isValid()) {
            $error = $resp->error;
			\AsyncWeb\Storage\Log::log("CaptchaError",$error);
			throw new \Exception($this->getExceptionText($item,"captchaTypeException"));
        }
	}
	if(!$data_type) return;
    // zkontroluj ci ma spravny format
    if($data_type == "date"){
	 if(URLParser::v($name1)){
	  if(URLParser::v($name1) == -1){
       $this->item = $item;
	   throw new \Exception($this->getExceptionText($item,"dataTypeException"));
	  }
	 }
    }elseif($data_type == "date_string"){
	  if(URLParser::v($name1) == -1){
       $this->item = $item;
 	   throw new \Exception($this->getExceptionText($item,"dataTypeException"));
	  }
      if(!Validate::check_input(@URLParser::v($name1),"number")){
       $this->item = $item;
       throw new \Exception($this->getExceptionText($item,"dataTypeException"));
      }
    }elseif($data_type == "number"){
	  $name1val = str_replace(",",".",URLParser::v($name1));
      $name1val = str_replace(" ","",$name1val);
	  if(isset($item["data"]["allowNull"]) && !$name1val){
		return;
	  }
	  $var = $name1val;
	  if(!Validate::check_input($var,"number")){
	   $this->item = $item;
       throw new \Exception($this->getExceptionText($item,"dataTypeException"));
      }
      if(!$name1val) $name1val = 0;
	  
	  
	  // over velkost cisla
	  if(isset($item["data"]["minnum"])){
	   if($name1val < (double) $item["data"]["minnum"]){
        $this->item = $item;
        throw new \Exception($this->getExceptionText($item,"minNumException"));
       }
	  }
     
      if(isset($item["data"]["maxnum"])){
       if($name1val > (double)$item["data"]["maxnum"]){
        $this->item = $item;
        throw new \Exception($this->getExceptionText($item,"maxNumException"));
       }
	  }
     }elseif($data_type=="enum"){
		
		if(!isset($item["filter"]["option"][URLParser::v($name1)])){
		 if(URLParser::v($name1) == "0" && isset($item["data"]["allowNull"]) && $item["data"]["allowNull"]){
		  // allow null
		 }else{
 		  $this->item = $item;
		  throw new \Exception($this->getExceptionText($item,"enumTypeException"));
		 }
		}
	 }else{
	  $var = @URLParser::v($name1);
	  if(!Validate::check_input($var,$data_type)){
		$this->item = $item;
       throw new \Exception($this->getExceptionText($item,"dataTypeException"));
      }
     }
	
    if(isset($item["data"]["minlength"])){
     if(mb_strlen(URLParser::v($name1)) < (int)$item["data"]["minlength"]){
      $this->item = $item;
      throw new \Exception($this->getExceptionText($item,"minLengthException"));
     }
	}
	
    // zkontroluj ci ma spravnu dlzku
    if(isset($item["data"]["maxlength"])){
     if(mb_strlen(URLParser::v($name1),'UTF-8') > (int)$item["data"]["maxlength"]){
      $this->item = $item;
      throw new \Exception($this->getExceptionText($item,"maxLengthException"));
     }
	}
	
    // skontroluj ci ma byt unikatny
    if(isset($item["data"]["unique"]) && $item["data"]["unique"]){
	 $row = DB::gr($this->data["table"],array($item["data"]["col"]=>URLParser::v($item)));
	 if($row){
	  if(!(null!==URLParser::v($form_name."___ID")) || ($row["id"] != URLParser::v($form_name."___ID")) || ($row["id"] == URLParser::v($form_name."___ID") && $update_ignore==false)){
       $this->item = $item;
       throw new \Exception($this->getExceptionText($item,"uniqueException"));
	  }
	 }
    }
    return true;
 }
/**
* This function makes the input filters and parses php
* also cleans the number format from spaces and replaces , to .
*
* $value = PHP::Time::get(), returns timestamp
*
* type = date | date_string | number
*/
 private function filters($value,$type = null,$safe=true){
  if($type == "date"){
   $ret = $this->convertDate($value,false);
   return $ret;
  }

  if($type == "date_string"){
	return date("Y-m-d",\AsyncWeb\Date\Time::getUnix($this->convertDate($value,false)));
  }
 
  if($type == "number"){
  	$value = str_replace(" ","",$value);
  	$value = str_replace(",",".",$value);
  }
  
  if(!$safe){
   if(($val = $this->execute($value))!==false){return $val;}
  }
  return $value;
 }
 /**
 * This function converts str2time or time2str
 * also parses date 2010-30-10 as time  2010-30-10T23:59:59
 *
 *@input $from true|false If false, than input time is string (str2time). 
 */
 private function convertDate($time, $from, $format="Y-m-d"){ 
  // tato funkcia skonvertuje cas UNIXovsky na normalny
  // from.. ak true, tak $time je unix, ak nie, tak je zadany vo formate $format
  if(!$format) $format = "Y-m-d";
  if($from){
   if(!$time) return "";
   if($time <= 0) return '';
   // ak nam nechce vratit rovnaky vysledok po strtotime, tak nastav zachranny rezim
   $ret = @date($format, \AsyncWeb\Date\Time::getUnix($time));
   
   if(\AsyncWeb\Date\Time::get(strtotime($ret)) != $time){
   	$ret = @date("c",\AsyncWeb\Date\Time::getUnix($time));//ISO 8601 
   }
   return $ret;
  }else{
   if(!$time) return 0;
  	$time1 = \AsyncWeb\Date\Time::get((int)strtotime($time));
   if(\AsyncWeb\Date\Time::get(strtotime(date("Y-m-d",\AsyncWeb\Date\Time::getUnix($time1)))) == $time1 && mb_strlen($time,'UTF-8') == 10){ // je vyplneny iba datum vo formate YYYY-MM-DD
   	// pridaj k tomu 23hod, 59min, 59 sek
   	$time1 += \AsyncWeb\Date\Time::span(24*3600-1);
   }
   if(\AsyncWeb\Date\Time::get(strtotime(date("d.m.Y",\AsyncWeb\Date\Time::getUnix($time1)))) == $time1 && mb_strlen($time,'UTF-8') == 10){ // je vyplneny iba datum vo formate DD-MM-YYYY
   	// pridaj k tomu 23hod, 59min, 59 sek
   	$time1 += \AsyncWeb\Date\Time::span(24*3600-1);
   }
  
   return $time1;
  }
 }
 private function checkCancel(){// for N2N tables if the item should be canceled
 
  
  if(!isset($this->data["allowInsert"])) return false;
  if(!(null!==URLParser::v($this->data["uid"]."___CANCEL"))) return false;

  
  if(!isset($this->data["table"])) return $this->merged;
  if(isset($this->data["rights"])){
  if(isset($this->data["rights"]["insert"]) && $this->data["rights"]["insert"]){                 // ak sa vyzaduju prava na vkladanie, tak ich over
   if(Group::exists($this->data["rights"]["insert"])){// ak existuje dane id skupiny
    if(!Group::isInGroupId($this->data["rights"]["insert"])) return false;
   }else{// inak existuje nazov skupiny
    if(!Group::userInGroup($this->data["rights"]["insert"])) return false;
   }
  }else{
   if(@$this->data["rights"]["insert"]) return false;
  }
  }
  $del = array(array("col"=>$this->data["tableN2Ncol2"],"op"=>"is","value"=>null));
  DB::delete($this->data["tableN2N"],$del);
  Header::reload(array($this->data["uid"]."___CANCEL"=>""));
  return true;
 }
 /**
 * Checks if the insert form was submitted
 */
 
 private function checkInsert(){
  if(!isset($this->data["allowInsert"])) return false;
  if(!(null!==URLParser::v($this->data["uid"]."___INSERT"))) return false;
  
  if(!isset($this->data["table"])) return $this->merged;
  if(isset($this->data["rights"])){
  if(isset($this->data["rights"]["insert"]) && $this->data["rights"]["insert"]){                 // ak sa vyzaduju prava na vkladanie, tak ich over
   if(Group::exists($this->data["rights"]["insert"])){// ak existuje dane id skupiny
    if(!Group::isInGroupId($this->data["rights"]["insert"])) return false;
   }else{// inak existuje nazov skupiny
    if(!Group::userInGroup($this->data["rights"]["insert"])) return false;
   }
  }else{
   if(@$this->data["rights"]["insert"]) return false;
  }
  }

  $formName = $this->data["uid"];
  if(!$this->insertingN2N && isset($this->data["tableN2N"])){
	$this->data["col"] = $this->makeN2NInsertCols();
	$this->data["table"] = $this->data["tableN2N"];
	$this->where = array($this->data["tableN2Ncol"] => $this->data["tableN2Nvalue"]);
  }
  
  if(isset($this->data["execute"])){
   if(isset($this->data["execute"]["beforeInsert"])){
    try{
	 if(!$this->execute($this->data["execute"]["beforeInsert"])){
	  return false;
	 }
	}catch(\Exception $exc){
	 $this->exception = $exc;
	 return false;
	}
   }
  }
  
  $data = array();
  $includedCols = array();

  foreach($this->data["col"] as $colname=>$item){
   if(isset($item["data"]["type"])) $item["data"]["datatype"] = $item["data"]["type"];
   $usg="MFi";if(isset($item["usage"]) && ((isset($item["usage"][$usg]) && $item["usage"][$usg]) || in_array($usg,$item["usage"]))){}else{continue;}
  
	if(isset($item["data"]["col"])) $colname = $item["data"]["col"];
	$n = $formName."_".$colname;
	$colValue = URLParser::v($n);
	
	if(isset($item["data"]["var"])) $n = $item["data"]["var"];

    if($in=$this->inWhere($colname)){
	 $colValue = $in["value"];
 	 $item["editable"] = false;
    }
	
	$name1 = $n;//."__MF_";
	if(isset($item["data"]["datatype"])){
		$datatype = $item["data"]["datatype"];
	}else{
		$datatype = "string";
	}
	
  	if(!(null!==URLParser::v($name1)) && (null!==URLParser::v($n))) $colValue = URLParser::v($n);

	if($item["form"]["type"] == "part") continue;
	if(isset($item["function"])) continue;
	
    // skontroluj format..
    
	if(@$includedCols[$colname]){
	 continue;
	}else{
 	 $includedCols[$colname] = true;
	}
    
    if(isset($item["editable"]) && $item["editable"] == false && isset($item["texts"]["default"])){
     $data[$colname] = $this->getText($item["texts"]["default"]);
	 continue;
	}else{
		if(isset($item["editable"]) && !$item["editable"]){continue;}
	}
	    
    switch($item["form"]["type"]){
     case 'value':
     	if(isset($item["texts"]["value"])){//value neprehodnocuj podla Language::get
			$value = $item["texts"]["value"];
		}else{
			$value = $this->getText($item["texts"]["text"]);
		}
		if(@URLParser::v($name1)) {//isset($item["allowChange"]) && $item["allowChange"] && 
			$value = $this->filters(@URLParser::v($name1),$datatype,true);	
		}
     	
     	$data[$colname] = $value;
     break;
     case 'password':
      	$data[$colname] = hash('sha256',URLParser::v($name1));
     case 'htmlText':
     case 'tinyMCE':
	  
	  $data[$colname] = $value = $this->filters(@URLParser::v($name1),$datatype,true);
	  if(isset($item["data"]["dictionary"]) && $item["data"]["dictionary"]){
		if($value){
		 if(class_exists("DetectIntrusion")){
	      $value = DetectIntrusion::XSSDecode($value);
	     }
	     $key = substr("Z___".md5(uniqid()),0,32);
		 //DB::u("dictionary",array("key"=>$k,"lang"=>$l),array("key"=>$key,"lang"=>Language::getLang(),"value"=>$value));
		 Language::set($key,$value);
		 $data[$colname] = $key;
		}
	  }
	 
	  if(isset($item["data"]["allowNull"]) && $item["data"]["allowNull"] && !@URLParser::v($name1)){
		$data[$colname] = null;
	  }

	 
	 break;
     case 'textbox':
     case 'hidden':
     case 'textarea':
      $data[$colname] = $value = $this->filters(@URLParser::v($name1),$datatype,true);

	  
	  if(isset($item["data"]["dictionary"]) && $item["data"]["dictionary"]){
		if($value){
			$key = substr("Z___".md5(uniqid()),0,32);
			//DB::u("dictionary",array("key"=>$k,"lang"=>$l),array("key"=>$key,"lang"=>Language::getLang(),"value"=>$value));
			Language::set($key,$value);
			$data[$colname] = $key;
		}
	  }
	 
	  if(isset($item["data"]["allowNull"]) && $item["data"]["allowNull"] && !@URLParser::v($name1)){
			$data[$colname] = null;
	  }


     break;
     case 'radio':
     case 'select':
     case 'selectDB':
      $data[$colname] = $value = $this->filters(@URLParser::v($name1),$datatype,true);
	  if(isset($item["data"]["allowNull"]) && $item["data"]["allowNull"] && !@URLParser::v($name1)){
			$data[$colname] = null;
	  }
     break;
     case 'set':
	  $data[$colname] = $value = implode(";",URLParser::v($name1));
      //$data[$colname] = $value = $this->filters(@URLParser::v($name1),$datatype,true);
	  if(isset($item["data"]["allowNull"]) && $item["data"]["allowNull"] && !@$data[$colname]){
			$data[$colname] = null;
	  }
     break;
     case 'checkbox':
      $val = @URLParser::v($name1) || @URLParser::v($name1);
      $data[$colname] = $value =$this->filters($val,$datatype,true);
      if(isset($item["data"]["allowNull"]) && $item["data"]["allowNull"] && !@URLParser::v($name1)){
			$data[$colname] = null;
	  }
     break;
     case 'file':
      $allowedExt = array("jpg","png","gif","pdf","xls","xlsx","doc","docx","txt","zip","rar");
	  if(isset($item["data"]["allowed"])) $allowedExt = $item["data"]["allowed"];
	  
	  $name = $formName."_".$colname;
	  
	  if(isset($item["data"]["allowNull"]) && $item["data"]["allowNull"] && (!isset($_FILES[$name]['name']) || !$_FILES[$name]['name'])){
		 $data[$colname] = null;
	  }else{
	  
      $newFilename = $_FILES[$name]['name'];
	  
	  $info = pathinfo($_FILES[$name]['name']);
	  $ext = $info["extension"];
	  if(!in_array($ext,$allowedExt)){
		throw new \Exception($this->getText("fileNotAllowed"));
	  }
	  if(!is_dir($item["data"]["dir"])){
		mkdir($item["data"]["dir"],true);
	  }
	  if($item["data"]["makeunique"]){
		$newFilename = md5_file($_FILES[$name]['tmp_name'])."_".Texts::clear(substr($_FILES[$name]['name'],0,-1*strlen($ext)-1)).".$ext";
	  }
      while($item["data"]["makeunique"] && is_file($item["data"]["dir"].$newFilename)){
       $newFilename = md5(uniqid())."_".Texts::clear(substr($_FILES[$name]['name'],0,-1*strlen($ext)-1)).".$ext";
      }
      $table = $item["data"]["tableForFiles"];
	  $info = pathinfo($newFilename);
	  
	  if(is_file($item["data"]["dir"].$newFilename)){
       if($item["data"]["overwrite"]){
        //DB::query("delete from `$table` where (name = '$newFilename' and ".$this->aditional_where.")");
       }else{
        throw new \Exception($this->getText("fileExistsException"));
       }
      }
	  
      $uploadfile = $item["data"]["dir"].$newFilename;
      if(!move_uploaded_file($_FILES[$name]['tmp_name'], $uploadfile)){
        throw new \Exception($this->getText("errorWhileMovingFile"));
      }	  
      DB::u($table,$pid = md5(uniqid()),array("md5"=>md5_file($uploadfile),"size"=>filesize($uploadfile),"type"=>$_FILES[$name]['type'],"name"=>$_FILES[$name]['name'],"path"=>$uploadfile,"fullpath"=>str_replace("\\","/",realpath($uploadfile))));
      $data[$colname] = $value = $pid;
	  }
     break;
    }
	  	  
    //URLParser::v($name1) = $value;
	

    try{
     $this->checkRightDataFormat($item,$name1);
    }catch(\Exception $e){
     throw $e;
    }
  }
  foreach($this->where as $col=>$val){
	if(is_array($val)){
	 if($val["op"]=="eq"){
	  $data[$val["col"]] = $val["value"];
	 }
	}else{
	  $data[$col] = $val;
	}
  }

  $this->insertData = $data;
  if($this->merged){
	return true;
  }else{
	return $this->doInsert();
  }
 }
 private $insertData = array();
 private function doInsert(){
  if(!isset($this->data["allowInsert"])) return false;
  if(!(null!==URLParser::v($this->data["uid"]."___INSERT")) || !URLParser::v($this->data["uid"]."___INSERT")) return false;
  if(!isset($this->data["table"])) return $this->merged;
  
  if(isset($this->data["rights"])){
  if(isset($this->data["rights"]["insert"]) && $this->data["rights"]["insert"]){                 // ak sa vyzaduju prava na vkladanie, tak ich over
   if(Group::exists($this->data["rights"]["insert"])){// ak existuje dane id skupiny
    if(!Group::isInGroupId($this->data["rights"]["insert"])) return false;
   }else{// inak existuje nazov skupiny
    if(!Group::userInGroup($this->data["rights"]["insert"])) return false;
   }
  }else{
   if(@$this->data["rights"]["insert"]) return false;
  }
  }
  $table = $this->data["table"];
  
  $res = DB::u($table,$id2=md5(uniqid()),$this->insertData);
  $id = DB::insert_id();
  if($res){
   \AsyncWeb\Storage\Log::log("INSERT","insert into $table");
  
    //execute
   if(isset($this->data["execute"])){
    if(isset($this->data["execute"]["onInsert"])){
     $row = DB::gr($table,$id2);
     $params = array("row"=>$row,"new"=>$row);
     $this->execute($this->data["execute"]["onInsert"],$params);
    } 
   }
   if(!$this->merged){
	if(isset($this->data["texts"]["insertSucces"])){
		$text = $this->getText($this->data["texts"]["insertSucces"]);
	}else{
		$text = $this->getText("insertSucces");
	}
    if(!$text || $text == "insertSucces") $text = Language::get("New item has been successfully inserted");
    Messages::getInstance()->mes($text);
	Header::s("reload",array($this->data["uid"]."___INSERT"=>"","insert_data_".$this->data["uid"]=>""));exit;
	}
   return true;
  }else{
   \AsyncWeb\Storage\Log::log("INSERT","MK FORM Insert failed: ".DB::error());
   Messages::getInstance()->error("FAILED");
   return false;
  }
 }
 private function checkUpdate(){
  if(!isset($this->data["allowUpdate"])) return false;
  if(!(null!==URLParser::v($this->data["uid"]."___UPDATE2")) || !URLParser::v($this->data["uid"]."___UPDATE2")) return false;
  if(!isset($this->data["table"])) return $this->merged;

  if(isset($this->data["rights"])){
  if(isset($this->data["rights"]["update"]) && $this->data["rights"]["update"]){                 // ak sa vyzaduju prava na vkladanie, tak ich over
   if(Group::exists($this->data["rights"]["update"])){// ak existuje dane id skupiny
    if(!Group::isInGroupId($this->data["rights"]["update"])) return false;
   }else{// inak existuje nazov skupiny
    if(!Group::userInGroup($this->data["rights"]["update"])) return false;
   }
  }else{
   if(@$this->data["rights"]["update"]) return false;
  }
  }
  $cols = array();
  $vals = array();
  $table = $this->data["table"];
  $formName = $this->data["uid"];
  
	
  if(isset($this->data["execute"])){
   if(isset($this->data["execute"]["beforeUpdate"])){
	try{
	 $where = $this->where;
	 $where["id"]=URLParser::v($formName."___ID");
	 
   	 $row = DB::gr($table,$where);
	 if(!$this->execute($this->data["execute"]["beforeUpdate"],array("r"=>$row))){
	  return false;
	 }
	}catch(\Exception $exc){
	 $this->exception = $exc;
	 return false;
	}
   }
  }
  $langupdates = array();
  
  foreach($this->data["col"] as $colname=>$item){
   $usg="MFu";if(isset($item["usage"]) && ((isset($item["usage"][$usg]) && $item["usage"][$usg]) || in_array($usg,$item["usage"]))){}else{continue;}
   if(isset($item["data"]["type"])) $item["data"]["datatype"] = $item["data"]["type"];
   if(isset($item["data"]["col"])) $colname = $item["data"]["col"];
   $name = $colname;
   $n = $formName."_".$name;
   $colValue = URLParser::v($n);
   if(isset($item["data"]["var"])) $n = $item["data"]["var"];
   if($in=$this->inWhere($colname)){
	 $colValue = $in["value"];
	 $item["editable"] = false;
   }

   	 	
   // nepokracuj ak je item typu part, ak nieje editovatelny, alebo sa nezmenil	
   if($item["form"]["type"] == "part") continue;
   if(isset($item["editable"]) && !$item["editable"]) continue;
   if(!(null!==URLParser::v($n."_CHANGED")) || ((null!==URLParser::v($n."_CHANGED")) && !URLParser::v($n."_CHANGED"))){
     if(isset($item["alwaysUpdate"]) && $item["alwaysUpdate"]){ // ked je nastavene na alwaysUpdate, tak to aktualizuj stale
     }else{
    	continue;
     }
    }
	$name1 = $n;//."__MF_";
	
    switch($item["form"]["type"]){
     case 'value':
	  if(isset($item["texts"]["value"])){
	   $value = $this->getText($item["texts"]["value"]);
	  }else{
	   $value = $this->getText($item["texts"]["text"]);
	  }
	  if(isset($item["allowChange"]) && $item["allowChange"] && @$colValue) $value = $colValue;
	  $cols[$colname] = $this->filters($value,@$item["data"]["datatype"],true);
     break;
	 case 'password':
      $colValue = hash('sha256',$colValue);
	 case 'tinyMCE':
	  $value = $this->filters($colValue,@$item["data"]["datatype"],true);
	  if(isset($item["data"]["dictionary"]) && $item["data"]["dictionary"] && $value){
	   if(class_exists("DetectIntrusion")){
	    $langupdates[$colname] = DetectIntrusion::XSSDecode($value);
	   }else{
		$langupdates[$colname] = $value;
	   }
	  }else{
	   $cols[$colname] = $this->filters($colValue,@$item["data"]["datatype"],true);
	   if((isset($item["allowNull"]) && $item["allowNull"] && !@$colValue) || (isset($item["data"]["allowNull"]) && $item["data"]["allowNull"] && !@$colValue)){
	    $cols[$colname] = null;
	   }
	  }
	 break;
     case 'textbox':
     case 'textarea':
	   $value = $this->filters($colValue,@$item["data"]["datatype"],true);
	  if(isset($item["data"]["dictionary"]) && $item["data"]["dictionary"] && $value){
		$langupdates[$colname] = $value;
	  }else{
	   $cols[$colname] = $this->filters($colValue,@$item["data"]["datatype"],true);
	   if((isset($item["allowNull"]) && $item["allowNull"] && !@$colValue) || (isset($item["data"]["allowNull"]) && $item["data"]["allowNull"] && !@$colValue)){
	    $cols[$colname] = null;
	   }
	  }
     break;
     case 'select':
     case 'selectDB':
	  $value = $this->filters($colValue,@$item["data"]["datatype"]);
	   $cols[$colname] = $this->filters($colValue,@$item["data"]["datatype"]);
	   if((isset($item["allowNull"]) && $item["allowNull"] && !@$colValue) || (isset($item["data"]["allowNull"]) && $item["data"]["allowNull"] && !@$colValue)){
	    $cols[$colname] = null;
	   }
     break;
     case 'set':
	  $cols[$colname] = $value = implode(";",$colValue);
      //$data[$colname] = $value = $this->filters(@$colValue,$datatype,true);
	  if(isset($item["data"]["allowNull"]) && $item["data"]["allowNull"] && !@$data[$colname]){
			$cols[$colname] = null;
	  }
     break;
     case 'checkbox':
      $colValue = $colValue || $colValue;
      $cols[$colname] = $this->filters($colValue,@$item["data"]["datatype"]);
	  
	  if((isset($item["allowNull"]) && $item["allowNull"] && !@$colValue) || (isset($item["data"]["allowNull"]) && $item["data"]["allowNull"] && !@$colValue)){
	   $cols[$colname] = null;
	  }
     break;
     case 'htmlText':
	  $value = $this->filters($colValue,@$item["data"]["datatype"],true);
	  if(isset($item["data"]["dictionary"]) && $item["data"]["dictionary"] && $value){
	   if(class_exists("DetectIntrusion")){
	    $langupdates[$colname] = DetectIntrusion::XSSDecode($value);
	   }else{
		$langupdates[$colname] = $value;
	   }
	  }else{
       $cols[$colname] = $this->filters($colValue,@$item["data"]["datatype"],true);
	  }
     break;
     case 'file':
      // vytvor unikatne meno
	  /*
	  $name = $formName."_".$colname;
      $newFilename = $_FILES[$name]['name'];
	  if(!is_dir($item["data"]["dir"])){
		mkdir($item["data"]["dir"],true);
	  }
      while($item["data"]["makeunique"] && is_file($item["data"]["dir"].$newFilename)){
       $newFilename = md5(uniqid())."_".$_FILES[$name]['name'];
      }
      $table = $item["data"]["tableForFiles"];
	  /**/
	  $allowedExt = array("jpg","png","gif","pdf","xls","xlsx","doc","docx","txt","zip","rar");
	  if(isset($item["data"]["allowed"])) $allowedExt = $item["data"]["allowed"];

	  if(isset($item["data"]["allowNull"]) && $item["data"]["allowNull"] && (!isset($_FILES[$n]['name']) || !$_FILES[$n]['name'])){
		 $data[$colname] = null;
	  }else{
	  
  	  $info = pathinfo($_FILES[$n]['name']);
	  
	  $ext = $info["extension"];
	  if(!in_array($ext,$allowedExt)){
		throw new \Exception($this->getText("fileNotAllowed"));
	  }

      $newFilename = $_FILES[$n]['name'];
	  if($item["data"]["makeunique"]){
		$newFilename = md5_file($_FILES[$n]['tmp_name'])."_".Texts::clear(substr($_FILES[$n]['name'],0,-1*strlen($ext)-1)).".$ext";
	  }
      while($item["data"]["makeunique"] && is_file($item["data"]["dir"].$newFilename)){
       $newFilename = md5(uniqid())."_".Texts::clear(substr($_FILES[$n]['name'],0,-1*strlen($ext)-1)).".$ext";
      }
	  
	  $info = pathinfo($newFilename);
	  if(!in_array($info["extension"],$allowedExt)){
	   throw new \Exception($this->getText("fileNotAllowed"));
	  }
	  
	  if(is_file($item["data"]["dir"].$newFilename)){
       if($item["data"]["overwrite"]){
        //DB::query("delete from `$table` where (name = '$newFilename' and ".$this->aditional_where.")");
       }else{
        throw new \Exception($this->getText("fileExistsException"));
       }
      }
	  
      // najdi cestu k staremu suboru
      $tableF = $item["data"]["tableForFiles"];
	  $where = $this->where;
	  $where["id"] = URLParser::v($formName."___ID");
	  $row = DB::gr($table,$where);
      $row2 = DB::gr($tableF,$row[$n]);
	  
      // vymaz stary subor
      /*if(!unlink($row2["path"])){
       throw new \Exception($this->getText("errorWhileDeletingFile"));
      }
      // vymaz stary subor z db
      DB::delete($tableF,$row2["id2"]);
	  /**/
      // vloz novy subor
	  
	  $uploadfile = $item["data"]["dir"].$newFilename;
	  if(!move_uploaded_file($_FILES[$n]['tmp_name'], $uploadfile)){
        throw new \Exception($this->getText("errorWhileMovingFile"));
      }
      // vloz novy subor do db
	  
	  
      DB::u($tableF,$id2=md5(uniqid()),array("md5"=>md5_file($uploadfile),"size"=>filesize($uploadfile),"type"=>$_FILES[$n]['type'],"name"=>$_FILES[$n]['name'],"path"=>$uploadfile,"fullpath"=>str_replace("\\","/",realpath($uploadfile))));
      $cols[$colname] = $colValue = $id2;
	  }
	 break;
    }
   
    try{
     $this->checkRightDataFormat($item,$name1, true,$formName);
    }catch(\Exception $e){
     throw $e;
    }
   }
   
  $this->updateData = $cols;

  $this->updateLangs = $langupdates;
  if($this->merged){
	return true;
  }else{
	return $this->doUpdate();
  }
 }
 private $updateData = array();
 private $updateLangs = array();
 private function doUpdate(){
  if(!isset($this->data["allowUpdate"])) return false;
  if(!(null!==URLParser::v($this->data["uid"]."___UPDATE2"))) return false;
  if(!isset($this->data["table"])) return $this->merged;
  
  if(isset($this->data["rights"])){
  if(isset($this->data["rights"]["update"]) && $this->data["rights"]["update"]){                 // ak sa vyzaduju prava na vkladanie, tak ich over
   if(Group::exists($this->data["rights"]["update"])){// ak existuje dane id skupiny
    if(!Group::isInGroupId($this->data["rights"]["update"])) return false;
   }else{// inak existuje nazov skupiny
    if(!Group::userInGroup($this->data["rights"]["update"])) return false;
   }
  }else{
   if(@$this->data["rights"]["update"]) return false;
  }
  }
   
  $formName = $this->data["uid"];
  
   $where = $this->where;
   $where["id"] = @URLParser::v($this->data["uid"]."___ID");

   $row = DB::gr($this->data["table"],$where);

   $old_row = $row;
   if(!DB::u($this->data["table"],$row["id2"],$this->updateData)){
   	 \AsyncWeb\Storage\Log::log("MakeForm","Update oddo failed".DB::error(),ML__HIGH_PRIORITY);
	 $this->exception = new \Exception("Chyba pri upravovaní záznamu!");
	 throw $this->exception;
	 return false;
   }
   
   $row = DB::gr($this->data["table"],$row["id2"]);
   $l = Language::getLang();
   foreach($this->updateLangs as $k=>$v){
    if(!$row[$k]){
		$key = substr("Z___".md5(uniqid()),0,32);
		DB::u($this->data["table"],$row["id2"],array($k=>$key));
		$row[$k] = $key;
	}
	$key=$row[$k];
	
    //DB::u("dictionary",array("key"=>$key,"lang"=>$l),array("key"=>$key,"lang"=>$l,"value"=>$v));
	Language::set($key,$v);
   }
   
   $new_row = $row;
   $table  = $this->data["table"];
   $id = DB::myAddSlashes(@URLParser::v($formName."___ID"));
   \AsyncWeb\Storage\Log::log("UPDATE","update $table where (id = '$id')");
   
   
   //execute
   if(isset($this->data["execute"])){
    if(isset($this->data["execute"]["onUpdate"])){
     $ret = $this->execute($this->data["execute"]["onUpdate"],array("old"=>$old_row,"new"=>$new_row));
    }
   }
   if(!$this->merged){
	   $text = $this->getText("updateSucces");
	   if(!$text || $text = "updateSucces") $text = Language::get("Item has been successfully updated");
	   Messages::getInstance()->mes($text);//
	   Header::s("reload",array($this->data["uid"]."___ID"=>"",$this->data["uid"]."___UPDATE2"=>"",$this->data["uid"]."___UPDATE1"=>""));exit;
	   exit;
   }
   return true;
  
 }
 private function checkDelete(){
  if(!isset($this->data["allowDelete"])) return false;
  
  if(!(null!==URLParser::v($this->data["uid"]."___DELETE")) || !URLParser::v($this->data["uid"]."___DELETE")) return false;
  if(!isset($this->data["table"])) return $this->merged;

  if(isset($this->data["rights"])){
  if(isset($this->data["rights"]["delete"]) && $this->data["rights"]["delete"]){                 // ak sa vyzaduju prava na vkladanie, tak ich over
   if(Group::exists($this->data["rights"]["delete"])){// ak existuje dane id skupiny
    if(!Group::isInGroupId($this->data["rights"]["delete"])) return false;
   }else{// inak existuje nazov skupiny
    if(!Group::userInGroup($this->data["rights"]["delete"])) return false;
   }
  }else{
   if(@$this->data["rights"]["delete"]) return false;
  }
  }

   $params = array();
   
   $where = $this->where;
   $where["id"] = URLParser::v($this->data["uid"]."___ID");
   $row = DB::gr($this->data["table"],$where);
   $params["row"] = $row;
   $params["old"] = $row;
   if(isset($this->data["execute"])){
    if(isset($this->data["execute"]["beforeDelete"])){
	try{
	 if(!$this->execute($this->data["execute"]["beforeDelete"],array("r"=>$row))){
	  return false;
	 }
	}catch(\Exception $exc){
	 $this->exception = $exc;
	 throw $this->exception;
	 return false;
	}
    }
   }
   
   
   
  if($this->merged){
	return true;
  }else{
	return $this->doDelete();
  }
 }
 private function doDelete(){
  if(!isset($this->data["allowDelete"])) return false;
  
  
  if(!(null!==URLParser::v($this->data["uid"]."___DELETE"))) return false;
  if(!isset($this->data["table"])) return $this->merged;
  $table = $this->data["table"];
  
  if(isset($this->data["rights"])){
  if(isset($this->data["rights"]["delete"])&&$this->data["rights"]["delete"]){                 // ak sa vyzaduju prava na vkladanie, tak ich over
   if(Group::exists($this->data["rights"]["delete"])){// ak existuje dane id skupiny
    if(!Group::isInGroupId($this->data["rights"]["delete"])) return false;
   }else{// inak existuje nazov skupiny
    if(!Group::userInGroup($this->data["rights"]["delete"])) return false;
   }
  }else{
   if(@$this->data["rights"]["delete"]) return false;
  }
  }
  
	
  
   $where = $this->where;
   $where["id"] = URLParser::v($this->data["uid"]."___ID");
   $row = DB::gr($this->data["table"],$where);
   
   DB::delete($this->data["table"],$where);
   if(DB::affected_rows() > 0){
   
	   \AsyncWeb\Storage\Log::log("DELETE","delete $table where id = ".URLParser::v($this->data["uid"]."___ID"));
		 
	   //execute
	   if(isset($this->data["execute"])){
		if(isset($this->data["execute"]["onDelete"])){
		 $this->execute($this->data["execute"]["onDelete"],array("row"=>$row,"old"=>$row));
		}
	   }
	   
		if(!$this->merged){
		   $text = $this->getText("deleteSucces");
		   if(!$text || $text == "deleteSucces") $text = Language::get("Deletion has been successfully commited");
		   Messages::getInstance()->mes($text);//$this->data["uid"]."___DELETE"
		   Header::s("reload",array($this->data["uid"]."___ID"=>"",$this->data["uid"]."___DELETE"=>""));exit;
		   exit;
	   }
	   return true;
   }else{
   
		if(!$this->merged){
		   $text = $this->getText("deleteNotSucces");
		   if(!$text || $text == "deleteNotSucces") $text = Language::get("Error occured while deleting the item");
		   Messages::getInstance()->err($text);
	   }
	   return false;
	   
   }
  
 }
 private function execute($function,$params=null){
 	  return \AsyncWeb\System\Execute::run($function,$params,false);
 }
/**
 * Tato funkcia zobrazi formular
 * parametre .. ALL .. zobrazi vsetky insert, update, a delete formulare
 *              INSERT .. zobrazi iba formular na vkladanie
 *              ONLY_INSERT .. zobrazi iba formular na vkladanie s obalom
 *              UPDATE1 .. zobrazi iba formular na upravovanie
 *              UPDATE2 .. zobrazi iba formular na upravovanie, ale musi byt vybrany riadok
 *              DELETE .. zobrazi iba formular na mazanie
 */
 public function show($what="ALL",$show_results=true){
 
  // $what == INSERT || UPDATE1 || UPDATE2 || DELETE || ALL
  $ret = "";
  if($show_results) $ret.=$this->show_results();
  if(@$this->data["expectRight"])
   if(!Group::is_in_group($this->data["expectRight"])) return;

  if($this->insertingN2N){
	 $what = "INSERT";
  } 
   
  switch($what){
   case "INSERT":
    $ret.=$this->insertForm();
   break;
   case "UPDATE1":
    $ret.=$this->update1Form();
   break;
   case "UPDATE2":
    $ret.=$this->update2Form();
   break;
   case "DELETE":
    $ret.=$this->deleteForm();
   break;
   case "ONLY_INSERT":
    $ret .= $this->makeCoverFront();
    $ret .= $this->show("INSERT");
    $ret .= $this->makeCoverBack();
   break;
   case "ONLY_UPDATE":
    $ret .= $this->makeCoverFront();
    $ret .= $this->show("UPDATE2");
    $ret .= $this->makeCoverBack();
   break;
   case "ALL_UPDATE_FIRST":
    $formName = $this->data["uid"];
    $ret .= $this->makeCoverFront();
    if(
        // ak bol odoslany formular na zobrazenie update2	
        URLParser::v($formName."___UPDATE1")
         ||
        // ak bol odoslany update2, a nastala chyba
        URLParser::v($formName."___UPDATE2")
        ){
     $ret .= $this->show("UPDATE2");
    }else{
     $ret .= $this->show("UPDATE1");
     $ret .= $this->show("INSERT");
     $ret .= $this->show("DELETE");/**/
    }
    $ret .= $this->makeCoverBack();
   break;
   case "ALL": 
   
    if(
        // ak bol odoslany formular na zobrazenie update2	
        @URLParser::v($formName."___UPDATE1")
         ||
        // ak bol odoslany update2, a nastala chyba
        @URLParser::v($formName."___UPDATE2")
        ){
     $ret .= $this->show("UPDATE2");
    }else{
		return $ret.\AsyncWeb\View\MakeDBView::make($this->data,$this);
	}
/*    $formName = $this->data["uid"];
    $ret = $this->makeCoverFront();
    
     $ret .= $this->show("INSERT");
     $ret .= $this->show("UPDATE1");
     $ret .= $this->show("DELETE");/**
    }
    $ret .= $this->makeCoverBack();/**/
   break;
  }
  return $ret;
 }
 private function makeCoverFront(){
  if(isset($this->data["texts"]["head"])){
	$text = $this->getText($this->data["texts"]["head"]);
  }else{
	$text = $this->getText($this->data["uid"]."-texts-head");
  }
  if(!$text) $text = "Editácia objektu";
  return '<table class="MFTable" cellpadding="0" cellspacing="0"><tr><td colspan="4" class="MFMainHead">'.$text.'</td></tr>'."\n";
 }
 private function makeCoverBack(){
  return "</table>\n";
 }
/**
 * Tato funkcia spracuje text ove polozky daneho uzlu a vrati text v jazyku ako je v L["id"]
 * ak neexisuje <text lang="L["id"]"> tak vrati prvy text, ktory nasiel
 */
 private function getText($item,$safe=false){
  // safe allows to execute PHP code..
  // Language::get should be used instead
  $ret = "";
  $ret = $this->filters($item,null,$safe);
  return Language::get($ret);
 }
 private function makeItemId(&$item){
	if(!isset($item["data"]["col"])) return $this->data["uid"]."-0";
 	return $this->data["uid"]."_".$item["data"]["col"];
 }
 private function makeText(&$item){
  $id = $this->makeItemId($item);
  $ret="";
  if(!isset($this->data["bootstrap"])){
  $ret = '
  <td class="MFTextColumn">';
  }
  $text="";
  if(isset($item["name"])) $text.=$this->getText($item["name"]);
  if($text){
  $ret.='
   <label for="'.$id.'" class="col-lg-2 control-label">'.$text.'</label>
   ';
  }
  if(!isset($this->data["bootstrap"])){
  $ret.='
  </td>
  ';
  }
  
  return $ret;
 }
 private function makeHelp($item){
  if(isset($this->data["bootstrap"])){return "";}
  $ret='
  <td class="MFHelpColumn">
  ';
  if(isset($item["texts"]["help"]) && $text=$this->getText($item["texts"]["help"])){
   $ret.='<img class="MFHelpImage" src="'.$this->help_pic.'" width="15" height="16" alt="'.$text.'" title="'.$text.'"/>
  ';
  }
  $ret.='
  </td>
  ';
  return $ret;
 }
 private function getInnerDBColConfig(&$row,&$colsettings){
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
 private function inWhere($col){
	$ret = array();
	if(isset($this->data["where"]))
	foreach($this->data["where"] as $k=>$v){
		if(is_array($v)){
			if($v["col"] == $col && isset($v["op"]) && $v["op"] == "eq"){
				$ret["value"] = $v["value"];
			}
		}else{
			if($k == $col){
				$ret["value"] = $v;
			}
		}
	}
	return $ret;
 }
 private $insertingN2N = false;
 protected function encodeEntities($str){
	 //$str = htmlentities($str,ENT_COMPAT, 'UTF-8');// gets converted in URLParser::v()
	 $str = str_replace("{","&#123;",$str);// so that templates are not executed when editing
	 return $str;
 }
 private function insertForm(){
  if(!isset($this->data["allowInsert"])) return false;
  if(isset($this->data["rights"])){
  if(isset($this->data["rights"]["insert"]) && $this->data["rights"]["insert"]){                 // ak sa vyzaduju prava na vkladanie, tak ich over
   if(Group::exists($this->data["rights"]["insert"])){// ak existuje dane id skupiny
    if(!Group::isInGroupId($this->data["rights"]["insert"])) return false;
   }else{// inak existuje nazov skupiny
    if(!Group::is_in_group($this->data["rights"]["insert"])) return false;
   }
  }else{
	if(@$this->data["rights"]["insert"]) return false;
  }
  }
  
  if(!$this->insertingN2N && isset($this->data["tableN2N"])){
	$this->data["col"] = $this->makeN2NInsertCols();
	$this->data["table"] = $this->data["tableN2N"];
	$this->where = array($this->data["tableN2Ncol"] => $this->data["tableN2Nvalue"]);
  }
  
  
  $formName = $this->data["uid"];
  
  
  if(isset($this->data["texts"]["insert"])){
	$text = $this->getText($this->data["texts"]["insert"]);
	if(!$text) $text = "Vlož novú položku";
  }else{
	$text = "";//$this->getText($this->data["uid"]."-texts-insert");
  }
  $ret= "";	
  if(!isset($this->data["bootstrap"])){
  
  $ret = '<tr class="MF_Insert"><td colspan="3" class="MFSubHead">'.$text.'</td></tr>'."\n";
  $ret .= '<tr><td>';
  }
  
  if($this->merged){
	$ret .= '<input type="hidden" name="'.$this->data["uid"]."___INSERT".'" value="1" />';
  }else{
   $ret .= '<form class="form-horizontal" role="form" method="post" action="'.Path::make(array($this->data["uid"]."___INSERT"=>1))."\"";
   if(isset($this->data["enctype"]) && $this->data["enctype"]) $ret.= ' enctype="'.$this->data["enctype"].'"';
   if(isset($this->data["form"]["onInsertSubmit"])){
    $ret .= " onsubmit=\"".$this->data["form"]["onInsertSubmit"]."\"";
   }
   $ret .=">\n";
  }
  
  if(!isset($this->data["bootstrap"])){
  $ret .= "<table>";
  }
  
  $form_submitted = (null!==URLParser::v($this->data["uid"]."___INSERT"));
  
  foreach($this->data["col"] as $colname=>$item){
   $usg="MFi";if(isset($item["usage"]) && ((isset($item["usage"][$usg]) && $item["usage"][$usg]) || in_array($usg,$item["usage"]))){}else{continue;}
   
   if(isset($item["data"]["col"])) $colname = $item["data"]["col"];
   $name = $formName."_".$colname;
   if(isset($item["data"]["var"])) $name = $item["data"]["var"];
   $colValue = URLParser::v($name);
   
   if($in=$this->inWhere($colname)){
	 $colValue = $in["value"];
	 $item["editable"] = false;
   }
  
	 $minl = @$item["data"]["minlength"];
	 $maxl = @$item["data"]["maxlength"];
	 $minn = @$item["data"]["minnum"];
	 $maxn = @$item["data"]["maxnum"];
	 $step = @$item["data"]["step"];
	 
   switch($item["form"]["type"]){
    case 'textbox':
	 if(!isset($this->data["bootstrap"])){
     $ret.= '<tr>';
	 }else{
	 $ret.= '<div class="form-group">';
	 }
	 $ret.= $this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
     $ret.= '<td class="MFFormColumn">';
	 }else{
	 $ret.= '<div class="col-lg-10 MFFormColumn">';
	 }
	 
     $type = @$item["data"]["datatype"];
	 if(!$type) $type = "text";
	$prepend = false;
	$after = false;
	if(isset($this->data["bootstrap"])){
		if(isset($item["texts"]["prepend"]) && $prepend=$this->getText($item["texts"]["prepend"])){
			$ret.='<div class="input-group"><span class="input-group-addon">'.$prepend.'</span>';
		}else{
			if(isset($item["texts"]["after"]) && $after=$this->getText($item["texts"]["after"])){
				$ret.='<div class="input-group">';
			}
		}
	}
	
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
	 $ret.= ' <input class="MFInput form-control'.$addclass.'" type="'.$type.'" id="'.$name.'" name="'.$name.'"';
     if($form_submitted){
      $ret.=' value="'.stripslashes(@$colValue).'"';
     }else{
	  if(isset($item["texts"]["default"]) && ($t=$this->getText($item["texts"]["default"])) !== null){
	   $ret.=' value="'.$t.'"';
	  }else{
	   $ret.=' value="'.stripslashes(@$colValue).'"';
	  }
	 }
     if(isset($maxl)) $ret .= ' maxlength="'.$maxl.'"';
	 if(isset($minn)) $ret .= ' min="'.$minn.'"';
	 if(isset($maxn)) $ret .= ' max="'.$maxn.'"';
	 if(isset($step)) $ret .= ' step="'.$step.'"';
	 
     if(isset($item["editable"]) && !$item["editable"]){
      $ret.=' disabled="disabled"';
     }
     $ret .= " onchange=\"apply_filter('$name','$type','$minl','$maxl','$minn','$maxn',false)\"";
	 
	 if(isset($this->data["bootstrap"])){
		if(isset($item["texts"]["help"]) && $text=$this->getText($item["texts"]["help"])){
		 $ret.= ' title="'.$text.'" placeholder="'.$text.'" data-content="'.$text.'" data-placement="bottom"';
		}
	 }
	 
	 
	 $ret.='/>';
	 if($prepend){
		$ret.='</div>';
	 }
	 if($after){
		$ret.='<span class="input-group-addon">'.$after.'</span></div>';;
	 }else{
		 if(isset($item["texts"]["after"]) && $t=$this->getText($item["texts"]["after"])){
		  $ret .= ' '.$t;
		 }
	 }
	 $ret .= "<script type=\"text/javascript\">apply_filter('$name','$type','$minl','$maxl','$minn','$maxn',true);</script>";
	 if($type == "date" || $type == "date_string"){
	  if(\AsyncWeb\IO\File::exists($f = "img/icons/calendar.png")){
	   $select = Language::get("Select date");
	   $ret .= ' <a href="#image" onclick="select_date(\''.$name.'\');return false;"><img width="20" height="20" src="/'.$f.'" class="icon" alt="'.$select.'" title="'.$select.'" /></a>';
      }else{
	   $ret .= ' <a href="#image" onclick="select_date(\''.$name.'\');return false;">'.$select.'</a>';
      }
	 }
     if(!isset($this->data["bootstrap"])){
		$ret .= '</td>
     </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
    break;
	case 'htmlText':
	 ////////////////// todo
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeText($item).$this->makeHelp($item);
	 if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
	 $ret .= '<object type="application/x-xstandard" id="'.$item["data"]["editorName"].'" width="'.$item["data"]["width"].'" height="'.$item["data"]["height"].'';
     if(isset($item["editable"]) && !$item["editable"]){
      $ret.=' disabled="disabled"';
     }
	 $ret.='">';
	 $ret .= '<param name="CSS" value="'.$item["data"]["cssSubor"].'" />';
	 $ret .= '<param name="Styles" value="'.$item["data"]["styleXMLSubor"].'" />';
	 $ret .= '<param name="Lang" value="'.Language::getLang().'"/>';
	 $ret .= '<param name="Value" value="" />';
	 $ret .= '<div>Ak vidíte túto správu, nainštalujte si XStandard Lite z http://xstandard.com/download.asp!</div>';
	 $ret .= '<textarea name="alternate1" id="alternate1" cols="50" rows="15"></textarea>';
	 $ret .= '</object>';
	 $ret .= '<input type="hidden" name="'.$name.'" id="'.$name.'" value="" /> ';
     if(!isset($this->data["bootstrap"])){
		$ret .= '</td>
     </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
	break;
	case 'captcha':
	 if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
	 $err=null;
	 
		$captcha = new \reCaptcha\Captcha();
		$captcha->setPrivateKey(MakeForm::$captchaPrivatekey);
		$captcha->setPublicKey(MakeForm::$captchaPublickey);
		$ret.=$captcha->html();

     if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'     </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
    break;

    case 'password':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
	 
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
	 $ret.=' <input class="MFInput form-control'.$addclass.'" type="password" id="'.$name.'" name="'.$name.'"';
     if(!$form_submitted){
      if(isset($item["texts"]["default"])) $ret.=' value="'.$this->getText($item["texts"]["default"]).'"';
     }
     if(isset($maxl)) $ret .= ' maxlength="'.$maxl.'"';
     $ret .= '" ';
     if(isset($item["editable"]) && !$item["editable"]){
      $ret.=' disabled="disabled"';
     }
	 
	 if(isset($this->data["bootstrap"])){
		if(isset($item["texts"]["help"]) && $text=$this->getText($item["texts"]["help"])){
		 $ret.= ' title="'.$text.'" placeholder="'.$text.'" data-content="'.$text.'" data-placement="bottom"';
		}
	 }
	 
	 $ret.='/>';
	 if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'     </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }

    break;
    case 'select':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
     $ret.= ' <select class="MFSelect form-control'.$addclass.'" id="'.$name.'" name="'.$name.'"';
     if(isset($item["editable"]) && !$item["editable"]){
      $ret.=' disabled="disabled"';
     }
	 $ret.='>';
     foreach($item["filter"]["option"] as $k=>$v){
	  $v = Language::get($v);
      $ret .= '<option value="'.$k.'"';
      if($form_submitted){
       if(@$colValue == $k){
        $ret .= ' selected="selected"';
       }
      }else{
	   if(isset($item["texts"]["default"]) && ($item["texts"]["default"] == $k || $item["texts"]["default"] == $v)){
        $ret .= ' selected="selected"';
       }elseif($in=$this->inWhere($colname) && ($k==$in["value"] || $v == $in["value"])){
	    $ret .= ' selected="selected"';
	   }
      }
      $ret .='>'.strip_tags($v).'</option>'."\n";
     }
     $ret.='</select>';
	 if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'     </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }

    break;

    case 'set':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
     $ret.= ' <select class="MFSelect form-control'.$addclass.'" id="'.$name.'" name="'.$name.'[]"';
     if(isset($item["editable"]) && !$item["editable"]){
      $ret.=' disabled="disabled"';
     }
	 $ret.=' multiple="multiple">';
     foreach($item["filter"]["option"] as $k=>$v){
	  $v = Language::get($v);
      $ret .= '<option value="'.$k.'"';
      if($form_submitted){
       if(in_array($k,$colValue)){
        $ret .= ' selected="selected"';
       }
      }else{
	   if(isset($item["texts"]["default"]) && ($item["texts"]["default"] == $k || $item["texts"]["default"] == $v) || (isset($item["texts"]["default"][$k]) && $item["texts"]["default"][$k])){
        $ret .= ' selected="selected"';
       }elseif($in=$this->inWhere($colname) && ($k==$in["value"] || $v == $in["value"])){
	    $ret .= ' selected="selected"';
	   }
      }
      $ret .='>'.strip_tags($v).'</option>'."\n";
     }
     $ret.='</select>';
	 if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'     </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }

    break;    
	case 'selectDB':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
     $ret.=' <select class="MFSelect form-control'.$addclass.'" id="'.$name.'" name="'.$name.'"';
     if(isset($item["editable"]) && !$item["editable"]){
      $ret.=' disabled="disabled"';
     }
	 $ret.='>';
	 $options = array();
	 
     
	 
	 $col = $item["data"]["fromColumn"];
     $index = "id2";
	 if(!isset($item["data"]["where"])) $item["data"]["where"] = array();
	 $res = DB::g($item["data"]["fromTable"],$item["data"]["where"],null,null,@$item["data"]["order"]);
     
     //$res = DB::query("select `".$index."`,`".(string)$item->db->fromColumn."`,`".(string)$item->db->fromColumn."` as RS_val1 from `".(string)$item->db->fromTable."` where ($where) order by `".(string)$item->db->fromColumn."`");

     while($row = DB::fetch_assoc($res)){
	 
	  if((isset($item["data"]["isText"]) && $item["data"]["isText"]) || (isset($item["data"]["dictionary"]) && $item["data"]["dictionary"])){
	   $options[$row[$index]] = strip_tags($this->getText($row[$col],true));
	  }else{
	   $options[$row[$index]] = $this->getInnerDBColConfig($row,$col);
	  }
	  
     }
	 asort($options);
	 $reto = "";
	 $sel = false;
	 foreach($options as $k=>$v){
	  $k = "".$k;
      $reto .= '<option value="'.$k.'"';
      if($form_submitted){
       if(@$colValue == $k){
        $reto .= ' selected="selected"';
       }
      }else{
       if(isset($item["texts"]["default"]) && ($v == $this->getText($item["texts"]["default"]) || $k == $this->getText($item["texts"]["default"]))){
        $reto .= ' selected="selected"';
		$sel = true;
       }elseif(($in=$this->inWhere($colname)) && $k==$in["value"]){
	    $reto .= ' selected="selected"';
		$sel = true;
	   }
      }
      $reto .= '>';
	  $reto .= $v;
	  $reto .='</option>'."\n";
	  
	 }
	  if(isset($item["data"]["allowNull"]) && $item["data"]["allowNull"]){
	   if(isset($item["texts"]["nullValue"]) && $item["texts"]["nullValue"]){
        $reto = '<option value="0">'.strip_tags($this->getText($item["texts"]["nullValue"])).'</option>'.$reto;
	   }else{
        $reto = '<option value="0">'.$this->getText("nullValue").'</option>'.$reto;
	   }
      }
	  
	 $ret.= $reto;
     $ret.='</select>';
	 if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'     </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
    break;
    case 'textarea':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
     $ret.=' <textarea class="MFTextArea form-control'.$addclass.'" id="'.$name.'" name="'.$name.'" cols="50" rows="15">';
     if($form_submitted){
      $ret.=$this->encodeEntities($colValue);
     }else{
      if(isset($item["texts"]["default"])) $ret .= $this->encodeEntities($this->getText($item["texts"]["default"]));
     }
     $ret.='</textarea>';
	 if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'     </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
    break;
    case 'tinyMCE':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
     $ret.=' <textarea class="MFTextArea form-control'.$addclass.'" id="'.$name.'" name="'.$name.'" cols="50" rows="15">';
     if($form_submitted){
      $ret.=$this->encodeEntities($colValue);
     }else{
      if(isset($item["texts"]["default"])) $ret .= $this->encodeEntities($this->getText($item["texts"]["default"]));
     }
$theme = "simple";
	 if(isset($item["form"]["theme"])){
		$theme = $item["form"]["theme"];
	 }
     $ret.='</textarea>
	 ';
	 if($theme != "advanced"){
	 $ret.= '	 
	 <script type="text/javascript">
	$().ready(function() {
		tinymce.init({selector:"#'.$name.'",
		remove_script_host : false,
		convert_urls : false,
		relative_urls : false
		
		';
		if(\AsyncWeb\IO\File::exists($f="/js/tinymce/langs/".substr(\AsyncWeb\System\Language::getLang(),0,2).".js")) $ret.=',language_url :"'.$f.'"';
		$ret.='});
	});
	</script>';
	
	 }else{
//			theme_advanced_styles : "image",
	 $ret.= '
<script type="text/javascript">
	$().ready(function() {
		tinymce.init({selector:"#'.$name.'",
		remove_script_host : false,
		convert_urls : false,
		relative_urls : false,
		plugins : "advlist autolink link image lists charmap print preview searchreplace visualblocks code fullscreen insertdatetime media table contextmenu paste"';
		if(\AsyncWeb\IO\File::exists($f="/js/tinymce/langs/".substr(\AsyncWeb\System\Language::getLang(),0,2).".js")) $ret.=',language_url :"'.$f.'"';
		$ret.='});
	});
</script>
	 ';
	 }
	 if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'     </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
    break;
    case 'checkbox':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
     $ret.=' <input class="MFCheckBox form-control'.$addclass.'" type="checkbox" id="'.$name.'" name="'.$name.'"';
     if($form_submitted){
      if((null!==$colValue) && $colValue=="on"){
       $ret.= ' checked="checked"';
      }
     }else{
      if(isset($item["texts"]["default"]) && $this->getText($item["texts"]["default"]) == 1){
       $ret.= ' checked="checked"';
      }
     }
     $ret .= "/>";
	 if(isset($item["texts"]["after"]) && $t=$this->getText($item["texts"]["after"])){
	  $ret .= ' '.$t;
	 }
	 if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'     </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
    break;
    case 'radio':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-offset-2 col-lg-10 MFFormColumn"><div class="radio inline">';
	 }
	 $value = "";
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
     if(isset($item["texts"]["value"])) $value = $this->getText($item["texts"]["value"]);
	 if(isset($item["filter"]["option"])){
	  foreach($item["filter"]["option"] as $k=>$v){
	   $ret.="<label for=\"${name}_${k}\">".' <input value="'.$k.'" class="MFRadio '.$addclass.'" type="radio" id="'.$name.'_'.$k.'" name="'.$name.'"';
        if($form_submitted){
         if($colValue==$k){
          $ret.= ' checked="checked"';
         }
        }else{
         if(isset($item["data"]["selected"]) && $item["data"]["selected"]){
          $ret.= ' checked="checked"';
         }
        }
       $ret .= "/>".$v."</label> ";
	   
	  }
	 }else{
      $ret.=' <input value="'.$value.'" class="MFRadio '.$addclass.'" type="radio" id="'.$name.'" name="'.$name.'"';
      if($form_submitted){
       if($colValue==$value){
        $ret.= ' checked="checked"';
       }
      }else{
       if(isset($item["data"]["selected"]) && $item["data"]["selected"]){
        $ret.= ' checked="checked"';
       }
      }
      $ret .= "/>";
	 }
	 if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'     </tr>'."\n";
	 }else{
		$ret.='</div></div></div>'."\n";
	 }
    break;
    case 'part':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeText($item).$this->makeHelp($item);
	 if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'     </tr>'."\n";
	 }else{
		$ret.='</div>'."\n";
	 }
    break;
    case 'file':
	//$item["data"]["maxFileSize"]
	
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeText($item).$this->makeHelp($item);
	 if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
     if(isset($item["data"]["maxFileSize"])) $ret .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.$item["data"]["maxFileSize"].'" />'."\n";
     
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
	 $ret .= '<input class="MFFile form-control'.$addclass.'" type="file" name="'.$name.'"/>';
	 if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'     </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
    break;
    case 'submit':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeText($item).$this->makeHelp($item);
	 if(!isset($this->data["bootstrap"])){
     $ret.= '<td class="MFFormColumn">';
	 }else{
	 $ret.='<div class="col-lg-offset-2 col-lg-10 MFFormColumn">';
	 }
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
	 
     $ret.= '<input class="MFSubmit btn btn-primary'.$addclass.'" type="submit" value="'.$this->getText($item["texts"]["insert"]).'"/>';
	 if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'     </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
	 if($this->merged) $this->merged->formDisplayed();
    break;
    case 'submitReset':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeText($item).$this->makeHelp($item);
	 if(!isset($this->data["bootstrap"])){
     $ret.= '<td class="MFFormColumn">';
	 }else{
	 $ret.='<div class="col-lg-offset-2 col-lg-10 MFFormColumn">';
	 
	 }
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
     $ret.= '<input class="MFSubmit btn btn-primary'.$addclass.'" type="submit" value="'.$this->getText($item["texts"]["insert"]).'" />
     <input class="MFSubmit btn btn-default"  type="reset" value="'.$this->getText($item["texts"]["reset"]).'" />';
	 if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'     </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
	 if($this->merged) $this->merged->formDisplayed();
    break;
	case "submitResetCancel":
	 if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
     $ret.= '<td class="MFFormColumn">';
	 }else{
	 $ret.='<div class="col-lg-offset-2 col-lg-10 MFFormColumn">';
	 
	 }
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
	 
     $ret.= '<input class="MFSubmit btn btn-primary'.$addclass.'" type="submit" value="'.$this->getText($item["texts"]["insert"]).'" />
     <input class="MFSubmit btn btn-default" type="reset" value="'.$this->getText($item["texts"]["reset"]).'" />
	 <a href="'.Path::make(array($this->data["uid"]."___CANCEL"=>"1")).'" class="btn btn-default">'.$this->getText("Cancel").'</a>';
	 if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'     </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
	 if($this->merged) $this->merged->formDisplayed();
	break;
     case 'hidden':
	 if(!isset($this->data["bootstrap"])){
		$ret.= '<tr><td><input type="hidden" value="'.$this->getText($item["texts"]["value"]).'" name="'.$name.'" /></td></tr>';
	 }else{
		$ret.= '<div class="form-group" style="display:none;"><input type="hidden" value="'.$this->getText($item["texts"]["value"]).'" name="'.$name.'" /></div>';
	 }
	 
    break;
    }
   }
   if(!isset($this->data["bootstrap"])){
	$ret .= "</table>";
   }
   
   $ret .= ''."\n";
  if(!$this->merged) $ret.='</form>';
  
    if(!isset($this->data["bootstrap"])){
		  $ret .= "</td></tr>";
	}   
   if($form_submitted && $this->item && isset($this->item["data"]["col"])){
    $ret .= '<script language="javascript">$("#'.$this->makeItemId($this->item).'").focus();</script>';
   }
   return $ret;
 }
 private function makeN2NInsertCols(){
	
  $cols =array(
	array("name"=>Language::get("Select existing or new item"),
						"form"=>array("type"=>"selectDB"),
						"data"=>array(
						    "col"=>$this->data["tableN2Ncol2"],
							"allowNull"=>true,
							"fromTable"=>$this->data["table"],
							"fromColumn"=>$this->data["tableN2Ncol2Textcol"],
							"dictionary"=>true,
							"where"=>@$this->data["tableN2Ncol2Where"],
						),"usage"=>array("MFi"),
						"texts"=>array("nullValue"=>"New item","allowNull"=>true),
					));
  $cols[] = array("form"=>array("type"=>"submitReset"),"texts"=>array("insert"=>"MF_insert","update"=>"MF_update","delete"=>"MF_delete","reset"=>"MF_reset"),"usage"=>array("MFi","MFu","MFd"));					
	return $cols;
}
 private function makeSelect(){
  $ret = "";
  // select
  $ret .="<select class=\"MF_Main_select\" name=\"".$this->data["uid"]."___ID\">";
  
  if(isset($this->data["mainColumn"]["maincol"])){
	$col = $this->data["mainColumn"]["maincol"];
  }else{
    $col = "id2";
  }
  $res = DB::g($this->data["table"],$this->where,$offset=null,$count=null,$order=array($col=>"asc"));
  while($row = DB::fetch_assoc($res)){
   if(isset($this->data["mainColumn"]["table"])){
    $rtable = $this->data["mainColumn"]["table"];
    $rcol   = $this->data["mainColumn"]["col"];
    $idCol   = $this->data["mainColumn"]["idCol"];
	if(!$idCol) $idCol = "id2";
    $isText   = $this->data["mainColumn"]["isText"];
    $row2 = DB::gr($rtable,array($idCol=>$row[$col]));
    if($isText){
     $row[$col] = $this->lang->getText($row2[$rcol],true);
    }else{
     $row[$col] = $row2[$rcol];
    }
   }
   if(isset($this->data["secondaryData"]["data"]))
   foreach($this->data["secondaryData"]["data"] as $data){
    if($data["type"] == "text"){
     $row[$col] .= $data["text"];
    }elseif($data["type"] == "column"){
     if($data["table"]){
      $colId = "id";
      if(@$data["colId"]){
      	$colId = $data["colId"];
      }
	  $row2 = DB::gr($data["table"],array($colId=>$row[$data["colIn"]]),array(),$cols=array($data["col"]),$groupby=array(),$having=array(),$offset=0,$time=$row["od"]);
	  
	  if($data["isText"]){
	   $row[$col] .= $this->lang->getText($row2[$colId],true);
	  }else{
       $row[$col] .= $row2[$colId];
	  }
     }else{
	  if($data["isText"]){
       $row[$col] .= $this->lang->getText($row[$data["text"]],true);
	  }else{
	   $row[$col] .= $row[$data["text"]];
	  }
     }
    }
   }
   $ret .= '<option value="'.$row["id"].'">';
   if(isset($data) && array_key_exists("isText",$data)){
    $ret .= strip_tags($this->getText($row[$col],true));
   }else{
    $ret .= strip_tags($row[$col]);
   }
   
   $ret .= '</option>'."\n";
  }
  
  $ret .= '</select>';
  return $ret;
 }
 private function update1Form(){
  if(!isset($this->data["allowUpdate"])) return false;
  if(isset($this->data["rights"])){
  if(isset($this->data["rights"]["update"]) && $this->data["rights"]["update"]){                 // ak sa vyzaduju prava na vkladanie, tak ich over
   if(Group::exists($this->data["rights"]["update"])){// ak existuje dane id skupiny
    if(!Group::isInGroupId($this->data["rights"]["update"])) return false;
   }else{// inak existuje nazov skupiny
    if(!Group::userInGroup($this->data["rights"]["update"])) return false;
   }
  }else{
	if(@$this->data["rights"]["update"]) return false;
  }
  }
  $text = "";
  if(isset($this->data["texts"]["update"])){ 
	$text = $this->getText($this->data["texts"]["update"]);
	if(!$text) $text = "Uprav položku";
  }else{
	$text = "";
  }
  
  $ret = '<tr><td colspan="4" class="MFSubHead">'.$text.'</td></tr>'."\n";
  $ret .= "<tr><td>";
  if($this->merged){
	$ret .= '<input type="hidden" name="'.$this->data["uid"]."___UPDATE1".'" value="1" />';
  }else{
   $ret .= "<form method=\"post\" action=\"".Path::make(array($this->data["uid"]."___UPDATE1"=>"1"))."\">\n";
  }
  $ret .= "<table>";
  $ret .= "<tr><td colspan=\"3\">";
  
  $ret .= $this->makeSelect();
  $text = "";
  if(isset($this->data["texts"]["updateMessage"])){
	$text = $this->getText($this->data["texts"]["updateMessage"]);
  }else{
	$text = "";//$this->getText($this->data["uid"]."-texts-updateMessage");
  }
  if(!$text) $text = "Načítaj";
  $ret.='<input type="submit" value="'.$text.'" />'."</td></tr>\n".'</table>';
  if(!$this->merged) $ret.='</form>';
  $ret .= "</td></tr>";
  return $ret;
 }
 private function makeCheck($item, $def = false){
  $ret = "<!-- Check -->";
  $name = $this->data["uid"]."_".$item["data"]["col"];
  if(isset($item["data"]["var"])) $name = $item["data"]["var"];
  if(isset($item["editable"]) && !$item["editable"]){
  
    if(!isset($this->data["bootstrap"])){
	 return $ret.'<td class="MFCheckColumn"></td>';
    }else{
	 return $ret.'<span class="MFCheckColumn"></span>';
	}
  }
  if(!isset($this->data["bootstrap"])){
  $ret.='<td class="MFCheckColumn" style="">';
  }else{
  $ret.='<span class="MFCheckColumn">';
  }
  $ret.='<input class="MFUpdateCheckBox"';
  if(@URLParser::v($name.'_CHANGED') || $def){
   $ret.=' checked="checked"';
  }
  $txt = "Pri zmenení položky sa automaticky zaškrtne. Znamená to, že položka sa pri odoslaní formuláru zmení.";
  if(class_exists("Language")){
   if(Language::getLang() != "sk"){
    $txt = "Check this checkbox, if you want to change the value in the line. Uncheck it if you do not want to change it and do not want to reset the form.";
   }
  }
  $ret.=' type="checkbox"  title="'.$txt.'" name="'.$name.'_CHANGED" id="'.$name.'_CHANGED" />';
  if(!isset($this->data["bootstrap"])){
   $ret.='</td>';
  }else{
   $ret.='</span>';
  }
  return $ret;
 }
 private function update2Form(){
  if(!isset($this->data["allowUpdate"])) return false;
  if(isset($this->data["rights"])){
  if(isset($this->data["rights"]["update"]) && $this->data["rights"]["update"]){                 // ak sa vyzaduju prava na vkladanie, tak ich over
   if(Group::exists($this->data["rights"]["update"])){// ak existuje dane id skupiny
    if(!Group::isInGroupId($this->data["rights"]["update"])) return false;
   }else{// inak existuje nazov skupiny
    if(!Group::userInGroup($this->data["rights"]["update"])) return false;
   }
  }else{
	if(@$this->data["rights"]["update"]) return false;
  }
  }
 
  $name = $this->data["uid"];
  $where=$this->where;
  $where["id"] = (int) URLParser::v($name."___ID");
  $formName = $name;
  $row = DB::gr($this->data["table"],$where);
  
  if(!$row){
  	Messages::message(Language::get("Error while selecting information from the database"));
  	\AsyncWeb\Storage\Log::log("MakeForm","update2 no row selected",ML__HIGH_PRIORITY);
  	Header::s("location",MakeForm::$redirectAfterSuccess);
  	exit;
  }


  $text = "";$ret="";
  if(isset($this->data["texts"]["update"])){
	$text = $this->getText($this->data["texts"]["update"]);
  }else{
	$text = "";//$this->getText($this->data["uid"]."-texts-update");
  }
  if($text){
  if(!isset($this->data["bootstrap"])){
	$ret = '<tr><td></td><td colspan="3" class="MFSubHead">'.$text."</td></tr>\n";
  }
  }
  if(@$this->data["showUp"]){
  if(!isset($this->data["bootstrap"])){
   $ret .= "<tr><td></td><td colspan=\"3\" class=\"MFSubHead\"><a href=\"".Path::make(array("REMOVE_VARIABLES"=>"1"))."\">".$this->get("Zobraziť data")."</a></td></tr>\n";
  }
  }
  if(!isset($this->data["bootstrap"])){
  $ret .= "<tr><td>";
  }
  if($this->merged){
	$ret .= '<input type="hidden" name="'.$this->data["uid"]."___UPDATE2".'" value="1" />';
  }else{
   $ret .= '<form class="form-horizontal" role="form"  method="post" action="'.Path::make(array($name."___UPDATE2"=>1))."\"";
	if(isset($this->data["enctype"]) && $this->data["enctype"]) $ret.= ' enctype="'.$this->data["enctype"].'"';
    if(isset($this->data["form"]["onUpdateSubmit"])){
    $ret .= " onsubmit=\"".$this->data["form"]["onUpdateSubmit"]."\"";
   }
   $ret .= ">\n";
  }
  $ret .= '<input type="hidden" value="'.((int)URLParser::v($name."___ID")).'" name="'.$name."___ID".'" />';
  
  if(!isset($this->data["bootstrap"])){
  $ret .= "<table>";
  $ret .= '<tr><td colspan="3">';
  $ret .= '</td></tr>';
  }
  
  foreach($this->data["col"] as $colname => $item){
	  $usg="MFu";if(isset($item["usage"]) && ((isset($item["usage"][$usg]) && $item["usage"][$usg]) || in_array($usg,$item["usage"]))){}else{continue;}


   if(isset($item["data"]["col"])) $colname = $col = $item["data"]["col"];
   if(!isset($item["form"])) continue;
   $col = $colname;
   $name = $formName."_".$col;
   if(isset($item["data"]["var"])) $name = $item["data"]["var"];
   $colValue = URLParser::v($name);
   if($in=$this->inWhere($col)){
	 $colValue = $in["value"];
	 $item["editable"] = false;
   }
   
   $form_submitted = (null!==URLParser::v($this->data["uid"]."___UPDATE2"));

	 $minl = @$item["data"]["minlength"];
	 $maxl = @$item["data"]["maxlength"];
	 $minn = @$item["data"]["minnum"];
	 $maxn = @$item["data"]["maxnum"];
	 $step = @$item["data"]["step"];   
	 
   switch($item["form"]["type"]){
    case 'password':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeCheck($item).$this->makeText($item).$this->makeHelp($item);
	 if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }

	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
     $ret.=' <input class="MFInput form-control'.$addclass.'" type="password" onchange="document.getElementById(\''.$name.'_CHANGED\').checked=true" id="'.$name.'" name="'.$name.'"';
     if(isset($item["editable"]) && !$item["editable"]){
      $ret.=' disabled="disabled"';
     }
     if(isset($this->data["data"]["maxlength"])) $ret.=' maxlength="'.$this->data["data"]["maxlength"].'"';
	 
	 if(isset($this->data["bootstrap"])){
		if(isset($item["texts"]["help"]) && $text=$this->getText($item["texts"]["help"])){
		 $ret.= ' title="'.Language::get("Help").'" placeholder="'.$text.'" data-content="'.$text.'" data-placement="bottom"';
		}
	 }
	 
	 $ret.='/>';
	 
     if(isset($item["data"]["datatype"]) && ($item["data"]["datatype"] == "date" || $item["data"]["datatype"] == "date_string")){
		$select = Language::get("Select date");
		if(\AsyncWeb\IO\File::exists($f = "img/icons/calendar.png")){
			$ret .= ' <a href="#image" onclick="document.getElementById(\''.$name.'_CHANGED\').checked=true;select_date(\''.$name.'\');return false;"><img width="20" height="20" src="/'.$f.'" class="icon" alt="'.$select.'" title="'.$select.'" /></a>';
		}else{
		   $ret .= ' <a href="#image" onclick="document.getElementById(\''.$name.'_CHANGED\').checked=true;select_date(\''.$name.'\');return false;">'.$select.'</a>';
		}
     }
     if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'      </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
    break;
    case 'captcha':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeCheck($item).$this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
		
		$err=null;
		$captcha = new \reCaptcha\Captcha();
		$captcha->setPrivateKey(MakeForm::$captchaPrivatekey);
		$captcha->setPublicKey(MakeForm::$captchaPublickey);
		$ret.=$captcha->html();

     if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'      </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
    break;
    case 'textbox':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeCheck($item).$this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
	$prepend = false;
	$after = false;
	if(isset($this->data["bootstrap"])){
		if(isset($item["texts"]["prepend"]) && $prepend=$this->getText($item["texts"]["prepend"])){
			$ret.='<div class="input-group"><span class="input-group-addon">'.$prepend.'</span>';
		}else{
			if(isset($item["texts"]["after"]) && $after=$this->getText($item["texts"]["after"])){
				$ret.='<div class="input-group">';
			}
		}
	}
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
     $ret.=' <input class="MFInput form-control'.$addclass.'" onchange="document.getElementById(\''.$name.'_CHANGED\').checked=true" type="text" id="'.$name.'" name="'.$name.'"';
     if($form_submitted){
	  if(URLParser::v("date_".$name) !== null){
       $ret.=' value="'.stripslashes(URLParser::v("date_".$name)).'"';
	  }else{
	   $ret.=' value="'.stripslashes($colValue).'"';
	  }
     }else{
	  
      if(isset($item["data"]["datatype"]) && $item["data"]["datatype"] == "date"){
       if(isset($item["data"]["format"])){
		$format1 = $item["data"]["format"];
	   }else{
		$format1 = "Y-m-d";
	   }
       $ret.=' value="'.stripslashes($this->convertDate($row[$col], true, $format1)).'"';
	  }else{
	   $value = @$row[$col];
	   if(isset($item["data"]["dictionary"]) && $item["data"]["dictionary"]){
	    $value = $this->getText($value,true);
	   }
	   $ret.=' value="'.stripslashes($value).'"';
	  }
     }
     if(isset($item["editable"]) && !$item["editable"]){
      $ret.=' disabled="disabled"';
     }

     if(isset($maxl)) $ret .= ' maxlength="'.$maxl.'"';
	 if(isset($minn)) $ret .= ' min="'.$minn.'"';
	 if(isset($maxn)) $ret .= ' max="'.$maxn.'"';
	 if(isset($step)) $ret .= ' step="'.$step.'"';
	 
	 if(isset($this->data["bootstrap"])){
		if(isset($item["texts"]["help"]) && $text=$this->getText($item["texts"]["help"])){
		 $ret.= ' title="'.Language::get("Help").'" placeholder="'.$text.'" data-content="'.$text.'" data-placement="bottom"';
		}
	 }
	 
	 $ret.='/>';
	 
	 if(isset($item["data"]["datatype"]))
     if($item["data"]["datatype"] == "date" || $item["data"]["datatype"] == "date_string"){
		$select = Language::get("Select date");
		if(\AsyncWeb\IO\File::exists($f = "img/icons/calendar.png")){
			$ret .= ' <a href="#image" onclick="document.getElementById(\''.$name.'_CHANGED\').checked=true;select_date(\''.$name.'\');return false;"><img width="20" height="20" src="/'.$f.'" class="icon" alt="'.$select.'" title="'.$select.'" /></a>';
		}else{
		   $ret .= ' <a href="#image" onclick="document.getElementById(\''.$name.'_CHANGED\').checked=true;select_date(\''.$name.'\');return false;">'.$select.'</a>';
		}
     }
	 if($prepend){
		$ret.='</div>';
	 }
	 if($after){
		$ret.='<span class="input-group-addon">'.$after.'</span></div>';;
	 }else{
		 if(isset($item["texts"]["after"]) && $t=$this->getText($item["texts"]["after"])){
		  $ret .= ' '.$t;
		 }
	 }	 

     if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'      </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
    break;
	case 'htmlText':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeCheck($item,true).$this->makeText($item).$this->makeHelp($item);
	 if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
	 $ret .= '<object type="application/x-xstandard" id="'.(string)$item->settings->editorName.'" width="'.(string)$item->settings->width.'" height="'.(string)$item->settings->height.'">';
	 $ret .= '<param name="CSS" value="'.(string)$item->settings->cssSubor.'" />';
	 $ret .= '<param name="Styles" value="'.(string)$item->settings->styleXMLSubor.'" />';
	 $ret .= '<param name="Lang" value="sk"/>';
     if((null!==$colValue)){
      $ret .= '<param name="Value" value="'.htmlspecialchars(stripslashes($colValue), ENT_COMPAT,'UTF-8').'" />';
     }else{
	   $value = $row[$col];
	   if(isset($item["data"]["dictionary"]) && $item["data"]["dictionary"]){
	    $value = $this->getText($value,true);
	   }
	   $ret .= '<param name="Value" value="'.htmlspecialchars($value, ENT_COMPAT,'UTF-8').'" />';
     }
	 $ret .= '<div>Ak vidíte túto správu, nainštalujte si XStandard Lite z http://xstandard.com/download.asp!</div>';
	 $ret .= '<textarea name="alternate1" id="alternate1" cols="50" rows="15">'.htmlspecialchars($row[$col], ENT_COMPAT,'UTF-8').'</textarea>';
	 $ret .= '</object>';
	 $ret .= '<input type="hidden" name="novy_text" id="novy_text" value="" /> ';
	 if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'      </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }

	break;

    case 'select':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeCheck($item).$this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
     $ret.=' <select class="MFSelect form-control'.$addclass.'" id="'.$name.'" name="'.$name.'" onchange="document.getElementById(\''.$name.'_CHANGED\').checked=true"';
     if(isset($item["editable"]) && !$item["editable"]){
      $ret.=' disabled="disabled"';
     }
     $ret .='>';
     
     foreach($item["filter"]["option"] as $k=>$v){
      $ret .= '<option value="'.$k.'"';
      if($form_submitted){
       if($colValue == $k){
        $ret .= ' selected="selected"';
       }
      }else{
       if(array_key_exists($col,$row) && $row[$col] == $k){
        $ret .= ' selected="selected"';
       }elseif($in=$this->inWhere($colname) && $k==$in["value"]){
	    $ret .= ' selected="selected"';
	   }
      }
      $ret .='>'.strip_tags($v).'</option>'."\n";
     }
     $ret.='</select>';
	 
 	 if(isset($item["texts"]["after"]) && $t = $this->getText($item["texts"]["after"])){
	  $ret .= ' '.$t;
	 }

     if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'      </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }

    break;

    case 'set':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeCheck($item).$this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
     $ret.=' <select class="MFSelect form-control'.$addclass.'" id="'.$name.'" name="'.$name.'[]" onchange="document.getElementById(\''.$name.'_CHANGED\').checked=true"';
     if(isset($item["editable"]) && !$item["editable"]){
      $ret.=' disabled="disabled"';
     }
     $ret .=' multiple="multiple">';
     
     foreach($item["filter"]["option"] as $k=>$v){
      $ret .= '<option value="'.$k.'"';
      if($form_submitted){
       if($colValue == $k){
        $ret .= ' selected="selected"';
       }
      }else{
	   $items = explode(";", @$row[$col]);
       if(in_array($k,$items)){
        $ret .= ' selected="selected"';
       }elseif($in=$this->inWhere($colname) && $k==$in["value"]){
	    $ret .= ' selected="selected"';
	   }
      }
      $ret .='>'.strip_tags($v).'</option>'."\n";
     }
     $ret.='</select>';
	 
 	 if(isset($item["texts"]["after"]) && $t = $this->getText($item["texts"]["after"])){
	  $ret .= ' '.$t;
	 }

     if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'      </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }

    break;	
    case 'selectDB':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeCheck($item).$this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
     $ret.=' <select class="MFSelect form-control'.$addclass.'" onchange="document.getElementById(\''.$name.'_CHANGED\').checked=true" class="MFSelect form-control'.$addclass.'" id="'.$name.'" name="'.$name.'"';
     if(isset($item["editable"]) && !$item["editable"]){
      $ret.=' disabled="disabled"';
     }
     $ret.='>';
	 $options = array();
	 
     $index = "id2";
     $where = 1;
	 
     $col2 = $item["data"]["fromColumn"];
     $index = "id2";
	 if(!isset($item["data"]["where"])) $item["data"]["where"] = array();
	 $res1 = DB::g($item["data"]["fromTable"],$item["data"]["where"],null,null,@$item["data"]["order"]);
	 
     while($row1 = DB::f($res1)){
	  if((isset($item["data"]["isText"]) && $item["data"]["isText"]) || (isset($item["data"]["dictionary"]) && $item["data"]["dictionary"])){
	   $options[$row1[$index]] =  strip_tags($this->getText($row1[$col2],true));
	  }else{
	   $options[$row1[$index]] = $this->getInnerDBColConfig($row1,$col2);
	  }
     }
	 $reto = false;

	 asort($options);//sort by value, maintain key assoc
	 foreach($options as $k=>$v){
		$k = "".$k;
	 $reto .= '<option value="'.$k.'"';
	  if($form_submitted){
       if($colValue == $k){
        $reto .= ' selected="selected"';
       }       
      }else{
       if($row[$col] == "".$k){
        $reto .= ' selected="selected"';
       }elseif($in=$this->inWhere($colname) && $k == $in["value"]){
	    $reto .= ' selected="selected"';
	   }
      }
	   $reto .= '>'.$v.'</option>'."\n";
	 }
	 	 
	  if(isset($item["data"]["allowNull"]) && $item["data"]["allowNull"]){
	   if(isset($item["texts"]["nullValue"]) && $item["texts"]["nullValue"]){
        $reto = '<option value="0">'.strip_tags($this->getText($item["texts"]["nullValue"])).'</option>'.$reto;
	   }else{
		   $text = $this->getText("nullValue");
		   if($text == "nullValue") $text = Language::get("Please select value");
       $reto = '<option value="0">'.$text.'</option>'.$reto;
	   }
      }
	 $ret.=$reto;
     $ret.='</select>';
	 if(isset($item["texts"]["after"]) && $t = $this->getText($item["texts"]["after"])){
	  $ret .= ' '.$t;
	 }

     if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'      </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
    break;
    case 'textarea':

     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeCheck($item).$this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
     $ret.=' <textarea class="MFTextArea form-control'.$addclass.'" class="MFTextArea form-control'.$addclass.'" id="'.$name.'" name="'.$name.'" onchange="document.getElementById(\''.$name.'_CHANGED\').checked=true"';
     if(isset($item["editable"]) && !$item["editable"]){
      $ret.=' disabled="disabled"';
     }
     $ret.=' cols="50" rows="15">';
     if($form_submitted){
      $ret .= $colValue;
     }else{
	   $value = $this->encodeEntities($row[$col]);
	   if(isset($item["data"]["dictionary"]) && $item["data"]["dictionary"]){
	    $value = $this->encodeEntities($this->getText($value,true));
	   }
      $ret .= $value;
     }
     $ret.='</textarea>';
     if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'      </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }

     break;
    case 'tinyMCE':

     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeCheck($item,true).$this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
     $ret.=' <textarea class="MFTextArea form-control'.$addclass.'" class="MFTextArea form-control'.$addclass.'" id="'.$name.'" name="'.$name.'" onchange="document.getElementById(\''.$name.'_CHANGED\').checked=true"';
     if(isset($item["editable"]) && !$item["editable"]){
      $ret.=' disabled="disabled"';
     }
     $ret.=' cols="50" rows="15">';
     if($form_submitted){
      $ret .= $colValue;
     }else{
	   $value = $this->encodeEntities($row[$col]);
	   if(isset($item["data"]["dictionary"]) && $item["data"]["dictionary"]){
	    $value = $this->encodeEntities($this->getText($value,true));
	   }
      $ret .= $value;
     }
     
$theme = "simple";
	 if(isset($item["form"]["theme"])){
		$theme = $item["form"]["theme"];
	 }
     $ret.='</textarea>';
	 if($theme != "advanced"){
	 $ret.= '	 
	 <script type="text/javascript">
	$().ready(function() {
		tinymce.init({selector:"#'.$name.'",
		remove_script_host : false,
		convert_urls : false,
		relative_urls : false
		
		';
		if(\AsyncWeb\IO\File::exists($f="/js/tinymce/langs/".substr(\AsyncWeb\System\Language::getLang(),0,2).".js")) $ret.=',language_url :"'.$f.'"';
		$ret.='});
	});
	</script>';
	
	 }else{
//			theme_advanced_styles : "image",
	 $ret.= '
<script type="text/javascript">
	$().ready(function() {
		tinymce.init({selector:"#'.$name.'",
		remove_script_host : false,
		convert_urls : false,
		relative_urls : false,
		plugins : "advlist autolink link image lists charmap print preview searchreplace visualblocks code fullscreen insertdatetime media table contextmenu paste"';
		if(\AsyncWeb\IO\File::exists($f="/js/tinymce/langs/".substr(\AsyncWeb\System\Language::getLang(),0,2).".js")) $ret.=',language_url :"'.$f.'"';
		$ret.='});
	});
</script>
	 ';
	 }
	 
     if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'      </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }

     break;
    case 'checkbox':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeCheck($item).$this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
     $ret.=' <input class="MFCheckBox form-control'.$addclass.'" type="checkbox" id="'.$name.'" name="'.$name.'" onchange="document.getElementById(\''.$name.'_CHANGED\').checked=true"';
     if($form_submitted){
      if(URLParser::v($name) !== null)
      if($colValue=="on"){
       $ret.= ' checked="checked"';
      }
     }else{
      if($row[$col]){
       $ret.= ' checked="checked"';
      }
     }
     if(isset($item["editable"]) && !$item["editable"]){
      $ret.=' disabled="disabled"';
     }
     $ret .= "/>";
	 if(isset($item["texts"]["after"]) && $t = $this->getText($item["texts"]["after"])){
	  $ret .= ' '.$t;
	 }
	 
     if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'      </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
    break;
    case 'radio':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeCheck($item).$this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
     $value = $this->getText($item["texts"]["value"]);
     $ret.=' <input class="MFRadio form-control'.$addclass.'" value="$value" type="radio" id="'.$name.'" name="'.$name.'" onchange="document.getElementById(\''.$name.'_CHANGED\').checked=true"';
     if($form_submitted){
      if($colValue==$value){
       $ret.= ' checked="checked"';
      }
     }else{
      if($row[$col] == $value){
       $ret.= ' checked="checked"';
      }
     }
     if(isset($item["editable"]) && !$item["editable"]){
      $ret.=' disabled="disabled"';
     }
     $ret .= "/>";
     if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'      </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
    break;
    case 'part':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= '<td class="MFCheckColumn"></td>'.$this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'      </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }

    break;
    case 'file':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= $this->makeCheck($item).$this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-10 MFFormColumn">';
	 }
     if(isset($item->data["data"]["maxFileSize"])) $ret .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.$item->data["data"]["maxFileSize"].'" />'."\n";
     $ret .= '<input class="MFFile" type="file" name="'.$name.'" onchange="document.getElementById(\''.$name.'_CHANGED\').checked=true"';
     if(isset($item["editable"]) && !$item["editable"]){
      $ret.=' disabled="disabled"';
     }
     $ret .='/>';
     if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'      </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
    break;
    case 'submit':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= '<td class="MFCheckColumn"></td>'.$this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-offset-2 col-lg-10 MFFormColumn">';
	 }
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
	 
     $ret.= '<input class="MFSubmit btn btn-primary'.$addclass.'" type="submit" value="'.$this->getText($item["texts"]["update"]).'"/>';
     if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'      </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
	 
	 if($this->merged) $this->merged->formDisplayed();
    break;
 	case "submitResetCancel":
    case 'submitReset':
     if(!isset($this->data["bootstrap"])){
		$ret.= '<tr>';
	 }else{
		$ret.= '<div class="form-group">';
	 }
	 $ret .= '<td class="MFCheckColumn"></td>'.$this->makeText($item).$this->makeHelp($item);
     if(!isset($this->data["bootstrap"])){
		$ret.= '<td class="MFFormColumn">';
	 }else{
		$ret.='<div class="col-lg-offset-2 col-lg-10 MFFormColumn">';
	 }
	 $addclass = "";if(isset($item["form"]["class"])) $addclass = " ".$item["form"]["class"];
	 
     $ret.= '<input class="MFSubmit btn btn-primary'.$addclass.'" type="submit" value="'.$this->getText($item["texts"]["update"]).'" />
     <input class="MFSubmit btn btn-default" type="reset" value="'.$this->getText($item["texts"]["reset"]).'" />';
     if(!isset($this->data["bootstrap"])){
		$ret .= '</td>'."\n".'      </tr>'."\n";
	 }else{
		$ret.='</div></div>'."\n";
	 }
	 
	 if($this->merged) $this->merged->formDisplayed();
    break;
    }
   }
  $ret .= "</table>";
  $ret.='<script type="text/javascript">$(".MFCheckColumn").hide();$(".MFFormColumn").dblclick(function(){  $(".MFCheckColumn").show(); });</script>';
   $ret .= "\n".'';
  if(!$this->merged) $ret.='</form>';
  $ret .= "</td></tr>";
   if($this->item && isset($this->item["data"]["col"])){
    $ret .= '<script language="javascript">$("#'.$this->makeItemId($item).'").focus();</script>';
   }

  return $ret;
 }
 private function deleteForm(){
 if(!isset($this->data["allowDelete"])) return false;
  if(isset($this->data["rights"])){
  if(isset($this->data["rights"]["delete"])&& $this->data["rights"]["delete"]){                 // ak sa vyzaduju prava na vkladanie, tak ich over
   if($this->data["rights"]["delete"]){
    if(Group::exists($this->data["rights"]["delete"])){// ak existuje dane id skupiny
     if(!Group::isInGroupId($this->data["rights"]["delete"])) return false;
    }else{// inak existuje nazov skupiny
     if(!Group::userInGroup($this->data["rights"]["delete"])) return false;
    }
   }
  }else{
	if(@$this->data["rights"]["delete"]) return false;
  }
  }
  
  $name = $this->data["uid"];
  if(isset($this->data["texts"]["delete"])){
	$text = $this->getText($this->data["texts"]["delete"]);
  }else{
	$text = $this->getText($this->data["uid"]."-texts-delete");
  }
  if(!$text) $text = "Zruš položku";
  $ret = '<tr><td colspan="3" class="MFSubHead">'.$text.'</td></tr>'."\n";
  if(isset($this->data["texts"]["deleteWarning"])){
	$text = $this->getText($this->data["texts"]["deleteWarning"]);
  }else{
	$text = $this->getText($this->data["uid"]."-texts-deleteWarning");
  }
  
  if(!$text) $text = "Naozaj chcete zrušiť položku?";
  
  $confirm_text = $text;
  $confirm_text = str_replace("\n","'+'\\n'+\n'",$confirm_text);
  
  $ret .= "<tr><td>";
  if($this->merged){
	$ret .= '<input type="hidden" name="'.$this->data["uid"]."___DELETE".'" value="1" />';
  }else{
   $ret .= "<form method=\"post\" action=\"".Path::make(array($name."___DELETE"=>"1"))."\" onsubmit=\"confirm('".$confirm_text."')?ret=true:ret=false;return ret;\">\n";
  }
  $ret .= "<table>";
  $ret .= '<tr><td colspan="3">';
  
  $ret .= $this->makeSelect();
  if(isset($this->data["texts"]["deleteWarning"])){
	$text = $this->getText($this->data["texts"]["deleteMessage"]);
  }else{
	$text = $this->getText($this->data["uid"]."-texts-deleteMessage");
  }
  if(!$text) $text = "Zruš";
  $ret.='<input type="submit" value="'.$text.'" />'."</td></tr>\n".'</table>';
  if(!$this->merged) $ret.='</form>';
  $ret .= "</td></tr>";
  return $ret;
 }
 private $results_shown = false;
 public function show_results(){
  $ret = "";
  if($this->results_shown) return false;
  $this->results_shown = true;
  if($this->exception != null){
   if($this->exception->getMessage()){
    $ret .= '<div class="MFError alert alert-danger">'.$this->exception->getMessage().'</div>';
   }
   $this->exception = null;
  }else{
   if(@$_SESSION["mes"][URLParser::v("mes")]){
    $ret .= '<div class="MFMessage">'.$_SESSION["mes"][URLParser::v("mes")].'</div>';
   }
  }
  return $ret;
 }
} // endof class

