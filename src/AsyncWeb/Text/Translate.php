<?php
namespace AsyncWeb\Text;
use AsyncWeb\DB\DB;
use AsyncWeb\Connectors\Page;


class Translate{
	private static $translatech;
	public static function init(){
		$headers = array(
			  "Keep-Alive: 300",
			  "Connection: keep-alive",
			  //"Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
			  );

			Translate::$translatech = curl_init("http://translate.google.com");
			$options = array(
				CURLOPT_HEADER=>0,
				CURLOPT_RETURNTRANSFER=>true,
				CURLOPT_CONNECTTIMEOUT=>10,
				CURLOPT_TIMEOUT=>10,
				CURLOPT_USERAGENT=>"Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)",
				CURLOPT_ENCODING=>"gzip,deflate",
				CURLOPT_COOKIEFILE=>"cookie.txt",
				CURLOPT_COOKIEJAR=>"cookie.txt",
				CURLOPT_SSL_VERIFYPEER => false,
			);
			curl_setopt_array(Translate::$translatech,$options);
			curl_setopt(Translate::$translatech, CURLOPT_HTTPHEADER, $headers);  
	}
	public static $bingappid = null;
	public static function getBing($text,$from,$to,$usecache=true){
	
		$text = str_replace('"',"'",$text);$text = str_replace('[',"",$text);$text = str_replace(']',"",$text);
		 if(strlen($text) > 250) return $text;
		 $id2 = md5($from.$to.$text);
		 if($usecache){
			 $row = DB::qbr("translations-bing",array("where"=>array("id2"=>$id2),"cols"=>array("translation")));
			 if($row) return $row["translation"];
		 }
		 
		if(!Translate::$translatech){
			Translate::init();
		}
		$appId = '"TMyjXH09yChqUWNuBuK3NM6OR99Q02CUxeLXJ33jUrBM*"';
		$appId = '"TW6g0FbU_fssLAQUbFrRkJFY-zy_JDec-91CfqQS09zxIBqImTLndN2gsdpvsM4uh"';
				//"Tim362g6WYNCnMByWKs6Ui4o86884UD0fyawcHrUS9ECp7NOwipmK_CTeI0pxZTAU" 
		$appId = '"ThnDPw99fdcdWVtemom9cAIadKAqILpSr6vhNpwPdNuYbo_PzP9-93t94OkNnUb7t"';
		$appId = '"TGn9jnrGYr2FbXE6YSw5wWgoKcYj4uXH36-A5Fy36k0DVH2LEWVCCyDmvMNiEJkdl"';
		$appId = '"TXL80-UnHTOsR6lGF7epGZ3LjIjwETFme_XZeU4Fm5nDswVOiMOEKSNPM_ZqvEHCs"';
		if(Translate::$bingappid){
			$appId = Translate::$bingappid;
		}else{
			$html = Page::get("http://www.bing.com/translator/");
			//var_dump($text);exit;
			$find = "AjaxApiAppId = '";
			if($pos = strpos($html,$find)){
				$pos+=strlen($find);
				
				$pos2 = strpos($html,"'",$pos+1);
				if($pos2){
					Translate::$bingappid = $appId = substr($html,$pos,$pos2-$pos);
					echo "setting bing appid: $appId\n";
				}
			}
		}
		
		curl_setopt(Translate::$translatech, CURLOPT_URL, 'http://api.microsofttranslator.com/v2/ajax.svc/TranslateArray?appId='.$appId.'&texts=["'.urlencode($text).'"]&from="'.$from.'"&to="'.$to.'"&oncomplete=_mstc2&onerror=_mste2&loc=en&ctr=CzechRepublic&rgp=cce376b');
		
		$data = curl_exec(Translate::$translatech);
		if(($pos = strpos($data,$t='TranslatedText":"'))!==false){
			$start = $pos+strlen($t);
			$pos2 = strpos($data,'"',$start);
			if($pos2 > $start){$l = $pos2-$start;}else{$l=0;}
			if($l > 0){
				$ret= substr($data,$start,$l);
				if($ret) DB::u("translations-bing",$id2,array("from"=>$from,"to"=>$to,"text"=>$text,"translation"=>$ret));
				return $ret;
			}
		}
		return $text;
	}
	public static function getGoogle($text,$from="sk",$to="en",$usecache=true){
				//var_dump($text);exit;

		$text = str_replace('"',"'",$text);
		 if($from==$to) return $text;
		 if(strlen($text) > 250) return $text;
		 
		 $id2 = md5($from.$to.$text);
		 if($usecache){
			$row = DB::qbr("translations-google",array("where"=>array("id2"=>$id2),"cols"=>array("translation")));
			 //$row = DB::gr("translations",array("id2"=>$id2));
			 if($row) return $row["translation"];
		 }
		 
		if(!Translate::$translatech){
			Translate::init();
		}
		
		//curl_setopt(Translate::$translatech, CURLOPT_URL, "http://translate.google.com/translate_a/t?client=t&text=".urlencode($text)."&hl=en&sl=$from&tl=$to&ie=UTF-8&oe=UTF-8&multires=1&prev=btn&ssel=0&tsel=0&sc=1");
		curl_setopt(Translate::$translatech, CURLOPT_URL, $path="https://translate.google.com/translate_a/single?client=t&sl=$from&tl=$to&hl=en&dt=bd&dt=ex&dt=ld&dt=md&dt=qca&dt=rw&dt=rm&dt=ss&dt=t&dt=at&ie=UTF-8&oe=UTF-8&ssel=3&tsel=0&otf=1&kc=5&tk=523093|15087&q=".urlencode($text));
		
		 
		$data = curl_exec(Translate::$translatech);

		$data = str_replace(",,",',"",',$data);
		$data = str_replace(",,",',"",',$data);
		$data = str_replace(",,",',"",',$data);
		$data = str_replace(",,",',"",',$data);
		$data = str_replace(",,",',"",',$data);
		$data = str_replace(",,",',"",',$data);
		$data = str_replace(",,",',"",',$data);
		$data = str_replace(",,",',"",',$data);
		$data = str_replace("[,",'["",',$data);
		$data = str_replace(",]",',""]',$data);
		$t = json_decode($data,true);
		//var_dump($data);
		$trtext = @$t[0][0][0];
		if(!$trtext && is_file("cookie.txt")){
			unlink("cookie.txt");
		}
		if(!$trtext){
			Translate::init();
		}
		if($trtext){
			$ret= DB::u("translations-google",$id2,array("from"=>$from,"to"=>$to,"text"=>$text,"translation"=>$trtext));
			return $trtext;
		}else{
			return $text;
		}
	}
	public static function get($text,$from="sk",$to="en",$usecache=true){
		if($from == $to) return $text;
		$ret = Translate::getBing($text,$from,$to,$usecache);
		if($ret==$text) {
			$ret = Translate::getGoogle($text,$from,$to,$usecache);
			if($ret != $text){
				if(rand(0,100) > 90) Translate::$bingappid = null;
			}
		}
		return $ret;
	}
}