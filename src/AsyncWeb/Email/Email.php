<?php
/**

Email::send("sendto@email","subject","message","sendfrom@email",array(),"html");

		// $sign = array("crt"=>"file.crt","pass"=>$pass);
		// $attachment = array( array( "content-type"=> "text/plain", "name"=>"file", "data"=>file_get_contents("x")));


*/

namespace AsyncWeb\Email;
use AsyncWeb\DB\DB;
use AsyncWeb\System\Language;

class Email{
	public static $safedir = "/dev/shm/pcks";
	public static $debuginplace = false;
	public static $defaulttheme = null;
	public static $defaultprepend = null;
	public static $defaultSenderEmail = "";
	
	public static function send(){
		//$to,$subject,$message,$from = "",$attachment=array(),$sendAs='text/plain; charset="utf-8"',$sign=array(),$dbg=false
		$to = "";
		$subject = "";
		$message = "";
		$from = "";
		$attachment=array();
		$sendAs='text/plain; charset="utf-8"';
		$sign=array();
		$dbg=false;
		$theme = "";
		$prepend = "";
		
		$args = func_get_args();
		$i = 0;
		while(($arg = array_shift($args)) !== null){
			if(is_a($arg,"\\AsyncWeb\\Email\\Theme")){
				$theme = $arg->get();
				continue;
			}elseif(is_a($arg,"\\AsyncWeb\\Email\\To")){
				$to = $arg->get();
				continue;
			}elseif(is_a($arg,"\\AsyncWeb\\Email\\Subject")){
				$subject = $arg->get();
				continue;
			}elseif(is_a($arg,"\\AsyncWeb\\Email\\Message")){
				$message = $arg->get();
				continue;
			}elseif(is_a($arg,"\\AsyncWeb\\Email\\From")){
				$from = $arg->get();
				continue;
			}elseif(is_a($arg,"\\AsyncWeb\\Email\\Attachments")){
				$attachment = $arg->get();
				continue;
			}elseif(is_a($arg,"\\AsyncWeb\\Email\\SendAs")){
				$sendAs = $arg->get();
				continue;
			}elseif(is_a($arg,"\\AsyncWeb\\Email\\Sign")){
				$sign = $arg->get();
				continue;
			}elseif(is_a($arg,"\\AsyncWeb\\Email\\Debug")){
				$dbg = $arg->get();
				continue;
			}elseif(is_a($arg,"\\AsyncWeb\\Email\\Prepend")){
				$prepend = $arg->get();
				continue;
			}else{
			
				$i++;
				if($i == 1){
					$to = $arg;
				}elseif($i == 2){
					$subject = $arg;
				}elseif($i == 3){
					$message = $arg;
				}elseif($i == 4){
					$from = $arg;
				}elseif($i == 5){
					$attachment = $arg;
				}elseif($i == 6){
					$sendAs = $arg;
				}elseif($i == 7){
					$sign = $arg;
				}elseif($i == 8){
					$dbg = $arg;
				}
			}
		}
		if(!$prepend) $prepend = Email::$defaultprepend;
		if($prepend) $subject = $prepend.": ".$subject;
		if(!$from) $from = Email::$defaultSenderEmail;
		
		if(!$theme) $theme = Email::$defaulttheme;
		if($dbg){
			echo "Setting theme: $theme\n";
		}
		if($theme && substr($sendAs,0,10)!='text/plain'){
			$row = DB::gr("emailstyles",array("name"=>$theme));
			if($row){
				$template = Language::get($row["text"]);
				$message = \AsyncWeb\Text\Template::loadTemplate($template,array("email"=>$message),$dbg);
				if(File::exists("templates/$text.html")){
					
					if($dbg){
						echo "THEME OK\n";
						echo \AsyncWeb\Frontend\Block::$TEMPLATES_PATH."$text.html\n";
						echo strlen($message)."\n";
					}
				}else{
					if($dbg){
						echo "THEME FILE NOT FOUND\n";
						echo \AsyncWeb\Frontend\Block::$TEMPLATES_PATH."$text.html\n";
					}
				}
				
				
			}else{
				if($dbg){
					echo "THEME NOT FOUND IN DB!\n";
				}
			}
		}
		
		
		$email = $to;
		$emailwname = $email;
		if(is_array($to) && isset($to["email"])){
			$email = $to["email"];
			$emailwname = $email;
			if(isset($to["name"]) && $to["name"]){
				$emailwname = "=?utf-8?B?".base64_encode($to["name"])."?="." <".$to["email"].">";
//				$emailwname = ;
			}
		}
		$fromorig = $fromwname = $from;
		if(is_array($from) && isset($from["email"])){
			$from = $from["email"];
			$fromwname = $from;
			if(isset($fromorig["name"]) && $fromorig["name"]){
				$fromwname = "=?utf-8?B?".base64_encode($fromorig["name"])."?="." <".$fromorig["email"].">";
//				$emailwname = ;
			}
		}
		if($sendAs == "html"){
			$sendAs='text/html; charset="utf-8"';
		}
                
		if(!Email::$debuginplace && @$_SERVER["SystemRoot"] != "C:\\Windows") {
                    $mime_boundary = md5(uniqid());
                    $headers = "";
                    $mes = "";
                    $nl = "\n";
                    if(@$_SERVER["windir"]){
                            $nl = "\r\n";
                    }
                    $headers .= 'MIME-Version: 1.0' . $nl;
                    //$headers .= 'To: '.$emailwname. $nl;
                    if(!$sign) $headers .= 'From: '.$fromwname . $nl;
                    if(!$sign) $headers .= "Return-Path: $from$nl";
                    $headers .= "Content-Type: multipart/mixed; boundary=\"$mime_boundary\"$nl";

                    $mes .= "--$mime_boundary$nl";
                    $mes .= "Content-Type: $sendAs".$nl;
                    $mes .= "Content-Transfer-Encoding: base64".$nl.$nl.chunk_split(base64_encode($message)).$nl;
                    foreach ($attachment as $priloha){
                            $mes .= "--$mime_boundary$nl";
                            $mes .= "Content-Type: ".$priloha["content-type"]."$nl";
                            if($priloha["name"])
                                $mes .= "Content-disposition: attachment; filename=".$priloha["name"].$nl;
                            $mes .= "Content-Transfer-Encoding: base64$nl$nl";
                            $mes .= chunk_split(base64_encode($priloha["data"])).$nl;
                    }
                    $mes .= "--$mime_boundary--$nl$nl";		

                    $matches = array();
                    preg_match('/<(?P<email>.*)>/', $from, $matches);
                    if(@$matches["email"]){
                     $params = "-f".$matches["email"]." -F".$matches["email"];
                    }else{
                     $params = "-f$from -F$from";
                    }


                    if(!\AsyncWeb\Text\Validate::check_input($email,"email")){
                            DB::insert("emails",array("to"=>$emailwname,"subject"=>$subject,"message"=>$message,"from"=>$fromwname,"result"=>false),array("cols"=>array("message"=>array("type"=>"text"))));
                            return false;
                    }

                    if($sign){
                      $boddy .= "--$mime_boundary--$nl";
                            $boddy = $headers.$nl.$mes;

                            if(!is_dir(Email::$safedir)) mkdir("/dev/shm/pcks",0700,true);
                            file_put_contents($in = Email::$safedir."/esi-".md5(uniqid()).".tmp",$boddy);

                            $subject = "=?utf-8?B?".base64_encode($subject)."?=";
                            if(openssl_pkcs7_sign(
                                    $in, 
                                    $out = Email::$safedir."/eso-".md5(uniqid()).".tmp", 
                                    "file://".$sign["crt"], 
                                    array("file://".$sign["crt"], $sign["pass"]), 
                                    array(
                                    //"To"=>$email,
                                    "From"=>$fromwname
                                    //,"Subject"=>$subject
                                    )
                                    ,PKCS7_BINARY
                                    )){//,PKCS7_DETACHED,$sign["pem"]
                                    $data = file_get_contents($out);
                                    //unlink($in);unlink($out);
                                    //var_dump(openssl_pkcs7_verify($out,0));
                                    $parts = explode("\n\n", $data, 2);
                                    $ret = mail($emailwname, $subject, $parts[1], $parts[0],$params);

                                    return $ret;
                            }else{
                                    if($dbg){ echo "Failed to sign data\n";}
                            }
                            unlink($in);
                    }

                    $subject = "=?utf-8?B?".base64_encode($subject)."?=";
                    if(@mail($emailwname,$subject,$mes,$headers,$params)){
                       $ret = true;
                    }elseif(@imap_mail($email,$subject,$message,$headers)){
                       $ret = true;
                    }else{
                            \AsyncWeb\Storage\Log::log("Email","Error occured while sending email : to :: $email",ML__MEDIUM_PRIORITY);
                            \AsyncWeb\Text\Msg::err(Language::get("Email error"));
                            $ret = false;
                    }
                }
                
		DB::insert("emails",array("to"=>$emailwname,"subject"=>$subject,"message"=>$message,"from"=>$from,"result"=>$ret),array("cols"=>array("message"=>array("type"=>"text"))));

		return $ret;
	}

}

?>