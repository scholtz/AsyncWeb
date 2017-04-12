<?php
////////////////////////////////////////////
//
// Created & designed by Ludovit Scholtz
// ludovit __ AT __ scholtz.sk
//
// 6.9.2016	 	Unified texts section for fule management
//
// 6.2.2016	 	Added variables $form->BT_WIDTH_OF_LABEL and $form->BT_SIZE
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
 public $BT_SIZE = "md";
 public $BT_WIDTH_OF_LABEL = 2;
 
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
 public static $captchaPublickey = ""; // to be filled in settings if captcha is enabled
 public static $captchaPrivatekey = ""; // to be filled in settings if captcha is enabled
 public static $disableCaptcha = false;
 
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
  protected $checked = false;
 public function check_update(){
	try{
		if($this->checked) return;
		$this->checked = true;
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
	if($this->exception){
		\AsyncWeb\Text\Msg::err($this->exception->getMessage());
		Header::s("reload",array($this->data["uid"]."___INSERT"=>"","insert_data_".$this->data["uid"]=>"",$this->data["uid"]."___CANCEL"=>"",$this->data["uid"]."___ID"=>"",$this->data["uid"]."___UPDATE2"=>"",$this->data["uid"]."___UPDATE1"=>"",$this->data["uid"]."___ID"=>"",$this->data["uid"]."___DELETE"=>""));
		exit;
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
	if($form_type == "captcha" && !MakeForm::$disableCaptcha && MakeForm::$captchaPublickey){
		if(class_exists("\\ReCaptcha\\ReCaptcha")){
			$recaptcha = new \ReCaptcha\ReCaptcha(MakeForm::$captchaPrivatekey);
			$resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
			if (!$resp->isSuccess()){
				$error = $resp->error;
				\AsyncWeb\Storage\Log::log("CaptchaError",$error);
				throw new \Exception($this->getExceptionText($item,"captchaTypeException"));
			}
		}elseif(class_exists("\\reCaptcha\\Captcha")){
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
	 throw $exc;
	}
   }
  }
  
  $data = array();
  $includedCols = array();

  foreach($this->data["col"] as $colname=>$item){
	  
   if(isset($item["data"]["type"])) $item["data"]["datatype"] = $item["data"]["type"];
   $usg="MFi";if(isset($item["usage"]) && ((isset($item["usage"][$usg]) && $item["usage"][$usg]) || in_array($usg,$item["usage"]))){}else{continue;}
  
   if(is_numeric($colname) && !isset($item["data"]["col"])) continue;
   
	if(isset($item["data"]["col"])) $colname = $item["data"]["col"];
	$n = $formName."_".$colname;
	
	if(isset($item["data"]["var"])) $n = $item["data"]["var"];
	$colValue = URLParser::v($n);

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
	    
	if(!isset($item["FormItemInstance"])){
		if(!self::$ItemsMap){
			self::MakeItemsMap();
		}
		if(!isset(self::$ItemsMap[$item["form"]["type"]])){
			throw new \Exception(Language::get("Form type %formtype% has not been found!",array("%formtype%"=>$item["form"]["type"])));
		}
		$item["FormItemInstance"] = new self::$ItemsMap[$item["form"]["type"]]($item,$this->data);
	}
	$data[$colname] = $item["FormItemInstance"]->Validate($name1);
	$data[$colname] = $this->filters($data[$colname],$datatype,true);	


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
	}elseif(isset($this->data["texts"]["insertSuccess"])){
		$text = $this->getText($this->data["texts"]["insertSuccess"]);
	}else{
		$text = $this->getText("insertSucces");
	}
    if(!$text || $text == "insertSucces" || $text == "insertSuccess") $text = Language::get("New item has been successfully inserted");
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
	 throw $exc;
	}
   }
  }
  $langupdates = array();
  
  foreach($this->data["col"] as $colname=>$item){
   $usg="MFu";if(isset($item["usage"]) && ((isset($item["usage"][$usg]) && $item["usage"][$usg]) || in_array($usg,$item["usage"]))){}else{continue;}
   if(is_numeric($colname) && !isset($item["data"]["col"])) continue;
   if(isset($item["data"]["type"])) $item["data"]["datatype"] = $item["data"]["type"];
   if(isset($item["data"]["col"])) $colname = $item["data"]["col"];
   $name = $colname;
   $n = $formName."_".$name;
   if(isset($item["data"]["var"])) $n = $item["data"]["var"];
   $colValue = URLParser::v($n);
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


		if(!isset($item["FormItemInstance"])){
			if(!self::$ItemsMap){
				self::MakeItemsMap();
			}
			if(!isset(self::$ItemsMap[$item["form"]["type"]])){
				throw new \Exception(Language::get("Form type %formtype% has not been found!",array("%formtype%"=>$item["form"]["type"])));
			}
			$item["FormItemInstance"] = new self::$ItemsMap[$item["form"]["type"]]($item,$this->data);
		}
		
 
		if($item["FormItemInstance"]->IsDictionary()){
			$langupdates[$colname] = $item["FormItemInstance"]->Validate($colValue);
			$langupdates[$colname] = $this->filters($langupdates[$colname],$datatype,true);	
		}else{
			$cols[$colname] = $item["FormItemInstance"]->Validate($colValue);
			$cols[$colname] = $this->filters($cols[$colname],$datatype,true);	
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
   
   foreach($this->updateLangs as $k=>$v){
    if(!$this->updateData[$k]){
		$key = substr("Z___".md5(uniqid()),0,32);
		$this->updateData[$k] = $key;
	}
	$key=$this->updateData[$k];
	Language::set($key,$v);
   }
   
   
   if(!DB::u($this->data["table"],$row["id2"],$this->updateData)){
   	 \AsyncWeb\Storage\Log::log("MakeForm","Update oddo failed".DB::error(),ML__HIGH_PRIORITY);
	 $this->exception = new \Exception(Language::get("Error while editing the item!"));
	 throw $this->exception;
   }
   
   $row = DB::gr($this->data["table"],$row["id2"]);
   
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
		if(isset($this->data["texts"]["updateSuccess"])){
			$text = $this->getText($this->data["texts"]["updateSuccess"]);
		}elseif(isset($this->data["texts"]["updateSucces"])){
			$text = $this->getText($this->data["texts"]["updateSucces"]);
		}else{
			$text = $this->getText("updateSucces");
		}
	   if(!$text || $text == "updateSucces" || $text == "updateSuccess") $text = Language::get("Item has been successfully updated");
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
			if(isset($this->data["texts"]["deleteSuccess"])){
				$text = $this->getText($this->data["texts"]["deleteSuccess"]);
			}elseif(isset($this->data["texts"]["deleteSucces"])){
				$text = $this->getText($this->data["texts"]["deleteSucces"]);
			}else{
				$text = $this->getText("deleteSucces");
			}
		   if(!$text || $text == "deleteSucces") $text = Language::get("Deletion has been successfully commited");
		   Messages::getInstance()->mes($text);//$this->data["uid"]."___DELETE"

		   Header::s("reload",array($this->data["uid"]."___ID"=>"",$this->data["uid"]."___DELETE"=>""));exit;
		   exit;
	   }
	   return true;
   }else{
   
		if(!$this->merged){
			$text = "";
		   if(isset($this->data["texts"]["deleteNotSuccess"])){
			$text = $this->getText("deleteNotSuccess");
		   }elseif(isset($this->data["texts"]["deleteNotSucces"])){
			$text = $this->getText("deleteNotSucces");
		   }
		   if(!$text || $text == "deleteNotSucces"|| $text == "deleteNotSuccess") $text = Language::get("Error occured while deleting the item");
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
   <label for="'.$id.'" class="col-'.$this->BT_SIZE.'-'.$this->BT_WIDTH_OF_LABEL.' control-label">'.$text.'</label>
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
		if(!$text) $text = "Insert new item";
	}else{
		$text = "";
	}
  
	$ret= "";	
	$form_submitted = (null!==URLParser::v($this->data["uid"]."___INSERT"));
  
  
	foreach($this->data["col"] as $colname=>$item){
		$usg="MFi";if(isset($item["usage"]) && ((isset($item["usage"][$usg]) && $item["usage"][$usg]) || in_array($usg,$item["usage"]))){}else{continue;}

		if(isset($item["data"]["col"])) $colname = $item["data"]["col"];
		$name = $formName."_".$colname;
		if(isset($item["data"]["var"])) $name = $item["data"]["var"];
		
		$colValue = null;
		if($form_submitted)
			$colValue = URLParser::v($name);

		if($in=$this->inWhere($colname)){
			$colValue = $in["value"];
			$item["editable"] = false;
		}

  
		if(!isset($item["BT_SIZE"])) $item["BT_SIZE"] = $this->BT_SIZE;
		if(!isset($item["BT_WIDTH_OF_LABEL"])) $item["BT_WIDTH_OF_LABEL"] = $this->BT_WIDTH_OF_LABEL;
		if(!isset($item["BT_WIDTH_9"])) $item["BT_WIDTH_9"] = 12 - 1 - $this->BT_WIDTH_OF_LABEL;
		if(!isset($item["BT_WIDTH_10"])) $item["BT_WIDTH_10"] = 12 - $this->BT_WIDTH_OF_LABEL;
		
		if(!isset($item["FormItemInstance"])){
			if(!self::$ItemsMap){
				self::MakeItemsMap();
			}
			if(!isset(self::$ItemsMap[$item["form"]["type"]])){
				throw new \Exception(Language::get("Form type %formtype% has not been found!",array("%formtype%"=>$item["form"]["type"])));
			}
			$item["FormItemInstance"] = new self::$ItemsMap[$item["form"]["type"]]($item,$this->data);
		}
		

		$ret .= $item["FormItemInstance"]->InsertForm($colValue);
			 
		switch($item["form"]["type"]){
			case 'submit':
			case 'submitReset':
			case "submitResetCancel":
				if($this->merged) $this->merged->formDisplayed();
			break;
		}
	}
	
	if($form_submitted && $this->item && isset($this->item["data"]["col"])){
		$Focus = $this->makeItemId($this->item);
	}
	
	$ID = $this->data["uid"];
	$SubmitURL = Path::make(array($ID."___INSERT"=>1));
	$EncType = false;if(isset($this->data["enctype"]) && $this->data["enctype"]) $EncType = $this->data["enctype"];
	$OnSubmit = false;if(isset($this->data["form"]["onInsertSubmit"]) && $this->data["form"]["onInsertSubmit"]) $OnSubmit = $this->data["form"]["onInsertSubmit"];
	$Template = "View_InsertForm"; if(isset($this->data["template"]["insert"]) && $this->data["template"]["insert"]) $Template = Language::get($this->data["template"]["insert"]);
	
	return \AsyncWeb\Text\Template::loadTemplate($Template,array(
			"HeaderText"=>$text,
			"HTML"=>$ret,
			"Focus"=>$Focus,
			"ID"=>$this->data["uid"],
			"Merged"=>$this->merged,
			"SubmitURL"=>$SubmitURL,
			"OnSubmit"=>$OnSubmit,
			"EncType"=>$EncType,
			
		),false,false);		
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
	if(MakeForm::$redirectAfterSuccess == "?"){	
		Header::s("reload",array($this->data["uid"]."___ID"=>"",$this->data["uid"]."___UPDATE2"=>"",$this->data["uid"]."___UPDATE1"=>""));
	}else{
		Header::s("location",MakeForm::$redirectAfterSuccess);
	}
  	exit;
  }


	$text = "";$ret="";
	if(isset($this->data["texts"]["update"])){
		$text = $this->getText($this->data["texts"]["update"]);
	}else{
		$text = "";
	}

  
	$form_submitted = (null!==URLParser::v($this->data["uid"]."___UPDATE2"));
	
	foreach($this->data["col"] as $colname => $item){
		$usg="MFu";if(isset($item["usage"]) && ((isset($item["usage"][$usg]) && $item["usage"][$usg]) || in_array($usg,$item["usage"]))){}else{continue;}


		if(isset($item["data"]["col"])) $colname = $col = $item["data"]["col"];
		if(!isset($item["form"])) continue;
		$col = $colname;
		$name = $formName."_".$col;
		if(isset($item["data"]["var"])) $name = $item["data"]["var"];
		
		$colValue = null;
		if($form_submitted)
			$colValue = URLParser::v($name);
		
		if($in=$this->inWhere($col)){
			$colValue = $in["value"];
			$item["editable"] = false;
		}
   
  
		if(!isset($item["BT_SIZE"])) $item["BT_SIZE"] = $this->BT_SIZE;
		if(!isset($item["BT_WIDTH_OF_LABEL"])) $item["BT_WIDTH_OF_LABEL"] = $this->BT_WIDTH_OF_LABEL;
		if(!isset($item["BT_WIDTH_9"])) $item["BT_WIDTH_9"] = 12 - 1 - $this->BT_WIDTH_OF_LABEL;
		if(!isset($item["BT_WIDTH_10"])) $item["BT_WIDTH_10"] = 12 - $this->BT_WIDTH_OF_LABEL;
		
		if(!isset($item["FormItemInstance"])){
			if(!self::$ItemsMap){
				self::MakeItemsMap();
			}
			if(!isset(self::$ItemsMap[$item["form"]["type"]])){
				throw new \Exception(Language::get("Form type %formtype% has not been found!",array("%formtype%"=>$item["form"]["type"])));
			}
			$item["FormItemInstance"] = new self::$ItemsMap[$item["form"]["type"]]($item,$this->data);
		}
		
		$ret.=$item["FormItemInstance"]->UpdateForm($colValue);
		
		switch($item["form"]["type"]){
			case 'submit':
			case 'submitReset':
			case "submitResetCancel":
				if($this->merged) $this->merged->formDisplayed();
			break;
		}
	 
	}
	
	$ID = $this->data["uid"];
	$SubmitURL = Path::make(array($ID."___UPDATE2"=>1));
	$OriginalRecordID = URLParser::v($ID."___ID");
	
	if($form_submitted && $this->item && isset($this->item["data"]["col"])){
		$Focus = $this->makeItemId($this->item);
	}
	$Template = "View_UpdateForm"; if(isset($this->data["template"]["update"]) && $this->data["template"]["update"]) $Template = Language::get($this->data["template"]["update"]);
	
	$ShowUp = false;if(isset($this->data["showUp"]) && $this->data["showUp"]) $ShowUp = true;
	$ShowUpURL = $ShowUpText= false;
	if($ShowUp){
		$ShowUpURL = Path::make(array("REMOVE_VARIABLES"=>"1"));
		$ShowUpText = Language::get("Show data");
	}
	
	return \AsyncWeb\Text\Template::loadTemplate($Template,array(
			"HeaderText"=>$text,
			"HTML"=>$ret,
			"Focus"=>$Focus,
			"ID"=>$this->data["uid"],
			"Merged"=>$this->merged,
			"SubmitURL"=>$SubmitURL,
			"OnSubmit"=>$OnSubmit,
			"EncType"=>$EncType,
			"OriginalRecordID"=>$OriginalRecordID,
			"ShowUp"=>$ShowUp,
			"ShowUpURL"=>$ShowUpURL,
			"ShowUpText"=>$ShowUpText,
		),false,false);	
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
	protected static $ItemsMap = array();
	public static function MakeItemsMap(){
		self::RegisterItemsMap("\\AsyncWeb\\View\\FormItem\\Value");
		self::RegisterItemsMap("\\AsyncWeb\\View\\FormItem\\Password");
		self::RegisterItemsMap("\\AsyncWeb\\View\\FormItem\\XHTMLText");
		self::RegisterItemsMap("\\AsyncWeb\\View\\FormItem\\TinyMCE");
		self::RegisterItemsMap("\\AsyncWeb\\View\\FormItem\\TextBox");
		self::RegisterItemsMap("\\AsyncWeb\\View\\FormItem\\Hidden");
		self::RegisterItemsMap("\\AsyncWeb\\View\\FormItem\\TextArea");
		self::RegisterItemsMap("\\AsyncWeb\\View\\FormItem\\Radio");
		self::RegisterItemsMap("\\AsyncWeb\\View\\FormItem\\Select");
		self::RegisterItemsMap("\\AsyncWeb\\View\\FormItem\\SelectDB");
		self::RegisterItemsMap("\\AsyncWeb\\View\\FormItem\\Set");
		self::RegisterItemsMap("\\AsyncWeb\\View\\FormItem\\CheckBox");
		self::RegisterItemsMap("\\AsyncWeb\\View\\FormItem\\File");
		
		self::RegisterItemsMap("\\AsyncWeb\\View\\FormItem\\Captcha");
		self::RegisterItemsMap("\\AsyncWeb\\View\\FormItem\\SubmitResetCancel");
		self::RegisterItemsMap("\\AsyncWeb\\View\\FormItem\\SubmitReset");
		self::RegisterItemsMap("\\AsyncWeb\\View\\FormItem\\Submit");
		self::RegisterItemsMap("\\AsyncWeb\\View\\FormItem\\Part");
	}
	public static function RegisterItemsMap($class){
		$impl = class_implements($class);
		
		if(!isset($impl['AsyncWeb\\View\\FormItemInterface'])){
			throw new \Exception(Language::get("Class $class must implement FormItemInterface"));
		}
		$tmp=array();
		$instance = new $class($tmp,$tmp);
		$tag = $instance->TagName();
		if(isset(self::$ItemsMap[$tag])) throw new \Exception(Language::get("Tag name $tag is already defined in view manager!"));
		self::$ItemsMap["".$tag] = "".$class;
	}
} // endof class

