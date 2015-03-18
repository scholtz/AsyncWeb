<?php

class User{
	public static function get($id2=null){
		if($id2==null){
			require_once("modules/Login.php");
			$id2=Login::getUserId();
		}
		require_once("modules/DB.php");
		return DB::gr("users",$id2);
	}
	public static function getDPH($id2){
		$ret = User::getUserSetting($id2,"VAT");
		if($ret){
			return $ret;
		}
		$row = DB::gr("obchodnik_ht",array("users"=>$id2));
		if($row){
			return $row["vattype"];
		}
		$row = DB::gr("obchodnik_vt",array("users"=>$id2));
		if($row){
			return $row["vattype"];
		}
		$row = DB::gr("obchodnik_eua",array("users"=>$id2));
		if($row){
			return $row["vattype"];
		}
		return "d";
	}
	public static function getDPHId($id2=null){
		if(!$id2){
			require_once("modules/Login.php");
			$id2=Login::getUserId();
		}
		$row = DB::gr("obchodnik_ht",array("users"=>$id2));
		if($row){
			return $row["dic"];
		}
		$row = DB::gr("obchodnik_vt",array("users"=>$id2));
		if($row){
			return $row["dic"];
		}
		$row = DB::gr("obchodnik_eua",array("users"=>$id2));
		if($row){
			return $row["vat"];
		}
		return "f";
	}
	public static function getFullName($id2=null){
		if(!$id2){require_once("modules/Login.php");$id2 = Login::getUserId();}
		if(is_array($id2)){
			$row = $id2;
		}else{
			$row = User::get($id2);
		}
		$ret = $row["meno"];
		if($row["priezvisko"]){ 
			if($ret) $ret.=" ";
			$ret.=$row["priezvisko"];
		}
		if(!$ret){
			$ret=$row["login"];
		}
		return $ret;
	}
	public static function getCompanyOrName($id2=null){
		if(!$id2){require_once("modules/Login.php");$id2 = Login::getUserId();}
		$pc = DB::gr("pohoda_contacts",array("users"=>$id2));
		if($pc && $pc["company"]) return $pc["company"];
		$obch = DB::gr("obchodnik_eua",array("users"=>$id2));
		if($obch && $obch["spolocnost"]) return $obch["spolocnost"];
		$obch = DB::gr("trader",array("id2"=>$id2));
		if($obch && $obch["name"]) return $obch["name"];
		return User::getFullName($id2);
	}
	public static function getEmails($usr=null){
	  $ret = array();
	  if(!$usr) $usr = Login::getUserId();
	  $row=DB::gr("users",array("id2"=>$usr));
	  if($row) $ret[$row["email"]] = $row["email"];
	  $row=DB::gr("outer_user_access",array("id2"=>$usr));
	  if($row) $ret[$row["email"]] = $row["email"];
	  $row=DB::gr("trader",array("id2"=>$usr));
	  if($row) $ret[$row["email"]] = $row["email"];
	  if(!$row) return $ret;
	  $ret[$row["email"]] = $row["email"];
	  $res = DB::g("users_emails",array("users"=>$usr));
	  while($row=DB::f($res)){
		$ret[$row["email"]] = $row["email"];
	  }
	  return $ret;
	}
	public static function getEmail($id2=null){
		$usr = User::get($id2);
		return @$usr["email"];
	}
	public static function getName($id2){
		$row = User::get($id2);
		return $row["login"];
	}
	public static function getUserSetting($id2,$name){
		$row = DB::gr("users_settings",array("users"=>$id2,"name"=>$name));
		return $row["value"];
	}
	public static function setUserSetting($id2,$name,$value){
		return DB::u("users_settings",md5("u-$id2-$name"),array("users"=>$id2,"name"=>$name,"value"=>$value));
	}
	public static function getUserF1LimitCelkHodnotaTermKontr($id2){
		return User::getUserSetting($id2,"F1Limit1");
	}
	public static function getUserF1LimitMaxStrata($id2){
		return User::getUserSetting($id2,"F1Limit2");
	}
	public static function getUserF1LimitCas($id2){
		return User::getUserSetting($id2,"F1LimitCas");
	}
	public static function getUserDPHMultiplikator($id2){
		$val = User::getUserDPH($id2);
		return ($val+100)/100;
	}
	public static function getUserDPH($id2){
		$val = User::getUserSetting($id2,"DPH");
		if($val === false || $val === null) return VAT::getDPH();
		if(!$val) return "0";
		return $val;
	}
	public static function isInGroup($user,$group){
		require_once("modules/Group.php");
		return Group::userInGroup($user,$group);
	}
	public static function isCarbonTrader($user = null){
		if($user == null){require_once("modules/Login.php");$user = Login::getUserId();}
		$row = DB::gr("obchodnik_eua",array("users"=>$user));
		if(!$row) return false;
		return true;
	}
	public static function listContactInformationHtml($user,$lang="sk",$clean=false){
		$ret = '<div><table class="contactdetails">';
		$row = DB::gr("pohoda_contacts",array("users"=>$user));
		if(!$row){
		 $ret.='<tr><td>Person does not have filled in data</td></tr>';
		}
		if($clean)
		foreach($row as $k=>$v){
		 $row[$k] = "█████████████████████";
		}
		if($row[$id="company"]) 	$ret .= '<tr><td>'.Language::get($id).':</td><td>'.$row[$id].'</td></tr>';
		if($row[$id="division"]) 	$ret .= '<tr><td>'.Language::get($id).':</td><td>'.$row[$id].'</td></tr>';
		if($row[$id="name"]) 	$ret .= '<tr><td>'.Language::get($id).':</td><td>'.$row[$id].'</td></tr>';
		if($row[$id="street"]) 	$ret .= '<tr><td>'.Language::get($id).':</td><td>'.$row[$id].'</td></tr>';
		if($row[$id="zip"]) 	$ret .= '<tr><td>'.Language::get($id).':</td><td>'.$row[$id].'</td></tr>';
		if($row[$id="city"]) 	$ret .= '<tr><td>'.Language::get($id).':</td><td>'.$row[$id].'</td></tr>';
		if($row[$id="state"]) 	$ret .= '<tr><td>'.Language::get($id).':</td><td>'.$row[$id].'</td></tr>';
		if($row[$id="ico"]) 	$ret .= '<tr><td>'.Language::get($id).':</td><td>'.$row[$id].'</td></tr>';
		if($row[$id="dic"]) 	$ret .= '<tr><td>'.Language::get($id).':</td><td>'.$row[$id].'</td></tr>';
		if($row[$id="web"]) 	$ret .= '<tr><td>'.Language::get($id).':</td><td>'.$row[$id].'</td></tr>';
		if($row[$id="email"]) 	$ret .= '<tr><td>'.Language::get($id).':</td><td>'.$row[$id].'</td></tr>';
		if($row[$id="tel"]) 	$ret .= '<tr><td>'.Language::get($id).':</td><td>'.$row[$id].'</td></tr>';
		if($row[$id="fax"]) 	$ret .= '<tr><td>'.Language::get($id).':</td><td>'.$row[$id].'</td></tr>';
		$ret.= '</table></div>';
		return $ret;
	}
	public static function getTel($user){
		$row = DB::gr("pohoda_contacts",array("users"=>$user));
		if($row) return $row["tel"];
		return null;
	}
	public static function getLang($user=null){
		if(!$user){require_once("modules/Login.php"); $user = Login::getUserId();}
		$row = DB::gr("users_adv_settings",array("users"=>$user));
		if($row && @$row["language"]) return $row["language"];
		
		$row = DB::gr("trader",array("id2"=>$user));
		if($row) return $row["language"];
		
		$row = DB::gr("users_settings_advanced",array("users"=>$user));
		if($row) return $row["language"];

		
		require_once("modules/Language.php");
		return Language::getLang();
	}
	public static function getAddress($usr=null,$row=null){
		if(!$usr) {require_once("modules/Login.php");$usr = Login::getUserId();}
		if(!$row) $row=DB::gr("pohoda_contacts",array("users"=>$usr));
		
		$ret = "";
		if(isset($row["street"]) && $row["street"]) $ret.=$row["street"];
		if($ret) $ret.=", ";
		if(isset($row["zip"]) && $row["zip"]) $ret.=$row["zip"];
		if($ret) $ret.=" ";
		if(isset($row["city"]) && $row["city"]) $ret.=$row["city"];
		if($ret) $ret.=", ";
		if(isset($row["state"]) && $row["state"]) $ret.=$row["state"];
		
		return $ret;
	}
	public static function getTexts($params=array()){
		$ret= array();
		if(!$params["entity"]) return $ret;
		$usr = User::get($params["entity"]);
		foreach($usr as $k=>$v){
			$ret["%u.$k%"] = $v;
		}
		
		$row = DB::qbr("users_settings_advanced",array("where"=>array("users"=>$params["entity"])));
		$files = array("kyc","orsr","demrequest","podpisovyvzorkonatel","podpisovyvzorobchodnik","opkonatela","opobchodnika","demdelegdoc","accountsstatement","monagreement");
		foreach($files as $file){
			if(isset($row[$file])){
				$filerow = DB::qbr("files",array("where"=>array("id2"=>$file)));
				if($filerow){
					$ret["%u.$file.a%"] = '<a href="'.$filerow["path"].'">'.$filerow["name"].'</a>';
				}else{
					$ret["%u.$file.a%"] = "";
				}
			}else{
				 $ret["%u.$file.a%"] = "";
			}
		}
		return $ret;
	}
}
?>