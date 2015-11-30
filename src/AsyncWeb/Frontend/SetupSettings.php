<?php
namespace AsyncWeb\Frontend;
use AsyncWeb\Frontend\URLParser;

class SetupSettings{
	public static function show(){
		$err = '';
		
		$blockspath = "../blocks/";
		$templatespath = "../templates/";
		$dbtype = "0";
		$dbtypes=  array("0"=>"None","mysql"=>"MySQL","oracle"=>"Oracle","postgresql"=>"PostgreSQL");
		$dbserver = "localhost";
		$dbuser = "";
		$dbpass = "";
		$dbdb = "";
		
		if(URLParser::v("blockspath") !== null) $blockspath = URLParser::v("blockspath");
		if(URLParser::v("templatespath") !== null) $templatespath = URLParser::v("templatespath");
		if(URLParser::v("dbtype") !== null) $dbtype = URLParser::v("dbtype");
		if(URLParser::v("dbserver") !== null) $dbserver = URLParser::v("dbserver");
		if(URLParser::v("dbuser") !== null) $dbuser = URLParser::v("dbuser");
		if(URLParser::v("dbpass") !== null) $dbpass = URLParser::v("dbpass");
		if(URLParser::v("dbdb") !== null) $dbdb = URLParser::v("dbdb");
		if(URLParser::v("googleon") !== null) $googleon = URLParser::v("googleon");
		if(URLParser::v("googleid") !== null) $googleid = URLParser::v("googleid");
		if(URLParser::v("googlesecret") !== null) $googlesecret = URLParser::v("googlesecret");
		$googleonvalue = "";
		if($googleon) $googleonvalue = ' checked="checked"';
		if(URLParser::v("mainmenuon") !== null) $mainmenuon = URLParser::v("mainmenuon");
		$mainmenuonvalue = "";
		if($mainmenuon) $mainmenuonvalue = ' checked="checked"';
		
		var_dump(URLParser::v("dbtype"));exit;
		
		$setup = false;
		if(URLParser::v("setup") !== null) $setup = URLParser::v("setup");
		
		
		if($setup){
			if(!is_dir($blockspath)){
				$err .= "Blocks path does not exists!<br/>";
			}
			if(!is_dir($templatespath)){
				$err .= "Templates path does not exists!<br/>";
			}
			if(!is_writable(".")){
				$err .= "Root path is not writable for installation script!<br/>";
			}
			
			if(is_file("../conf/settings.php")){
				$err .= "Settings file already exists!<br/>";
			}
			if(is_file("settings.php")){
				$err .= "Settings file already exists!<br/>";
			}
			if(!$err){
				$file = "";
				$use[] = "\AsyncWeb\Frontend\BlockManagement";
				$use[] = "\AsyncWeb\Frontend\Block";
				$file.="#templates setup\n";
				$file.= "\AsyncWeb\Frontend\Block::\$BLOCK_PATH='".$blockspath."';\n";
				$file.= "\AsyncWeb\Frontend\Block::\$TEMPLATES_PATH='".$templatespath."';\n\n";
				switch($dbtype){
					case "mysql":
						$file.="#DB setup\n";
						$use[] = "\AsyncWeb\DB\DB";
						$use[] = "\AsyncWeb\DB\MysqlServer";
						try{
							$db = new \AsyncWeb\DB\MysqlServer(false,$dbserver,$dbuser,$dbpass,$dbdb);
						}catch(\Exception $exc){
							$err.=$exc->getMessage()."<br/>\n";
						}
						
						$file.= "\AsyncWeb\DB\DB::\$DB_TYPE='\AsyncWeb\DB\MysqlServer';\n";
						$file.= "\AsyncWeb\DB\MysqlServer::\$SERVER='".$dbserver."';\n";
						$file.= "\AsyncWeb\DB\MysqlServer::\$LOGIN='".$dbuser."';\n";
						$file.= "\AsyncWeb\DB\MysqlServer::\$PASS='".$dbpass."';\n";
						$file.= "\AsyncWeb\DB\MysqlServer::\$DB='".$dbdb."';\n";
					break;
					case "0":
					break;
					default: 
					 $err .= "Database type is not implemented yet!<br/>";
				}
				
				if($mainmenuon){
					$file .= '# Use MainMenu module
\AsyncWeb\Menu\MainMenu::registerBuilder(new \AsyncWeb\Menu\DBMenu5(),-1000);
';
					 
				}
				
				if($googleon){
					$file='
					
					# Google oAuth
					$storage = new \AsyncWeb\Storage\OAuthLibSession();

					$credentials = new OAuth\Common\Consumer\Credentials(
						"'.$googleid.'",
						"'.$googlesecret.'",
						"https://".$_SERVER["HTTP_HOST"]."/go=Google"
					);

					$serviceFactory = new \OAuth\ServiceFactory();
					$serviceFactory->setHttpClient(new OAuth\Common\Http\Client\CurlClient());
					$googleService = $serviceFactory->createService("google", $credentials, $storage, array("userinfo_email", "userinfo_profile"));
					$googleService->setAccessType("offline");

					$oauth = new \AsyncWeb\Security\AuthServicePHPoAuthLib();
					$oauth->registerService("Google",$googleService,"https://www.googleapis.com/oauth2/v1/userinfo");
					\AsyncWeb\Security\Auth::register($oauth);
					';
				}
				foreach($use as $u) $file = "use $u;\n".$file;
				$file = "<?php\n".$file;
				if(!$err){
					$size = file_put_contents("../conf/settings.php",$file);
					if(!$size){
						$err.='I was unable to save the config file!<br/>';
					}
				}
				if(!$err){
					header("Location: /");exit;
					return;
				}
			}
			
		}
		echo '<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8"> 
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<link href="//netdna.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css" rel="stylesheet">
		<script src="http://code.jquery.com/jquery-latest.min.js" type="text/javascript"></script>
        <script async type="text/javascript" src="//netdna.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
        <link href="//netdna.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">
	</head>
	<body>
		<div class="container" style="margin:1em auto">
			<div class="panel panel-primary">
				<div class="panel-heading">Setup your website</div>
				<div class="panel-body">';
					if($err) echo '<div class="alert alert-danger">'.$err.'</div>';
					echo '<form method="post" action="/setup=1">
						<div>
							<label for="pathblocks">Path to blocks</label>
							<input class="form-control" value="'.$blockspath.'" name="blockspath" id="blockspath" />
						</div>
						<div>
							<label for="templatespath">Path to templates</label>
							<input class="form-control" value="'.$templatespath.'" name="templatespath" id="templatespath" />
						</div>
						<div>
							<label for="dbtype">DB type</label>
							<script>
							function dbchange(){
								if($("#dbtype").val() != "0"){
									$("#dbsettings").show();
								}else{
									$("#dbsettings").hide();
								}
							}
							$(function(){dbchange();});
							</script>
							<select onchange="dbchange()" class="form-control" id="dbtype" name="dbtype">';
							foreach($dbtypes as $k=>$v){echo '<option value="'.$k.'"';if($dbtype==$k) echo ' selected="selected"';echo '>'.$v.'</option>';}
							echo'</select>
						</div>
						<div id="dbsettings">
						<div>
							<label for="dbserver">DB server</label>
							<input class="form-control" value="'.$dbserver.'" name="dbserver" id="dbserver" />
						</div>
						<div>
							<label for="dbuser">DB user</label>
							<input class="form-control" value="'.$dbuser.'" name="dbuser" id="dbuser" />
						</div>
						<div>
							<label for="dbpass">DB password</label>
							<input class="form-control" value="'.$dbpass.'" type="password" name="dbpass" id="dbpass" />
						</div>
						<div>
							<label for="dbdb">Database name</label>
							<input class="form-control" value="'.$dbdb.'" name="dbdb" id="dbdb" />
						</div>
						<script>
							function gauthchange(){
								if($("#googleon").prop("checked")){
									$("#oathgoogle").show();
								}else{
									$("#oathgoogle").hide()();
								}
							}
							
							</script>
						<div>
							<label for="mainmenuon">Use MainMenu module</label>
							<input class="" '.$mainmenuonvalue.' name="mainmenuon" id="mainmenuon" type="checkbox" />
						</div>
						<div>
							<label for="googleon">Use Google authentification</label>
							<input onchange="gauthchange()" class="" '.$googleonvalue.' name="googleon" id="googleon" type="checkbox" />
						</div>
						<div id="oathgoogle" style="display:none">
						
						<div>
							<label for="goauthid">Google oAuth2 id</label>
							<input class="form-control" value="'.$goauthid.'" name="goauthid" id="goauthid" />
						</div>
						<div>
							<label for="goauthsecret">Google oAuth2 secret</label>
							<input class="form-control" value="'.$goauthsecret.'" name="goauthsecret" id="goauthsecret" />
						</div>
						
						
						</div><!-- /oathgoogle -->
						
						
						
						
						</div><!-- /dbsettings -->
						<div><br/>
							<input class="form-control col-md-2 btn btn-primary" type="submit" value="Check and setup configuration" />
						</div>
					</form>
				</div>
			</div>
		<h1>
	</body>
</html>';
		exit;
	}
}

