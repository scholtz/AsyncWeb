<?php

namespace AsyncWeb\CLI;

class CLI{
	protected static $namespaces = array("\\AsyncWeb\\CLI\\Command"=>1000);
	/**
		@namespace .. namespace it will search command for
		@priority .. higher number has higher priority
	*/
	public static function registerNamespace($namespace,$priority=0){
		CLI::$namespaces[$namespace] = $priority;
		asort(CLI::$namespaces);
	}
	public static function check(){
		global $argv;
		if(isset($argv[1])){
			if($argv[1] == "help"){
				if(isset($argv[2]) && $argv[2]){
					foreach(CLI::$namespaces as $nm=>$v){
						$cl = $nm."\\".$argv[2];
						if(\class_exists($cl) && class_implements($cl,"\\AsyncWeb\\CLI\\Command")){
							try{
								$obj = new $cl;
								echo $obj->help()."\n";
								
							}catch(\Exception $exc){
								echo \AsyncWeb\System\Language::get("Fatal error: %error%",array("%error%"=>$exc->getMessage()))."\n";
							}
							exit;
						}
					}
					
					echo \AsyncWeb\System\Language::get("Command %command% not found",array("%command%"=>$argv[2]))."\n";
					exit;
				}else{
					echo \AsyncWeb\System\Language::get("You did not set any command to get help for")."\n";
					exit;
				}
			}else{
				foreach(CLI::$namespaces as $nm=>$v){
					$cl = $nm."\\".$argv[1];
					if(\class_exists($cl) && class_implements($cl,"\\AsyncWeb\\CLI\\Command")){
						try{
							$obj = new $cl;
							$obj->execute();
						}catch(\Exception $exc){
							echo \AsyncWeb\System\Language::get("Fatal error: %error%",array("%error%"=>$exc->getMessage()))."\n";
						}
						exit;
					}
				}
				
				
				echo \AsyncWeb\System\Language::get("Command %command% not found",array("%command%"=>$argv[1]))."\n";
				exit;
			}
		}
		
		echo "List of available classes:\n";
		if(is_dir($path = realpath(__DIR__ . "/../../../../../composer"))){
			if(file_exists($f = $path.'/autoload_namespaces.php')){
				$map = require $f;
				foreach(CLI::$namespaces as $nm=>$pr){
					$nma = explode("\\",$nm);
					if(isset($map[$nma[1]])){
						foreach($map[$nma[1]] as $dir){
							$nm2 = \str_replace("\\","/",$nm);
							$cmddir = $dir.$nm2;
							if(is_dir($cmddir)){
								foreach(scandir($cmddir) as $filename) {
									if($filename == "." || $filename == "..") continue;
									if(substr($filename,-4) == ".php"){
										$class = substr($filename,0,-4);
										$cl = $nm."\\".$class;
										if(\class_exists($cl) && class_implements($cl,"\\AsyncWeb\\CLI\\Command")){
											echo $class."\n";
										}
									}
								}
							}
						}
					} 
					
				}
			}
        }
		
		
		echo "\n";
		//print_r(\get_declared_classes());
		echo \AsyncWeb\System\Language::get("Please use the command 'php bin/cli.php [COMMAND]'")."\n";
		echo \AsyncWeb\System\Language::get("For more information use the help keyword 'php bin/cli.php help [COMMAND]'")."\n";
		exit;
	}
	// source: http://php.net/manual/en/function.getopt.php
	public static function parseParameters($noopt = array()) {
        $result = array();
        $params = $GLOBALS['argv'];
        // could use getopt() here (since PHP 5.3.0), but it doesn't work relyingly
        reset($params);
        while (list($tmp, $p) = each($params)) {
            if ($p{0} == '-') {
                $pname = substr($p, 1);
                $value = true;
                if ($pname{0} == '-') {
                    // long-opt (--<param>)
                    $pname = substr($pname, 1);
                    if (strpos($p, '=') !== false) {
                        // value specified inline (--<param>=<value>)
                        list($pname, $value) = explode('=', substr($p, 2), 2);
                    }
                }
                // check if next parameter is a descriptor or a value
                $nextparm = current($params);
                if (!in_array($pname, $noopt) && $value === true && $nextparm !== false && $nextparm{0} != '-') list($tmp, $value) = each($params);
                $result[$pname] = $value;
            } else {
                // param doesn't belong to any option
                $result[] = $p;
            }
        }
        return $result;
    }
}