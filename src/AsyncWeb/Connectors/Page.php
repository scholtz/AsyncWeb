<?php
namespace AsyncWeb\Connectors;
use AsyncWeb\DB\DB;
use AsyncWeb\Date\Time;
use AsyncWeb\Connectors\MyCurl;
use AsyncWeb\Cache\Cache;
use AsyncWeb\Text\Texts;
class Page {
    public static $debug = false;
    public static $cookieFile = "cookies.txt";
    public static $ua = "Mozilla/5.0 (Windows; U; Windows NT 5.1; sk; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 (.NET CLR 3.5.30729)";
    public static $info = null;
    /**
        If $path has not been yet downloaded or if etag validation does not match the data, then execute download and store to $table
        
        Returns true, if file has been just downloaded, false if etag validation succeed
    */
    public static function downloadWithEtag($path,$table, $largeFileName = false, $showText = false, $append = []){

        $oldrow = DB::qbr($table,["where"=>["id2"=>md5($path)],"cols"=>["headers"]]);
        $oldheaders = [];
        if($oldrow && $oldrow["headers"]){
            foreach(explode("\n",$oldrow["headers"]) as $row){
                if(($pos=strpos($row,":")) !== false){
                    $oldheaders[Texts::clear(substr($row,0,$pos))] = trim(substr($row,$pos+1));
                }
            }
        }

        if(isset($oldheaders["etag"])){
            $headers = [];
            $headersstr = Page::get($path,"","1",[
                CURLOPT_RETURNTRANSFER=>true,
                CURLOPT_NOBODY=>true,
                CURLOPT_FILETIME=>true,
                CURLOPT_HEADER=>true,
                CURLOPT_ENCODING=>"gzip"
                ]);
                
            foreach(explode("\n",$headersstr) as $row){
                if(($pos=strpos($row,":")) !== false){
                    $headers[Texts::clear(substr($row,0,$pos))] = trim(substr($row,$pos+1));
                }
            }
        }
        
        if(!isset($oldheaders["etag"]) || $oldheaders["etag"] != $headers["etag"]){
            if($showText) echo "New etag for $path: ".$headers["etag"]."\n";
            
            if($showText) echo "downloading..";
            $text = Page::get($path,"","1",[
                CURLOPT_FILETIME=>true,
                CURLOPT_HEADER=>true,
                CURLOPT_ENCODING=>"gzip"
            ]);
            $err = Page::$lastCurlError;
            if($showText) echo date("c")." saving ";
            $headers = "";
            MyCurl::divideHeaders($text,$headers,true);
            if($largeFileName && strlen($text) >= 100000000){
                if($showText) echo " to $largeFileName ";
                file_put_contents($largeFileName,$text);
                $text = "file://$largeFileName";
            }else{
                if($showText) echo " to DB ";
            }
            Page::save($path,$text,$table,$headers,Page::$info, $err, $append);
            if($showText) echo " saved ".date("c")."\n";
            return true;
        }else{
            
            if($showText) echo "$path has not been changed\n";
            
            $id2 = md5($path);
            $col = "checked";
            $row = DB::u($table, array("id2" => $id2), [$col=>time()], false,false,false);
            return false;
        }
        
    }
    public static function headers2array($headersstr){
        $headers = [];
        foreach(explode("\n",$headersstr) as $row){
            if(($pos=strpos($row,":")) !== false){
                $headers[Texts::clear(substr($row,0,$pos))] = trim(substr($row,$pos+1));
            }
        }
        return $headers;
    }
    public static function get($page, $post = "", $showHeaders = "1", $curlparams = array()) {
        $n = 1;
        $i = 0;
        while ($n > 0) {
            $i++;
            $ch = curl_init($page);
            if (Page::$debug) echo (curl_error($ch));
            curl_setopt($ch, CURLOPT_HEADER, $showHeaders);
            if (Page::$debug) echo (curl_error($ch));
            $p = "";
            if ($post) {
                if (is_array($post)) {
                    foreach ($post as $key => $value) {
                        $p.= $key . '=' . urlencode($value) . '&';
                    }
                    rtrim($p, '&');
                    $post = $p;
                }
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            if (substr($page, 0, 5) == "https") {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            }
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_COOKIEJAR, Page::$cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, Page::$cookieFile);
            curl_setopt($ch, CURLOPT_ENCODING, "gzip");
            $header = array("User-Agent: " . Page::$ua, 'Accept: application/json, text/javascript, */*', "Accept-Language: sk,cs;q=0.8,en-us;q=0.5,en;q=0.3",
            //				"Accept-Encoding: deflate,gzip",
            "Content-Type: application/x-www-form-urlencoded", "Accept-Charset: utf-8", "Keep-Alive: 300", "Connection: keep-alive", "Cache-Control: max-age=0");
            if ($curlparams && isset($curlparams[CURLOPT_HTTPHEADER])) {
                foreach ($curlparams[CURLOPT_HTTPHEADER] as $k => $v) {
                    foreach ($header as $kk => $row) {
                        if (substr($row, 0, strlen($k) + 1) == $k . ":") unset($header[$kk]);
                    }
                    $header[] = "$k: $v";
                }
                unset($curlparams[CURLOPT_HTTPHEADER]);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            if ($curlparams) curl_setopt_array($ch, $curlparams);
            Page::$lastCurlError = false;
            $output = curl_exec($ch);
            Page::$lastCurlError = curl_error($ch);
            if (Page::$debug) echo (curl_error($ch));
            $n = curl_errno($ch);
            Page::$info = curl_getinfo($ch);
            curl_close($ch);
            usleep(50000);
            if (Page::$debug) file_put_contents("test08.html", $output);
            if (strpos($output, "Location: /main.aspx?SessionLost=true")) {
                $n = 1;
                if (Page::$debug) echo "session timeout\n";
                if ($i > 3) return null;
            }
        }
        return $output;
    }
    public static $lastCurlError = false;
    
    private static $usedtables = array();
    public static function save($path, &$data, $table, $headers = "", $chinfo = false, $err = "", $append = []) {
        $id2 = md5($path);
        $oldmd5 = Page::load($path, $table, true);
        $config = array("cols" => array("data" => array("type" => "blob", "binary" => true), "headers" => array("type" => "text"), "info" => array("type" => "text"),));
        /*if(isset(Page::$usedtables[$table])){
        $config = false;
        }else{
        Page::$usedtables[$table] = true;
        }/**/
        $save = array();
        if($append){
            foreach($append as $k=>$v){
                $save[$k] = $v;
                if(!isset($config["keys"])){
                    $config["keys"] = [];
                }
                if(!in_array($k,$config["keys"])){
                    $config["keys"][] = $k;
                }
                if(is_int($v)){
                    $config["cols"][$k]["type"] = int;
                }
            }
        }
        
        if ($headers === true) {
            $head = "";
            MyCurl::divideHeaders($data, $head);
            if ($head) $save["headers"] = $head;
        } else {
            $headers = trim($headers);
            if ($headers) $save["headers"] = $headers;
        }
        $md5 = md5($data);
        if ($md5 == $oldmd5) {
            $save = array();
            $save["checked"] = Time::get();
            DB::u($table, $id2, $save, false, $insert_new = false, $useOdDoSystem = false);
            echo DB::error();
        } else {
            $save["md5"] = $md5;
            $save["web"] = $path;
            $save["time"] = Time::get();
            if ($chinfo) {
                $info = "";
                foreach ($chinfo as $k => $v) {
                    if (is_array($v)) {
                        foreach ($v as $k2 => $v2) {
                            $info.= $k . ": " . $k2 . ": " . $v2 . "\n";
                        }
                    } else {
                        $info.= $k . ": " . $v . "\n";
                    }
                }
                if ($info) $save["info"] = $info;
            }
            if ($err) {
                $save["err"] = $err;
            }
            $save["checked"] = Time::get();
            $save["data"] = gzcompress($data, 9);
            DB::u($table, md5($path), $save, $config);
            echo DB::error();
        }
    }
    public static function getLastTime($path, $table) {
        $id2 = md5($path);
        $col = "checked";
        $row = DB::gr($table, array("id2" => $id2), array(), array($col => $col));
        //echo DB::error();
        if (!$row) return null;
        return $row[$col];
    }
    public static function load($path, $table, $returnMD5 = false) {
        $id2 = md5($path);
        $col = "data";
        if ($returnMD5) $col = "md5";
        $row = DB::gr($table, array("id2" => $id2), array(), array($col => $col));
        if (!$row) return null;
        if ($returnMD5) return $row[$col];
        if(strpos($row[$col],"file://") === 0){
            $content = file_get_contents(substr($row[$col],7));
        }else{
            $content = gzuncompress($row[$col]);
        }
        if(strpos($content,"file://") === 0){
            $content = file_get_contents(substr($content,7));
        }
        if(substr($content,0,5) == "HTTP/"){
            MyCurl::divideHeaders($content,$headers,true);
        }
        return $content;
    }
    /**
     * Resolve a URL relative to a base path. This happens to work with POSIX
     * filenames as well. This is based on RFC 2396 section 5.2.
     */
    function resolve_url($base, $url) {
        if (!strlen($base)) return $url;
        // Step 2
        if (!strlen($url)) return $base;
        // Step 3
        if (preg_match('!^[a-z]+:!i', $url)) return $url;
        $base = parse_url($base);
        if ($url{0} == "#") {
            // Step 2 (fragment)
            $base['fragment'] = substr($url, 1);
            return Page::unparse_url($base);
        }
        unset($base['fragment']);
        unset($base['query']);
        if (substr($url, 0, 2) == "//") {
            // Step 4
            return Page::unparse_url(array('scheme' => $base['scheme'], 'path' => $url,));
        } else if ($url{0} == "/") {
            // Step 5
            $base['path'] = $url;
        } else {
            // Step 6
            $path = explode('/', $base['path']);
            $url_path = explode('/', $url);
            // Step 6a: drop file from base
            array_pop($path);
            // Step 6b, 6c, 6e: append url while removing "." and ".." from
            // the directory portion
            $end = array_pop($url_path);
            foreach ($url_path as $segment) {
                if ($segment == '.') {
                    // skip
                    
                } else if ($segment == '..' && $path && $path[sizeof($path) - 1] != '..') {
                    array_pop($path);
                } else {
                    $path[] = $segment;
                }
            }
            // Step 6d, 6f: remove "." and ".." from file portion
            if ($end == '.') {
                $path[] = '';
            } else if ($end == '..' && $path && $path[sizeof($path) - 1] != '..') {
                $path[sizeof($path) - 1] = '';
            } else {
                $path[] = $end;
            }
            // Step 6h
            $base['path'] = join('/', $path);
        }
        // Step 7
        return Page::unparse_url($base);
    }
    function unparse_url($parts_arr) {
        if (strcmp(@$parts_arr['scheme'], '') != 0) {
            $ret_url = $parts_arr['scheme'] . '://';
        }
        $ret_url.= @$parts_arr['user'];
        if (strcmp(@$parts_arr['pass'], '') != 0) {
            $ret_url.= ':' . $parts_arr['pass'];
        }
        if ((strcmp(@$parts_arr['user'], '') != 0) || (strcmp(@$parts_arr['pass'], '') != 0)) {
            $ret_url.= '@';
        }
        $ret_url.= @$parts_arr['host'];
        if (strcmp(@$parts_arr['port'], '') != 0) {
            $ret_url.= ':' . $parts_arr['port'];
        }
        $ret_url.= @$parts_arr['path'];
        if (strcmp(@$parts_arr['query'], '') != 0) {
            $ret_url.= '?' . $parts_arr['query'];
        }
        if (strcmp(@$parts_arr['fragment'], '') != 0) {
            $ret_url.= '#' . $parts_arr['fragment'];
        }
        return $ret_url;
    }
    public static function finish($cache = false) {
        $out1 = ob_get_contents();
        ob_end_clean();
        ob_start("ob_gzhandler");
        $etag = md5($out1);
        header("ETag: $etag");
        echo $out1;
        if ($cache > 0) {
            $res = Cache::save($_SERVER["REQUEST_URI"], $out1, $cache);
        }
        exit;
    }
}
