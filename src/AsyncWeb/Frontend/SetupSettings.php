<?php
namespace AsyncWeb\Frontend;

class SetupSettings{
	public static function show(){
		$err = '';
		
		$blockspath = "../blocks/";
		$templatespath = "../templates/";
		$dbtype = "mysql";
		$dbtypes=  array("mysql"=>"MySQL","oracle"=>"Oracle","postgresql"=>"PostgreSQL");
		$dbserver = "localhost";
		$dbuser = "";
		$dbpass = "";
		$dbdb = "";
		
		if(isset($_REQUEST["blockspath"])) $blockspath = $_REQUEST["blockspath"];
		if(isset($_REQUEST["templatespath"])) $templatespath = $_REQUEST["templatespath"];
		if(isset($_REQUEST["dbtype"])) $dbtype = $_REQUEST["dbtype"];
		if(isset($_REQUEST["dbserver"])) $dbserver = $_REQUEST["dbserver"];
		if(isset($_REQUEST["dbuser"])) $dbuser = $_REQUEST["dbuser"];
		if(isset($_REQUEST["dbpass"])) $dbpass = $_REQUEST["dbpass"];
		if(isset($_REQUEST["dbdb"])) $dbdb = $_REQUEST["dbdb"];
		
		
		if(isset($_REQUEST["setup"])){
			if(!is_dir($_REQUEST["blockspath"])){
				$err = "Blocks path does not exists!<br/>";
			}
			if(!is_dir($_REQUEST["templatespath"])){
				$err = "Templates path does not exists!<br/>";
			}
			if(!is_writable(".")){
				$err = "Root path is not writable for installation script!<br/>";
			}
			
			if(is_file("settings.php")){
				$err = "Settings file already exists!<br/>";
			}
			
			if(!$err){
				$file = "";
				$use[] = "\AsyncWeb\Frontend\BlockManagement";
				$use[] = "\AsyncWeb\Frontend\Block";
				$file.= "\AsyncWeb\Frontend\BlockManagement::\$BLOCK_PATH='".$_REQUEST["blockspath"]."';\n";
				$file.= "\AsyncWeb\Frontend\Block::\$TEMPLATES_PATH='".$_REQUEST["templatespath"]."';\n\n#DB setup\n";
				switch($_REQUEST["dbtype"]){
					case "mysql":
						$use[] = "\AsyncWeb\DB\DB";
						$use[] = "\AsyncWeb\DB\MysqlServer";
						$file.= "\AsyncWeb\DB\DB::\$DB_TYPE='\AsyncWeb\DB\MysqlServer';\n";
						$file.= "\AsyncWeb\DB\MysqlServer::\$SERVER='".$_REQUEST["dbserver"]."';\n";
						$file.= "\AsyncWeb\DB\MysqlServer::\$LOGIN='".$_REQUEST["dbuser"]."';\n";
						$file.= "\AsyncWeb\DB\MysqlServer::\$PASS='".$_REQUEST["dbpass"]."';\n";
						$file.= "\AsyncWeb\DB\MysqlServer::\$DB='".$_REQUEST["dbdb"]."';\n";
					break;
				}
				foreach($use as $u) $file = "use $u\n".$file;
				$file = "<?php\n".$file;
				
				file_put_contents("settings.php",$file);
				return;
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
		<div class="container">
			<div class="panel panel-primary">
				<div class="panel-heading">Setup your website</div>
				<div class="panel-body">';
					if($err) echo '<div class="alert alert-danger">'.$err.'</div>';
					echo '<form method="post" action="/?setup=1">
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
							<select class="form-control" id="dbtype">';
							foreach($dbtypes as $k=>$v){echo '<option value="'.$k.'"';if($dbtype==$k) echo ' selected="selected"';echo '>'.$v.'</option>';}
							echo'</select>
						</div>
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

