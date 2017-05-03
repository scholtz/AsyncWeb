<?php
namespace AsyncWeb\Frontend;
use AsyncWeb\Frontend\URLParser;
class SetupSettings {
    public static function show() {
        $err = '';
        $namespace = "KBB";
        $templatespath = "../templates/";
        $dbtype = "0";
        $dbtypes = array("0" => "None", "mysql" => "MySQL", "oracle" => "Oracle", "postgresql" => "PostgreSQL");
        $dbserver = "localhost";
        $dbuser = "";
        $dbpass = "";
        $dbdb = "";
        if (URLParser::v("namespace") !== null) $namespace = URLParser::v("namespace");
        //if (URLParser::v("templatespath") !== null) $templatespath = URLParser::v("templatespath");
        if (URLParser::v("dbtype") !== null) $dbtype = URLParser::v("dbtype");
        if (URLParser::v("dbserver") !== null) $dbserver = URLParser::v("dbserver");
        if (URLParser::v("dbuser") !== null) $dbuser = URLParser::v("dbuser");
        if (URLParser::v("dbpass") !== null) $dbpass = URLParser::v("dbpass");
        if (URLParser::v("dbdb") !== null) $dbdb = URLParser::v("dbdb");
        if (URLParser::v("basicauthon") !== null) $basicauthon = URLParser::v("basicauthon");
        if (URLParser::v("googleon") !== null) $googleon = URLParser::v("googleon");
        if (URLParser::v("goauthid") !== null) $goauthid = URLParser::v("goauthid");
        if (URLParser::v("goauthsecret") !== null) $goauthsecret = URLParser::v("goauthsecret");
        $googleonvalue = "";
        if ($googleon) $googleonvalue = ' checked="checked"';
		$basicauthonvalue = "";
		if ($basicauthon) $basicauthonvalue = ' checked="checked"';
		
		
        if (URLParser::v("mainmenuon") !== null) $mainmenuon = URLParser::v("mainmenuon");
        $mainmenuonvalue = "";
        if ($mainmenuon) $mainmenuonvalue = ' checked="checked"';
        $setup = false;
        if (URLParser::v("setup") !== null) $setup = URLParser::v("setup");
        if ($setup) {
			$namespaceSimplePath = "src/$namespace/src";
            if (!is_dir($namespacePath = realpath("..")."/".$namespaceSimplePath)) {
                if (!mkdir($namespacePath,0777,true)) {
                    $err.= "Namespace directory '$namespacePath' does not exists and I am unable to create it!<br/>";
                }
                if (!mkdir($namespacePath."/Template")) {
                    $err.= "I am unable to create templates directory!<br/>";
                }
                if (!mkdir($namespacePath."/Block")) {
                    $err.= "I am unable to create block directory!<br/>";
                }
                if (!mkdir($namespacePath."/i18n")) {
                    $err.= "I am unable to create it translation directory!<br/>";
                }
				
				
            }
            if (isset($_SERVER['APPLICATION_ENV']) && $_SERVER['APPLICATION_ENV']) {
                if (is_file($defaultconf = ($defaultpath = "../conf/" . $_SERVER['APPLICATION_ENV']) . "/settings.php")) {
                    $err.= "Settings file already exists!<br/>";
                }
            } else {
                $defaultpath = "../conf/";
                $defaultconf = $defaultpath . "/settings.php";
            }
            if (!is_dir($defaultpath)) {
                if (!mkdir($defaultpath,0777,true)) {
                    $err.= "Config path does not exists!<br/>";
                }
            }
            if (!is_writable($defaultpath)) {
                $err.= "Config path " . $defaultpath . " is not writable!<br/>";
            }
            if (is_file("../conf/settings.php")) {
                $err.= "Settings file already exists!<br/>";
            }
            if (is_file("settings.php")) {
                $err.= "Settings file already exists!<br/>";
            }
            if (!$err) {
                $file = "";
                $use[] = "\AsyncWeb\Frontend\BlockManagement";
                $use[] = "\AsyncWeb\Frontend\Block";
                $file.= "#templates setup\n";
                //$file.= "\AsyncWeb\Frontend\Block::\$BLOCK_PATH='" . $blockspath . "';\n";
                //$file.= "\AsyncWeb\Frontend\Block::\$TEMPLATES_PATH='" . $templatespath . "';\n\n";
				
				$file.= '\AsyncWeb\Frontend\Block::registerBlockPath("\\\\'.$namespace.'\\\\Block\\\\");'."\n";
				$file.= '\AsyncWeb\Frontend\Block::registerTemplatePath("'.$namespacePath.'/Template");'."\n";
				$file.= '\AsyncWeb\System\Language::registerLangPath("'.$namespacePath.'/i18n");'."\n";
				
				
				$json = json_decode(file_get_contents("../composer.json"),true);
				$json["autoload"]["psr-4"][$namespace."\\"] = $namespaceSimplePath;
				$r = file_put_contents("../composer.json",json_encode($json,JSON_PRETTY_PRINT));
				
                switch ($dbtype) {
                    case "mysql":
                        $file.= "#DB setup\n";
                        try {
                            $db = new \AsyncWeb\DB\MysqliServer(false, $dbserver, $dbuser, $dbpass, $dbdb);
                        }
                        catch(\Exception $exc) {
                            $err.= $exc->getMessage() . "<br/>\n";
                        }
                        $file.= "\AsyncWeb\DB\DB::\$DB_TYPE='\AsyncWeb\DB\MysqliServer';\n";
                        $file.= "\AsyncWeb\DB\MysqliServer::\$SERVER='" . $dbserver . "';\n";
                        $file.= "\AsyncWeb\DB\MysqliServer::\$LOGIN='" . $dbuser . "';\n";
                        $file.= "\AsyncWeb\DB\MysqliServer::\$PASS='" . $dbpass . "';\n";
                        $file.= "\AsyncWeb\DB\MysqliServer::\$DB='" . $dbdb . "';\n";
                    break;
                    case "0":
                    break;
                    default:
                        $err.= "Database type is not implemented yet!<br/>";
                }
				
				
				
                if ($mainmenuon) {
                    $file.= '# Use MainMenu module
\AsyncWeb\Menu\MainMenu::registerBuilder(new \AsyncWeb\Menu\DBMenu5(),-1000);
';
                }
				if($basicauthon){
                    $file.= '
					
# Basic authentification

$authProvider = new \AsyncWeb\Security\AuthServiceBasicUser();
\AsyncWeb\Security\Auth::register($authProvider);
';
				}
                if ($googleon) {
                    $file.= '
					
# Google oAuth
$storage = new \AsyncWeb\Storage\OAuthLibSession();

$credentials = new OAuth\Common\Consumer\Credentials(
	"' . $goauthid . '",
	"' . $goauthsecret . '",
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
                foreach ($use as $u) $file = "use $u;\n" . $file;
                $file = "<?php\n" . $file;
                if (!$err) {
                    $size = file_put_contents($defaultconf, $file);
                    if (!$size) {
                        $err.= 'I was unable to save the config file!<br/>';
                    }
                }
                if (!$err) {
                    header("Location: /");
                    exit;
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
		<script src="//code.jquery.com/jquery-latest.min.js" type="text/javascript"></script>
        <script async type="text/javascript" src="//netdna.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
        <link href="//netdna.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">
	</head>
	<body>
		<div class="container" style="margin:1em auto">
			<div class="panel panel-primary">
				<div class="panel-heading">Setup your website</div>
				<div class="panel-body">';
        if ($err) echo '<div class="alert alert-danger">' . $err . '</div>';
        echo '<form method="post" action="/setup=1">
						<div>
							<label for="pathblocks">Namespace of your project</label>
							<input class="form-control" value="' . $namespace . '" name="namespace" id="namespace" />
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
        foreach ($dbtypes as $k => $v) {
            echo '<option value="' . $k . '"';
            if ($dbtype == $k) echo ' selected="selected"';
            echo '>' . $v . '</option>';
        }
        echo '</select>
						</div>
						<div id="dbsettings">
						<div>
							<label for="dbserver">DB server</label>
							<input class="form-control" value="' . $dbserver . '" name="dbserver" id="dbserver" />
						</div>
						<div>
							<label for="dbuser">DB user</label>
							<input class="form-control" value="' . $dbuser . '" name="dbuser" id="dbuser" />
						</div>
						<div>
							<label for="dbpass">DB password</label>
							<input class="form-control" value="' . $dbpass . '" type="password" name="dbpass" id="dbpass" />
						</div>
						<div>
							<label for="dbdb">Database name</label>
							<input class="form-control" value="' . $dbdb . '" name="dbdb" id="dbdb" />
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
							<input class="" ' . $mainmenuonvalue . ' name="mainmenuon" id="mainmenuon" type="checkbox" />
						</div>
						<div>
							<label for="basicauthon">Use basic authentification</label>
							<input class="" ' . $basicauthonvalue . ' name="basicauthon" id="basicauthon" type="checkbox" />
						</div>
						<div>
							<label for="googleon">Use Google authentification</label>
							<input onchange="gauthchange()" class="" ' . $googleonvalue . ' name="googleon" id="googleon" type="checkbox" />
						</div>
						<div id="oathgoogle" style="display:none">
						
						<div>
							<label for="goauthid">Google oAuth2 id</label>
							<input class="form-control" value="' . $goauthid . '" name="goauthid" id="goauthid" />
						</div>
						<div>
							<label for="goauthsecret">Google oAuth2 secret</label>
							<input class="form-control" value="' . $goauthsecret . '" name="goauthsecret" id="goauthsecret" />
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
