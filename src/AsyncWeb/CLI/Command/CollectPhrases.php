<?php
namespace AsyncWeb\CLI\Command;
class CollectPhrases implements \AsyncWeb\CLI\Command {
    public static $DEBUG = false;
    protected function traverse($dir) {
        if (CollectPhrases::$DEBUG) echo $dir . "\n";
        $ret = array();
        $d = dir($dir);
        while (false !== ($entry = $d->read())) {
            if ($entry == "." || $entry == "..") continue;
            $file = $dir . "/" . $entry;
            if (is_dir($file)) {
                if (CollectPhrases::$DEBUG) echo $file . " is dir\n";
                foreach ($this->traverse($file) as $k => $arr1) {
                    foreach ($arr1 as $f => $t) {
                        $ret[$k][$f] = $t;
                    }
                }
                continue;
            }
            if (substr($entry, -5) == ".html") {
                if (CollectPhrases::$DEBUG) echo $entry . "\n";
                $f = file_get_contents($file);
                $fl = strtolower($f);
                $pos = null;
                $s = '{{';
                $sl = strlen($s);
                while ($pos = strpos($fl, $s, $pos)) {
                    if (CollectPhrases::$DEBUG) echo $pos . "\n";
                    $pos+= $sl;
                    $next = substr($fl, $pos, 1);
                    if ($next == "{" || $next == "}" || $next == "#") {
                        $pos++;
                        continue;
                    }
                    $sep = "}}";
                    $end = $pos;
                    while ($end = strpos($fl, $sep, $end + 1)) {
                        if (CollectPhrases::$DEBUG) echo $end . "\n";
                        if (CollectPhrases::$DEBUG) var_dump(substr($fl, $end - 1, 10));
                        if (substr($fl, $end - 1, 1) != '\\') {
                            break;
                        }
                    }
                    if (!$end) {
                        if (CollectPhrases::$DEBUG) echo "syntax error! $pos $file\n";
                        continue;
                    }
                    $key = substr($f, $pos, $end - $pos);
                    var_dump($key);
                    $pos = $end;
                    $ret[$key][$file] = \AsyncWeb\System\Language::get($key, \AsyncWeb\System\Language::$DEFAULT_LANGUAGE);
                }
            }
            if (substr($entry, -4) == ".php") {
                if (CollectPhrases::$DEBUG) echo $entry . "\n";
                $f = file_get_contents($file);
                $fl = strtolower($f);
                $pos = null;
                $s = 'language::get(';
                $sl = strlen($s);
                while ($pos = strpos($fl, $s, $pos)) {
                    if (CollectPhrases::$DEBUG) echo $pos . "\n";
                    $pos+= $sl + 1;
                    $sep = substr($fl, $pos - 1, 1);
                    if ($sep != "'" && $sep != '"') continue;
                    if ($sep == ")") continue;
                    $end = $pos;
                    while ($end = strpos($fl, $sep, $end + 1)) {
                        if (CollectPhrases::$DEBUG) echo $end . "\n";
                        if (CollectPhrases::$DEBUG) var_dump(substr($fl, $pos - 1, 10));
                        if (substr($fl, $end - 1, 1) != '\\') {
                            break;
                        }
                    }
                    if (!$end) {
                        if (CollectPhrases::$DEBUG) echo "syntax error! $pos $file\n";
                        continue;
                    }
                    $key = substr($f, $pos, $end - $pos);
                    if (CollectPhrases::$DEBUG) var_dump($key);
                    $pos = $end;
                    $ret[$key][$file] = \AsyncWeb\System\Language::get($key, \AsyncWeb\System\Language::$DEFAULT_LANGUAGE);
                }
            }
        }
        $d->close();
        return $ret;
    }
    protected function collect($basedir) {
        $ret = $this->traverse($basedir);
        $res = \AsyncWeb\DB\DB::g("dictionary", array("lang" => \AsyncWeb\System\Language::$DEFAULT_LANGUAGE));
        while ($row = \AsyncWeb\DB\DB::f($res)) {
            $ret[$row["key"]]["DB"] = \AsyncWeb\System\Language::get($row["key"], \AsyncWeb\System\Language::$DEFAULT_LANGUAGE);
        }
        return $ret;
    }
    public function execute() {
        global $argv;
        $options = \AsyncWeb\CLI\CLI::parseParameters("output");
        $output = "php://stdout";
        if (isset($options["output"]) && $options["output"]) $output = $options["output"];
        $dir = realpath("./src");
        if (isset($options["dir"]) && $options["dir"]) $dir = realpath($options["dir"]);
        $rewrite = false;
        if (isset($options["rewrite"]) && $options["rewrite"]) $rewrite = $options["rewrite"];
        if (!is_dir($dir)) throw new \Exception(\AsyncWeb\System\Language::get("You have set wrong directory to traverse: %dir%", array("%dir%" => $dir)));
        if (file_exists($output) && !$rewrite) throw new \Exception(\AsyncWeb\System\Language::get("File %file% already exists", array("%file%" => $output)));
        $fp = fopen($output, 'w');
        if (!$fp) throw new \Exception(\AsyncWeb\System\Language::get("We were unable to create file %file%", array("%file%" => $output)));
        echo \AsyncWeb\System\Language::get("Saving to: %file% Traversing dir: %dir%", array("%file%" => $output, "%dir%" => $dir)) . "\n";
        foreach ($this->traverse($dir) as $key => $arr1) {
            foreach ($arr1 as $file => $t) {
                $file = str_replace($dir, '', $file);
                fputcsv($fp, array($key, $t, $file));
            }
        }
        fclose($fp);
        echo \AsyncWeb\System\Language::get("File has been successfully saved: %file%", array("%file%" => $output)) . "\n";
    }
    public function help() {
        echo "Usage: php bin/cli.php CollectPhrases [--dir=DIR] [--rewrite=1] --output=out.csv\n";
    }
}
