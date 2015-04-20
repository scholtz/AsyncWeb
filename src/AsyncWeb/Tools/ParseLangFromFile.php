<?php
namespace AsyncWeb\Tools;
/**
Usage:

$parser = new \AsyncWeb\Tools\ParseLangFromFile("dir/");
$parser->writePHP("out.php");

*/
class ParseLangFromFile{
	protected $data = array();
	protected $files = array();
	public static $RECURSIVE = true;
	public function __construct($fileOrDir){
		if(is_dir($fileOrDir)){
			$this->parseDir($fileOrDir);
		}elseif(is_file($fileOrDir)){
			$this->parseFile($fileOrDir);
		}else{
			throw new Exception("File or Directory not found!");
		}
	}
	protected function parseDir($dir){
		$d = dir($dir);
		while (false !== ($entry = $d->read())) {
			if($entry == "." || $entry == "..") continue;
			$file = $dir."/".$entry;
			if(is_dir($file) && ParseLangFromFile::$RECURSIVE) {
				$this->parseDir($file);
				continue;
			}
			$this->parseFile($file);
		}
		$d->close();
	}
	
	protected function parseFile($file){
		
		if(substr($file,-4)!= ".php") continue;
		$f = file_get_contents($file);
		$fl = strtolower($f);
		$pos = null;
		$s = 'language::get(';
		$sl = strlen($s);
		while($pos = strpos($fl,$s,$pos)){
			$pos+=$sl+1;
			$sep = substr($fl,$pos-1,1);
			if($sep != "'" && $sep != '"') continue;
			$end = $pos;
			while($end=strpos($fl,$sep,$pos+1)){
				if(substr($fl,$end-1,1) != '\\'){
					break;
				}
			}
			if(!$end) { echo "syntax error! $pos $file\n"; continue;}
			
			$key = substr($f,$pos,$end-$pos);
			$pos = $end;
			$this->data[$key] = true;
			$this->files[$key][$file] = true;
		}
		
		$keys = array("name","nullValue","insert","update","delete","reset");
		foreach($keys as $key){
			$pos = null;
			$s = '"'.$key.'"=>';
			$sl = strlen($s);
			while($pos = strpos($fl,$s,$pos)){
				$pos+=$sl+1;
				$sep = substr($fl,$pos-1,1);
				if($sep != "'" && $sep != '"') continue;
				$end = $pos;
				while($end=strpos($fl,$sep,$pos+1)){
					if(substr($fl,$end-1,1) != '\\'){
						break;
					}
				}
				if(!$end) { echo "syntax error! $pos $file\n"; continue;}
				
				$key = substr($f,$pos,$end-$pos);
				$pos = $end;
				$this->data[$key] = true;
				$this->files[$key][$file] = true;
			}
		}
		
		
	}
	public function writePHP($file,$checkIfAlreadySet=false,$skip=array()){
		$out = '<?php'."\n";
		$n = 0;
		foreach($this->data as $key=>$v){
			if(isset($skip[$key])) continue;
			if($checkIfAlreadySet && \AsyncWeb\System\Language::is_set($key)) continue;
			$out .= '$L[\''.str_replace("'","\\'",$key).'\'] = \''.str_replace("'","\\'",$key).'\';//';
			foreach($this->files[$key] as $f=>$t){
				$out.=$f.";";
			}
			$n++;
			$out.="\n";
		}
		file_put_contents($file,$out);
		return $n;
	}
}