<?php
namespace AsyncWeb\Frontend;

class SetupSettings{
	public static function show(){
		$err = '';
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
		<link href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet">
		<script src="http://code.jquery.com/jquery-latest.min.js" type="text/javascript"></script>
        <script async type="text/javascript" src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
        <link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css" rel="stylesheet">
	</head>
	<body>
		<div class="container">
			<div class="panel panel-primary">
				<div class="penel-heading">Setup your website</div>
				<div class="panel-body">';
					if($err) echo '<div class="alert alert-danger">'.$err.'</div>';
					echo '<form action="/?setup=1">
						<div>
							<label for="pathblocks">Path to blocks</label>
							<input class="form-control" value="../blocks" name="blockspath" id="blockspath" />
						</div>
						<div>
							<label for="templatespath">Path to templates</label>
							<input class="form-control" value="../templates" name="templatespath" id="templatespath" />
						</div>
						<div>
							<label for="dbtype">DB type</label>
							<select id="dbtype"><option value="mysql">MySQL</option><option value="oracle">Oracle</option><option value="postgresql">PostgreSQL</option></select>
						</div>
						<div>
							<label for="dbserver">DB server</label>
							<input class="form-control" value="localhost" name="dbserver" id="dbserver" />
						</div>
						<div>
							<label for="dbuser">DB user</label>
							<input class="form-control" name="dbuser" id="dbuser" />
						</div>
						<div>
							<label for="dbpass">DB password</label>
							<input class="form-control" type="password" name="dbpass" id="dbpass" />
						</div>
						<div>
							<label for="dbdb">Database name</label>
							<input class="form-control" type="password" name="dbdb" id="dbdb" />
						</div>
						<div>
							<input class="form-control" type="submit" value="Check configuration" />
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

