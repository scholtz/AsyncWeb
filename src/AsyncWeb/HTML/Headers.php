<?php
namespace AsyncWeb\HTML;
use AsyncWeb\HTTP\Header;
/**
usage \AsyncWeb\HTML\Headers::add_script("text/javascript","/js/jquery.js");
*/
class Headers{
 public static $headers = array(); 	

 function __construct($showOnlyRequired = false){
  if(!$showOnlyRequired) Headers::$headers[md5("meta__Content-Type")] = array("tag"=>"meta","equiv"=>"Content-Type","content"=>"text/html; charset=UTF-8");
  Header::send("Content-Type: text/html; charset=UTF-8");
  if(!$showOnlyRequired) Headers::$headers[md5("title")] = array("tag"=>"title","value"=>"");
 }
 
 private static $inst;
 
 public static function add_title($title){
  if(($inst = Headers::$inst) == null) $inst = Headers::$inst = new Headers();
  Headers::$headers[md5("title")] = array("tag"=>"title","value"=>$title);
 }
 
 public static function getTitle(){
  return @Headers::$headers[md5("title")]["value"];
 }
 public static function add_meta($equiv,$content){
  Headers::$headers[md5("meta__".$equiv)] = array("tag"=>"meta","equiv"=>$equiv,"content"=>$content);
 }
 public static function get_meta($name){
  return @Headers::$headers[md5("meta__".$name)]["content"];
 }
 public static function add_script($type="text/javascript",$src){
  if($type == "javascript" || !$type){
  	$type = "text/javascript";
  }
  Headers::$headers[md5("script__".$type."__".$src)] = array("tag"=>"script","type"=>$type,"src"=>$src);
 }
 
 public static function add_link($href="",$rel="",$type="",$title="",$media=""){
  $arr = array();
  $arr["tag"]="link";
  
  if($href  !="") $arr["href"]=$href;
  if($rel   !="") $arr["rel"]=$rel;
  if($type  !="") $arr["type"]=$type;
  if($title !="") $arr["title"]=$title;
  if($media !="") $arr["media"]=$media;
  
  Headers::$headers[md5("link__".$type."__".$rel."__".$href)] = $arr;
 }
 public static $showHeadTags = false;
 public static function show($showOnlyRequired = false){

  if(($inst = Headers::$inst) == null) $inst = Headers::$inst = new Headers($showOnlyRequired);
  $ret = "";
  if(Headers::$showHeadTags) $ret .= "<head>\n";
  foreach (Headers::$headers as $header){
   switch($header["tag"]){
   	case 'meta':
		switch(strtolower($header["equiv"])){
			case "content-type":  
				$ret .= '<meta http-equiv="'.$header["equiv"].'" content="'.$header["content"].'" />'."\n";
			break;
			default:
				$ret .= '<meta name="'.$header["equiv"].'" content="'.$header["content"].'" />'."\n";	
		}
   	
   	break;
   	case 'title':
   	 $ret .= '<title>'.$header["value"].'</title>'."\n";
   	break;
   	case 'script':
	 if(!$header["type"]) $header["type"] = "text/javascript";
   	 $ret .= '<script type="'.$header["type"].'" src="'.$header["src"].'"></script>'."\n";
   	break;
   	case 'link':
   	 $ret .= '<link';
   	 if(@$header["rel"]) $ret .= ' rel="'.$header["rel"].'"';
   	 if(@$header["type"]) $ret .= ' type="'.$header["type"].'"';
   	 if(@$header["title"]) $ret .= ' title="'.$header["title"].'"';
   	 if(@$header["href"]) $ret .= ' href="'.$header["href"].'"';
   	 if(@$header["media"]) $ret .= ' media="'.$header["media"].'"';
   	 $ret.= ' />'."\n";
   	break;
   }
  }
  if(Headers::$showHeadTags) $ret.= "</head>\n";
  return $ret;
 }
}