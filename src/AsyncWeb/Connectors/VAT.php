<?php
namespace AsyncWeb\Connectors;
use AsyncWeb\DB\DB;
use AsyncWeb\Date\Time;
use AsyncWeb\Objects\User;
use AsyncWeb\Connectors\Page;

define("VAT_RATE_SUPER_REDUCED","srr");
define("VAT_RATE_REDUCED","rr");
define("VAT_RATE_REDUCED2","rr2");
define("VAT_RATE_STANDARD","standard");
define("VAT_RATE_PARKING","parking");


class VAT{
	
	public static $DOMESTIC_ZONE = "UK";
	public static $VAT_CHECK_TABLE = "vat_verification";
	public static function getVATCountry($vat=null){
		return substr(strtoupper($vat),0,2);
	}
	public static function verify($vat){
		if($vat === null) return true;
		$row = DB::gr(VAT::$VAT_CHECK_TABLE,array("vat"=>$vat));
		if($row){
			if($row["result"] == "1") return true;
			return $row["result"];
		}
		$country = substr($vat,0,2);
		$num = substr($vat,2);
		
		$text = Page::get($q="http://ec.europa.eu/taxation_customs/vies/viesquer.do?BtnSubmitVat=Verify&ms=$country&iso=$country&vat=$num");
		if(!$text) return "Error checking VAT: ".curl_error($ch);
		if(!strpos($text,"invalidStyle")) {
			DB::u(VAT::$VAT_CHECK_TABLE,md5($vat),array("vat"=>$vat,"result"=>"1"));return true;
		}else{
			if(strpos($text,"invalidStyle")) {DB::u(VAT::$VAT_CHECK_TABLE,md5($vat),array("vat"=>$vat,"result"=>"VAT not listed in VIES - VAT Information Exchange System"));return "VAT not listed in VIES - VAT Information Exchange System";}
		}
		
	}
	public static function getVATMult($type=VAT_RATE_STANDARD,$time=null){
		$curtime = Time::get();
		if($time) $curtime=$time;
		
		if(Time::get(strtotime("2011-01-01")) < $curtime && VAT::$DOMESTIC_ZONE == "SK") return 1.20;
		
		$rates=array(
			VAT_RATE_SUPER_REDUCED=>array(
				"ES"=>1.04,
				"FR"=>1.021,
				"IE"=>1.048,
				"IT"=>1.04,
				"LU"=>1.03,),
			VAT_RATE_REDUCED=>array(
				"BE"=>1.06,
				"BG"=>1.09,
				"CZ"=>1.10,
				"DE"=>1.07,
				"EE"=>1.09,
				"EL"=>1.065,
				"ES"=>1.1,
				"FR"=>1.055,
				"HR"=>1.05,
				"IE"=>1.09,
				"IT"=>1.1,
				"CY"=>1.05,
				"LV"=>1.12,
				"LT"=>1.05,
				"LU"=>1.08,
				"HU"=>1.05,
				"MT"=>1.05,
				"NL"=>1.06,
				"AT"=>1.1,
				"PL"=>1.05,
				"PT"=>1.06,
				"RO"=>1.05,
				"SI"=>1.095,
				"SK"=>1.1,
				"FI"=>1.1,
				"SE"=>1.06,
				"UK"=>1.05,
						 
			),
			VAT_RATE_REDUCED2=>array(
				"BE"=>1.12,
				"CZ"=>1.15,
				"EL"=>1.13,
				"FR"=>1.1,
				"HR"=>1.13,
				"IE"=>1.135,
				"CY"=>1.09,
				"LT"=>1.09,
				"HU"=>1.18,
				"MT"=>1.07,
				"PL"=>1.08,
				"PT"=>1.13,
				"RO"=>1.09,
				"FI"=>1.14,
				"SE"=>1.12,
						 
			),
			VAT_RATE_STANDARD=>array(
				"BE"=>1.21,
				"BG"=>1.20,
				"CZ"=>1.21,
				"DK"=>1.25,
				"DE"=>1.19,
				"EE"=>1.20,
				"EL"=>1.23,
				"ES"=>1.21,
				"FR"=>1.20,
				"HR"=>1.25,
				"IE"=>1.23,
				"IT"=>1.22,
				"CY"=>1.19,
				"LV"=>1.21,
				"LT"=>1.21,
				"LU"=>1.17,
				"HU"=>1.27,
				"MT"=>1.18,
				"NL"=>1.21,
				"AT"=>1.20,
				"PL"=>1.23,
				"PT"=>1.23,
				"RO"=>1.24,
				"SI"=>1.22,
				"SK"=>1.20,
				"FI"=>1.24,
				"SE"=>1.25,
				"UK"=>1.20,
						 
			),
			VAT_RATE_PARKING=>array(
				"BE"=>1.12,
				"IE"=>1.135,
				"LU"=>1.14,
				"AT"=>1.12,
				"PL"=>1.13,
			),
		);
		
		if(isset($rates[$type][VAT::$DOMESTIC_ZONE])) return $rates[$type][VAT::$DOMESTIC_ZONE];
	}
	public static function getVAT($type=VAT_RATE_STANDARD,$time=null){
		return (VAT::getVATMult($type,$time)-1)*100;
	}
	public static function getVATMultUserToUser($usr1,$usr2=false,$type=VAT_RATE_STANDARD,$time=null){
		if(User::getDPH($usr1) == "f") return 1;
		if($usr2) if(User::getDPH($usr2) == "f") return 1;
		return VAT::getVATMult($type,$time);
	}
}