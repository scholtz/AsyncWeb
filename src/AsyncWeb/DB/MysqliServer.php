<?php
namespace AsyncWeb\DB;
use AsyncWeb\DB\DBServer;
use AsyncWeb\Storage\Log;
use AsyncWeb\Date\Time;
use AsyncWeb\DB\DB;
/**
 * MySQLi DB Engine for AsyncWeb
 *
 * @author Ludovit Scholtz
 * @version 1.0.4.20161107
 *
 * 1.0.2 			neuklada si do tohoto suboru heslo.. bere ho z config/db.php
 * 1.0.3.20060312 	error() opravena a nedava do logu ked sa nevyskytne ziadna chyba
 * 1.0.4 			MySQLi support
 *
 * 22.12.2010 .. pridana moznost vkladat vyrazy do podmienky $row = DB::gr("kontrakt",array("mod(`mnozstvo`,1000)"=>"0"),array(),array("sum(mnozstvo)"));
 *               ak vyraz obsahuje `, tak to neuzavrie dokopy
 * 21.2.2013	Bugfix.. ak sa snazi uzivatel aktualizovat zaznam na hodnotu null, uz to funguje
 * 28.4.2013 	modules/Time.php compliant
 * 28.3.2014	self::$showlastquery
 */
class MysqliServer extends \AsyncWeb\DB\DBServer {
    protected $debug = false;
    public static $debug2 = false;
    public static $SERVER = "localhost";
    public static $LOGIN = null;
    public static $PASS = null;
    public static $DB = null;
    public static $PERSISTENT_CONNECTION = true;
    protected $defaultServer = "localhost";
    protected $defaultLogin = "userlogin";
    protected $defaultPass = "userpassword";
    protected $defaultDB = "database";
    protected static $working = false;
    protected $checkIndexes = false;
    protected $link; // link na db
    public static $showlastquery = false;
    protected $lastquery = "";
    public $logqueries = false;
    public $logfile = "";
    protected $tryrepair = true;
    public function __construct($defaultsettings = true, $server = "", $login = "", $pass = "", $db = "") {
        if ($defaultsettings) {
            if (self::$instance) return self::$instance;
            if (!self::$DB && MysqlServer::$DB) {
                self::$SERVER = MysqlServer::$SERVER;
                self::$LOGIN = MysqlServer::$LOGIN;
                self::$PASS = MysqlServer::$PASS;
                self::$DB = MysqlServer::$DB;
                self::$PERSISTENT_CONNECTION = MysqlServer::$PERSISTENT_CONNECTION;
            }
            $this->defaultServer = self::$SERVER;
            $this->defaultLogin = self::$LOGIN;
            $this->defaultPass = self::$PASS;
            $this->defaultDB = self::$DB;
            if ((!$this->defaultLogin || $this->defaultLogin == "userlogin") && \AsyncWeb\IO\File::exists("config/db.php")) { // backward compatibility
                include ("config/db.php");
                $this->defaultServer = $DB_SERVER;
                $this->defaultLogin = $DB_L;
                $this->defaultPass = $DB_P;
                $this->defaultDB = $DB_DB;
            }
            if (self::$PERSISTENT_CONNECTION) {
                if (!function_exists("mysqli_connect")) {
                    $err = "Mysql not configured properly";
                    if (\AsyncWeb\DB\DB::$CONNECTED) $err = \AsyncWeb\System\Language::get($err);
                    throw new \AsyncWeb\Exceptions\FatalException($err);
                }
                if (!$this->link = @mysqli_connect($this->defaultServer, $this->defaultLogin, $this->defaultPass)) {
                    $err = "Failed to connect to the database";
                    if (\AsyncWeb\DB\DB::$CONNECTED) $err = \AsyncWeb\System\Language::get($err);
                    throw new \AsyncWeb\Exceptions\FatalException($err);
                }
            } else {
                if (!function_exists("mysqli_connect")) {
                    $err = "Mysql not configured properly";
                    if (\AsyncWeb\DB\DB::$CONNECTED) $err = \AsyncWeb\System\Language::get($err);
                    throw new \AsyncWeb\Exceptions\FatalException($err);
                }
                if (!$this->link = @mysqli_connect($this->defaultServer, $this->defaultLogin, $this->defaultPass)) {
                    $err = "Failed to connect to the database";
                    if (\AsyncWeb\DB\DB::$CONNECTED) $err = \AsyncWeb\System\Language::get($err);
                    throw new \AsyncWeb\Exceptions\FatalException($err);
                }
            }
        } else {
            $this->defaultServer = $server;
            $this->defaultLogin = $login;
            $this->defaultPass = $pass;
            $this->defaultDB = $db;
            // do not try to do persistent on second connecitons self::$PERSISTENT_CONNECTION
            if (!function_exists("mysqli_connect")) {
                $err = "Mysql not configured properly";
                if (\AsyncWeb\DB\DB::$CONNECTED) $err = \AsyncWeb\System\Language::get($err);
                throw new \AsyncWeb\Exceptions\DBException($err);
            }
            if (!$this->link = @mysqli_connect($this->defaultServer, $this->defaultLogin, $this->defaultPass)) {
                $err = "Failed to connect to the database";
                if (\AsyncWeb\DB\DB::$CONNECTED) $err = \AsyncWeb\System\Language::get($err);
                throw new \AsyncWeb\Exceptions\DBException($err);
            }
        }
        if (!($result = @mysqli_select_db($this->link, $this->defaultDB))) {
            $err = "Failed to select the database";
            if (\AsyncWeb\DB\DB::$CONNECTED) $err = \AsyncWeb\System\Language::get($err);
            throw new \AsyncWeb\Exceptions\DBException($err);
        }
        if (!@mysqli_query($this->link, "SET NAMES utf8")) {
            $err = "Failed to connect to set up charset";
            if (\AsyncWeb\DB\DB::$CONNECTED) $err = \AsyncWeb\System\Language::get($err);
            throw new \AsyncWeb\Exceptions\DBException($err);
        }
        self::$instance = $this;
    }
    /**
     * tato funkcia vrati defaultnu instanciu obj. MysqlServer
     * - pripoji server a vrati pointer na tento objekt
     * ak treba urobit dalsiu instanciu tohoto objektu, so specif. nastaveniami, treba zavolat
     * konstruktor
     *
     * @return MysqlServer Instanciu
     */
    protected static $instance;
    public function setLogFile($logfile) {
        $this->logqueries = true;
        $this->logfile = $logfile;
    }
    public static function getInstance() {
        if (self::$working) throw new \AsyncWeb\Exceptions\FatalException("MYSQL server loop");
        self::$working = true;
        if (self::$instance == NULL) {
            try {
                self::$instance = new MysqlServer();
            }
            catch(Exception $e) {
                throw $e;
            }
        }
        self::$working = false;
        return self::$instance;
    }
    public function query($query, $link = null, $params = array()) {
        if ($link == null) $link = $this->link;
        $this->lastquery = $query;
        if ($this->logqueries) $start = microtime(true);
        $ret = @mysqli_query($link, $query);
        $this->afrows = @mysqli_affected_rows($link);
        if ($this->logqueries) {
            if (!file_exists($logdir = dirname($this->logfile))) mkdir($logdir, 0777, true);
            $res = file_put_contents($this->logfile, "D: " . (number_format(microtime(true) - $start, 4)) . " A: " . $this->afrows . " T:" . date("c") . " Q: " . $query . "\n", FILE_APPEND);
        }
        if ($this->logqueries && $err = @mysqli_error($this->link)) $res = file_put_contents($this->logfile, "ERROR: " . (number_format(microtime(true) - $start, 4)) . " " . $err . "\n", FILE_APPEND);
        return $ret;
    }
    public function fetch_assoc($res) {
        return @mysqli_fetch_assoc($res);
    }
    public function f($res) {
        return @mysqli_fetch_assoc($res);
    }
    public function fetch_array($res) {
        return @mysqli_fetch_array($res);
    }
    public function fetch_object($res) {
        return @mysqli_fetch_object($res);
    }
    public function num_rows($res) {
        return @mysqli_num_rows($res);
    }
    private $afrows = 0;
    public function affected_rows() {
        return $this->afrows;
    }
    public function error($showquery = false) {
        $mes = @mysqli_error($this->link);
        if ($showquery || self::$showlastquery) {
            $mes.= $this->lastquery;
        }
        if (isset($mes) && $mes != "") {
            \AsyncWeb\Storage\Log::log("MysqlServerError", "Bola zobrazena chybova hlaska: " . addslashes($mes), ML__LOW_PRIORITY);
            //var_dump(self::$showlastquery);
            
        }
        return $mes;
    }
    public function fetch_assoc_q($query, $link = null) {
        $res = $this->query($query, $link);
        if (!$res) return false;
        $ret = $this->fetch_assoc($res);
        $this->free($res);
        return $ret;
    }
    public function free($res) {
        mysqli_free_result($res);
    }
    private static function mysqli_escape_mimic($inp) {
        if (is_array($inp)) return array_map(__METHOD__, $inp);
        if (!empty($inp) && is_string($inp)) {
            //"\n", "\r", removed
            return str_replace(array('\\', "\0", "'", '"', "\x1a"), array('\\\\', '\\0', "\\'", '\\"', '\\Z'), $inp);
        }
        return $inp;
    }
    public static function myAddSlashes($string) {
        if (is_array($string)) {
            echo "ARRAY in SQL\n";
            var_dump($string);
            exit;
        }
        if (is_bool($string)) {
            if ($string) return "1";
            return "0";
        }
        if ($string === null) return null;
        if ($string && !is_string($string) && !is_numeric($string) && !is_bool($string)) {
            echo "NOT STRING in SQL\n";
            var_dump($string);
            exit;
        }
        return self::mysqli_escape_mimic(stripslashes($string));
        if (mysqli_real_escape_string(stripslashes($string)) == $string) {
            return $string;
        } else {
            return mysqli_real_escape_string($string);
        }
    }
    public function insert_id() {
        return mysqli_insert_id($this->link);
    }
    /*
    simple update function
    
    updates all colums from data col=>value to new values for identifier id2 = $id
    
    */
    public function updateSimple($table, $id, $data, $conf = array(), $insert_new = false) {
        $where = array();
        $where = $id;
        if ($insert_new) {
            $row = $this->gr($table, $where);
            if (!$insert_new) if (!$row) return false;
            if (!$row) {
                $data["id2"] = $id;
                return $this->insert($table, $data, $conf);
            }
            $where = $id = $this->myAddSlashes($row["id2"]);
        }
        $qupdate = "";
        foreach ($data as $k => $v) {
            if ($qupdate) $qupdate.= ",";
            $k = $this->myAddSlashes($k);
            if (@$config["cols"][$k]["compress"]) {
                $qupdate.= "`$k`=compress('" . $this->myAddSlashes($v) . "')";
            } elseif (@$config["cols"][$k]["type"] == "blob" || @$config["cols"][$k]["binary"] || @$config["cols"]["binary"]) {
                if (!$v) {
                    $qupdate.= "`$k`=''";
                } else {
                    $qupdate.= "`$k`=0x" . bin2hex($v);
                }
            } elseif (strpos($v, "`") !== false) {
                $qupdate.= "`$k`=$v";
            } else {
                $v = $this->myAddSlashes($v);
                $qupdate.= "`$k`='$v'";
            }
        }
        $qwhere = "";
        if (is_array($where)) {
            foreach ($where as $k => $v) {
                if ($qwhere) $qwhere.= " and ";
                $k = $this->myAddSlashes($k);
                if (is_numeric($v)) {
                    $qwhere.= "`$k` = '$v'";
                } elseif ($v === null) {
                    $qwhere.= "`$k` is null";
                } else {
                    $qwhere.= "`$k` = 0x" . bin2hex($v);
                }
            }
        } elseif (is_numeric($where)) {
            $qwhere = "id2 = '$id'";
        } else {
            $qwhere = "id2 = '$id' and do = 0";
        }
        $table = str_replace("`", "", $table);
        if (strpos($table, ".") !== false) {
            $ta = explode(".", $table);
            $t = "`" . self::myAddSlashes($ta[0]) . "`.`" . self::myAddSlashes($ta[1]) . "`";
        } else {
            $t = "`" . self::myAddSlashes($table) . "`";
        }
        $res = $this->query($q = "update /* MySQL::updateSimple */ $t set $qupdate where ($qwhere)");
        return $this->affected_rows($res);
    }
    /**
     * Funkcia sa stara o aktualizovanie tabulky s datami
     *
     * @param string $table Tabulka
     * @param array $data Data, vo formate array("col"=>"new_value") tieto data nemusia byt este osetrene
     * @param string $od Od stlpec
     * @param string $do Do stlpec
     * @param string $id2 ID stlpec
     */
    public function updateOdDoTable($table, $id, $data, $od = "od", $do = "do", $id1 = "id", $id2 = "id2", $insert_new = false, $config = array()) {
        $where = array();
        if (is_array($id)) {
            $where = $id;
        } else {
            $where = array($id2 => $id);
        }
        $od = $this->myAddSlashes($od);
        $do = $this->myAddSlashes($do);
        if (!array_key_exists($do, $data)) $data[$do] = 0;
        $id2 = $this->myAddSlashes($id2);
        $cols = array(); // vlozenim cols sa znizuje load na vystup z db, a malo zvysuje zatazenie ram a proc pri kazdom dotaze
        foreach ($data as $key => $val) {
            $cols[] = $key;
        }
        $cols[] = "id2";
        $row = $this->gr($table, $where, array(), $cols);
        $myId2 = $row["id2"];
        if (!$insert_new) {
            if (!$row) return false;
        } else {
            $data["id2"] = $id;
            if (!$row) return $this->insert($table, $data, $config);
        }
        // z bezpecnostnych dovodov najskor pridaj, potom zrus
        if (!$row) $row = array();
        $updateNeeded = false;
        if ($row[$do] > 0) $data[$do] = $row[$do];
        foreach ($data as $key => $value) {
            if ($data[$key] || is_bool($data[$key]) || is_int($data[$key])) { //ak sa aktualizuje hodnota ktora nie je null, false alebo "0", tak nepouzivaj kontrolu datovych typov
                if ((!array_key_exists($key, $data) || !array_key_exists($key, $row)) || ($data[$key] != $row[$key])) {
                    if ($key != "od" && $key != "do") {
                        //if($data[$key] !== $row[$key]){echo "idem zaktualizovat $key ".var_export($data[$key],true)."!=".var_export($row[$key],true)."\n";}
                        $updateNeeded = true;
                    }
                }
            } else {
                if ((!array_key_exists($key, $data) || !array_key_exists($key, $row)) || ($data[$key] !== $row[$key])) {
                    if ($key != "od" && $key != "do") {
                        //if($data[$key] !== $row[$key]){echo "idem zaktualizovat $key ".var_export($data[$key],true)."!=".var_export($row[$key],true)."\n";}
                        $updateNeeded = true;
                    }
                }
            }
            //if($value===null){}else{
            //		$row[$key] = DB::myAddSlashes($value);
            $row[$key] = $value;
            //}
            
        }
        $cols = "";
        $rows = "";
        if (is_array($id)) {
            if (!@$row[$id2] && $id && @$id[$id2]) $row[$id2] = $id[$id2];
        } else {
            if (!@$row[$id2] && $id) $row[$id2] = $id;
        }
        if (!@$row[$id2]) $row[$id2] = md5(uniqid());
        if (!$row) $updateNeeded = true;
        if (!$updateNeeded) return true;
        $row2 = $this->gr($table, $where);
        if (isset($config["tracktable"])) {
            foreach ($row as $col => $val) {
                if ($col == "od") continue;
                if ($col == "do") continue;
                if ($col == "id2") continue;
                if ($row2[$col] === $val) continue;
                $this->u($config["tracktable"], md5(uniqid()), array("table" => $table, "id3" => $row2["id2"], "type" => "coldatachange", "col" => $col, "old" => $row2[$col], "new" => $val));
            }
            $this->u($config["tracktable"], md5(uniqid()), array("table" => $table, "id3" => $row2["id2"], "type" => "rowchange"));
        }
        $row[$od] = Time::get();
        foreach ($row2 as $k => $v) {
            if (!array_key_exists($k, $row)) $row[$k] = $v;
        }
        foreach ($row as $key => $value) {
            if ($key === $id1) continue;
            if (!$key) continue;
            if ($key == "lchange") {
                continue;
            }
            if ($cols || $rows) {
                $cols.= ",";
                $rows.= ",";
            }
            if ($key == $od) {
                $cols.= "`$od`";
                if (isset($data[$od])) {
                    $rows.= "'" . DB::myAddSlashes($data[$od]) . "'";
                } else {
                    $rows.= "'" . Time::get() . "'";
                }
                continue;
            }
            if ($key == $do) {
                $cols.= "`$do`";
                if (isset($data[$do])) {
                    $rows.= "'" . DB::myAddSlashes($data[$do]) . "'";
                } else {
                    $rows.= "0";
                }
                continue;
            }
            if ($key == "edited_by") {
                $cols.= "`edited_by`";
                if (\AsyncWeb\Security\Auth::userId()) {
                    $rows.= "'" . DB::myAddSlashes(\AsyncWeb\Security\Auth::userId()) . "'";
                } else {
                    if ($value === null) {
                        $rows.= "NULL";
                    } else {
                        $rows.= "'$value'";
                    }
                }
                continue;
            }
            $cols.= "`$key`";
            if ($value === null) {
                $rows.= "null";
            } elseif ($value === false) {
                $rows.= "'0'";
            } elseif (@$config["cols"][$key]["compress"]) {
                $rows.= "compress('" . DB::myAddSlashes($value) . "')";
            } elseif (@$config["cols"][$key]["type"] == "blob" || @$config["cols"][$key]["binary"] || @$config["cols"]["binary"]) {
                //		file_put_contents("/tmp/01-".$row["id2"].".orig2",$value);
                if (!$value) {
                    $rows.= "''";
                } else {
                    $rows.= "0x" . bin2hex($value);
                }
            } else {
                $rows.= "'" . DB::myAddSlashes($value) . "'";
            }
        }
        if (substr($rows, -1) == ",") $rows = substr($rows, 0, -1);
        if (substr($cols, -1) == ",") $cols = substr($cols, 0, -1);
        $table = str_replace("`", "", $table);
        $t = "`" . self::myAddSlashes($table) . "`";
        if (strpos($table, ".") !== false) {
            $ta = explode(".", $table);
            $t = "`" . self::myAddSlashes($ta[0]) . "`.`" . self::myAddSlashes($ta[1]) . "`";
        }
        $query = "insert into $t ($cols) values ($rows)";
        $time = Time::get();
        $this->query("update $t set `$do` = '" . Time::get() . "' where (`$id2` = '$myId2' and (do <= 0 or do >= '$time'))");
        $rows = $this->affected_rows();
        $this->query($query);
        $rows = $this->affected_rows();
        if ($err = $this->error()) {
            \AsyncWeb\Storage\Log::log("MYSQL ERR", "Error occured while inserting row into the DB.. (" . addslashes($query) . ")");
            // zachrana polozky
            $table = str_replace("`", "", $table);
            $t = "`" . self::myAddSlashes($table) . "`";
            if (strpos($table, ".") !== false) {
                $ta = explode(".", $table);
                $t = "`" . self::myAddSlashes($ta[0]) . "`.`" . self::myAddSlashes($ta[1]) . "`";
            }
            $info = $this->getInfo("updateOdDoTable");
            $this->query("update $info $t set `$do` = 0 where (`$id2` = '$myId2') order by `$od` desc limit 1");
            return false;
        }
        return $rows;
    }
    public function getOdDoTableData($table, $col = "name", $id, $timestamp = null, $id2 = "id2", $od = "od", $do = "do") {
        if (!$timestamp) $timestamp = Time::get();
        $table = str_replace("`", "", $table);
        $t = "`" . self::myAddSlashes($table) . "`";
        if (strpos($table, ".") !== false) {
            $ta = explode(".", $table);
            $t = "`" . self::myAddSlashes($ta[0]) . "`.`" . self::myAddSlashes($ta[1]) . "`";
        }
        $info = $this->getInfo("DB::getOdDoTableData");
        $row = $this->fetch_assoc_q($q = "select $info `$col` from $t where (`$id2` = '$id' and od <= '$timestamp' and (do = '0' or do > '$timestamp'))");
        return $row[$col];
    }
    private function getInfo($add = "") {
        $info = @$_SERVER["SCRIPT_FILENAME"] . " " . @$_SERVER["REMOTE_ADDR"];
        if ($add) $info = $add . " " . $info;
        $info = str_replace("*", "", $info);
        return "/* $info */";
    }
    public function u($table, $id2, $data = array(), $config = false, $insert_new = true, $useOdDoSystem = true) {
        return $this->update($table, $id2, $data, $config, $insert_new, $useOdDoSystem);
    }
    public function uall($table, $id2, $data = array(), $config = false) {
        $res = DB::g($table, $id2);
        while ($row = DB::f($res)) {
            DB::u($table, $row["id2"], $data, $config);
        }
        return true;
    }
    private $spracovanet2 = array();
    public function update($table, $id2, $data = array(), $config = array(), $insert_new = false, $useOdDoSystem = true) {
        if ($config !== false) {
            // skontroluje strukturu, a zaktualizuje info
            $t = $table;
            foreach ($data as $key => $v) {
                $t = md5($t . $key);
            }
            if (!isset($this->spracovanet2[$t])) {
                $schema = $this->defaultDB;
                $table = str_replace("`", "", $table);
                $t = "`" . self::myAddSlashes($table) . "`";
                $tab = $table;
                if (strpos($table, ".") !== false) {
                    $ta = explode(".", $table);
                    $t = "`" . self::myAddSlashes($ta[0]) . "`.`" . self::myAddSlashes($ta[1]) . "`";
                    $schema = self::myAddSlashes($ta[0]);
                    $tab = self::myAddSlashes($ta[1]);
                }
                $res = $this->query("select column_name from information_schema.columns where table_schema = '" . $schema . "' and table_name = '$tab'");
                $indb = array();
                $i = 0;
                if (!array_key_exists("od", $data)) {
                    $data["od"] = Time::get();
                }
                if (!array_key_exists("do", $data)) $data["do"] = 0;
                while ($row = $this->fetch_assoc($res)) {
                    $i++;
                    $indb[$row["column_name"]] = $row["column_name"];
                }
                if (!$i) {
                    if (is_array($id2) && @$id2["id2"]) {
                        $data["id2"] = $id2["id2"];
                    } elseif (!is_array($id2)) {
                        $data["id2"] = $id2;
                    }
                    return $this->insert($table, $data, $config);
                }
                $data = array_reverse($data, true);
                $table = str_replace("`", "", $table);
                $t = "`" . self::myAddSlashes($table) . "`";
                if (strpos($table, ".") !== false) {
                    $ta = explode(".", $table);
                    $t = "`" . self::myAddSlashes($ta[0]) . "`.`" . self::myAddSlashes($ta[1]) . "`";
                }
                foreach ($data as $col => $value) {
                    if (!@$indb[$col]) {
                        $default = "NULL";
                        if (isset($config["cols"][$col]["default"])) $default = $config["cols"][$col]["default"];
                        switch (@$config["cols"][$col]["type"]) {
                            case 'int':
                                $lastcol = $this->getLastUsedColumn($table);
                                DB::query($q = "ALTER TABLE $t ADD `$col` bigint(20) NULL DEFAULT $default AFTER `$lastcol` ;");
                            break;
                            case 'double':
                                $lastcol = $this->getLastUsedColumn($table);
                                DB::query($q = "ALTER TABLE $t ADD `$col` double NULL DEFAULT $default AFTER `$lastcol` ;");
                            break;
                            case 'decimal':
                                $before = 10;
                                $after = 8;
                                if (isset($config["cols"][$col]["before"])) $before = $config["cols"][$col]["before"];
                                if (isset($config["cols"][$col]["after"])) $after = $config["cols"][$col]["after"];
                                $lastcol = $this->getLastUsedColumn($table);
                                DB::query($q = "ALTER TABLE $t ADD `$col` decimal($before,$after) NULL DEFAULT $default AFTER `$lastcol` ;");
                                break;
                            case 'text':
                                $lastcol = $this->getLastUsedColumn($table);
                                DB::query($q = "ALTER TABLE $t ADD `$col` text NULL DEFAULT $default AFTER `$lastcol` ;");
                                break;
                            case 'blob':
                                $lastcol = $this->getLastUsedColumn($table);
                                DB::query($q = "ALTER TABLE $t ADD `$col` longblob NULL DEFAULT $default AFTER `$lastcol` ;");
                                break;
                            case 'char':
                                $lastcol = $this->getLastUsedColumn($table);
                                $l = @$config["cols"][$col]["length"];
                                if (!$l) $l = 50;
                                DB::query($q = "ALTER TABLE $t ADD `$col` char($l) NULL DEFAULT $default AFTER `$lastcol` ;");
                                break;
                            case 'varchar':
                                $lastcol = $this->getLastUsedColumn($table);
                                $l = @$config["cols"][$col]["length"];
                                if (!$l) $l = 50;
                                DB::query($q = "ALTER TABLE $t ADD `$col` varchar($l) NULL DEFAULT $default AFTER `$lastcol` ;");
                                break;
                            default:
                                $lastcol = $this->getLastUsedColumn($table);
                                DB::query($q = "ALTER TABLE $t ADD `$col` VARCHAR( 250 ) NULL DEFAULT $default AFTER `$lastcol` ;");
                            }
                        }
                    }
                }
        }
        if ($useOdDoSystem) {
            $u = \AsyncWeb\Security\Auth::userId();
            if ($u) $data["edited_by"] = $u;
            if (!array_key_exists("do", $data)) $data["do"] = 0;
            if (!array_key_exists("od", $data)) $data["od"] = Time::get();
            return $this->updateOdDoTable($table, $id2, $data, "od", "do", "id", "id2", $insert_new, $config);
        } else {
            return $this->updateSimple($table, $id2, $data, $config, $insert_new);
        }
    }
    private function getLastUsedColumn($table) {
        $table = $this->myAddSlashes($table);
        $scheme = $this->myAddSlashes($this->defaultDB);
        $table = str_replace("`", "", $table);
        $t = "`" . self::myAddSlashes($table) . "`";
        if (strpos($table, ".") !== false) {
            $ta = explode(".", $table);
            $t = "`" . self::myAddSlashes($ta[0]) . "`.`" . self::myAddSlashes($ta[1]) . "`";
            $table = self::myAddSlashes($ta[1]);
            $scheme = self::myAddSlashes($ta[0]);
        }
        $info = $this->getInfo("DB::getLastUsedColumn");
        $row = $this->fetch_assoc_q("select $info * from information_schema.columns where TABLE_SCHEMA = '$scheme' and  TABLE_NAME = '$table' and COLUMN_NAME = 'od'");
        if (!$row) return "id2";
        $pos = $row["ORDINAL_POSITION"] - 1;
        $row = $this->fetch_assoc_q("select $info * from information_schema.columns where TABLE_SCHEMA = '$scheme' and TABLE_NAME = '$table' and ORDINAL_POSITION = '$pos'");
        if (!$row) return "id2";
        return $row["COLUMN_NAME"];
    }
    public function deleteAll($table, $where) {
        $res = DB::g($table, $where);
        while ($row = DB::f($res)) {
            DB::delete($table, $row["id2"]);
        }
        return true;
    }
    public function delete($table, $id2) {
        if (is_array($id2)) {
            return $this->deleteAll($table, $id2);
        } else {
            $id2 = $this->myAddSlashes($id2);
            $where = "id2='$id2'";
        }
        $table = str_replace("`", "", $table);
        $t = "`" . self::myAddSlashes($table) . "`";
        if (strpos($table, ".") !== false) {
            $ta = explode(".", $table);
            $t = "`" . self::myAddSlashes($ta[0]) . "`.`" . self::myAddSlashes($ta[1]) . "`";
        }
        $time = Time::get();
        $this->query("update $t set do = '$time' where($where and od <= '$time' and (do = '0' or do > '$time'))"); //
        return $this->affected_rows();
    }
    private $spracovanet = array();
    public function insert($table, $data = array(), $config = array(), $create = true) {
        $cols = "";
        $vals = "";
        if (!@$data["id2"]) $data["id2"] = md5(uniqid());
        if (!@$data["od"]) $data["od"] = Time::get();
        $t1 = $table;
        foreach ($data as $key => $v) {
            $t1 = md5($t1 . $key);
        }
        if ($create && $this->tryrepair && !isset($this->spracovanet[$t1])) {
            $table = str_replace("`", "", $table);
            $t = "`" . self::myAddSlashes($table) . "`";
            if (strpos($table, ".") !== false) {
                $ta = explode(".", $table);
                $t = "`" . self::myAddSlashes($ta[0]) . "`.`" . self::myAddSlashes($ta[1]) . "`";
            }
            // pokus sa vytvorit tabulku
            $new_table_q = "CREATE TABLE IF NOT EXISTS  $t (
  `id` int(11)  NOT NULL AUTO_INCREMENT,
  `id2` char(32) collate utf8_unicode_ci NOT NULL,\n";
            $colsprocessed = array("id" => true, "id2" => true, "od" => true, "do" => true, "edited_by" => true);
            foreach ($data as $key => $value) {
                if (isset($colsprocessed[$key])) {
                    continue;
                }
                $colsprocessed[$key] = true;
                $default = "NULL";
                if (isset($config["cols"][$key]["default"])) $default = "'" . $this->myAddSlashes($config["cols"][$key]["default"]) . "'";
                if (@$config["cols"][$key]["type"] == "double") {
                    $new_table_q.= "`$key` double NULL DEFAULT $default,";
                } elseif (@$config["cols"][$key]["type"] == "decimal") {
                    $before = 10;
                    $after = 8;
                    if (isset($config["cols"][$key]["before"])) $before = $config["cols"][$key]["before"];
                    if (isset($config["cols"][$key]["after"])) $after = $config["cols"][$key]["after"];
                    $new_table_q.= "`$key` decimal($before,$after) NULL DEFAULT $default,";
                } elseif (@$config["cols"][$key]["type"] == "int") {
                    $new_table_q.= "`$key` bigint NULL DEFAULT $default,";
                } elseif (@$config["cols"][$key]["type"] == "char") {
                    $l = @$config["cols"][$key]["length"];
                    if (!$l) $l = 50;
                    $new_table_q.= "`$key` char($l) collate utf8_unicode_ci NULL DEFAULT $default,\n";
                } elseif (@$config["cols"][$key]["type"] == "text") {
                    $new_table_q.= "`$key` text NULL DEFAULT $default,";
                } elseif (@$config["cols"][$key]["type"] == "blob") {
                    $new_table_q.= "`$key` longblob NULL DEFAULT $default,";
                } elseif (@$config["cols"][$key]["type"] == "varchar") {
                    $l = @$config["cols"][$key]["length"];
                    if (!$l) $l = 250;
                    $new_table_q.= "`$key` varchar($l) NULL DEFAULT $default,";
                } else {
                    $new_table_q.= "`$key` varchar(250) collate utf8_unicode_ci NULL DEFAULT $default,\n";
                }
            }
            if (isset($config["cols"]) && is_array($config["cols"])) foreach ($config["cols"] as $key => $colConfig) {
                if (isset($colsprocessed[$key])) {
                    continue;
                }
                $colsprocessed[$key] = true;
                $default = "NULL";
                if (isset($config["cols"][$key]["default"])) $default = "'" . $this->myAddSlashes($config["cols"][$key]["default"]) . "'";
                if (@$config["cols"][$key]["type"] == "double") {
                    $new_table_q.= "`$key` double NULL DEFAULT $default,";
                } elseif (@$config["cols"][$key]["type"] == "decimal") {
                    $before = 10;
                    $after = 8;
                    if (isset($config["cols"][$key]["before"])) $before = $config["cols"][$key]["before"];
                    if (isset($config["cols"][$key]["after"])) $after = $config["cols"][$key]["after"];
                    $new_table_q.= "`$key` decimal($before,$after) NULL DEFAULT $default,";
                } elseif (@$config["cols"][$key]["type"] == "int") {
                    $new_table_q.= "`$key` bigint NULL DEFAULT $default,";
                } elseif (@$config["cols"][$key]["type"] == "char") {
                    $l = @$config["cols"][$key]["length"];
                    if (!$l) $l = 50;
                    $new_table_q.= "`$key` char($l) collate utf8_unicode_ci NULL DEFAULT $default,\n";
                } elseif (@$config["cols"][$key]["type"] == "text") {
                    $new_table_q.= "`$key` text NULL DEFAULT $default,";
                } elseif (@$config["cols"][$key]["type"] == "blob") {
                    $new_table_q.= "`$key` longblob NULL DEFAULT $default,";
                } elseif (@$config["cols"][$key]["type"] == "varchar") {
                    $l = @$config["cols"][$key]["length"];
                    if (!$l) $l = 250;
                    $new_table_q.= "`$key` varchar($l) NULL DEFAULT $default,";
                } else {
                    $new_table_q.= "`$key` varchar(250) collate utf8_unicode_ci NULL DEFAULT $default,\n";
                }
            }
            $new_table_q.= "
  `od` bigint(20) NOT NULL DEFAULT 0,
  `do` bigint(20) NOT NULL DEFAULT 0,
  `edited_by` char(32) collate utf8_unicode_ci NULL DEFAULT null,
  `lchange` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  ";
            if ($config && array_key_exists("keys", $config)) foreach (@$config["keys"] as $key) {
                $new_table_q.= "KEY `$key` (`$key`),";
            }
            $new_table_q.= "
  KEY `id2` (`id2`),
  KEY `od` (`od`),
  KEY `do` (`do`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1000000 ;
";
            $this->query($new_table_q);
            //foreach ($data as $key=>$value){
            //	$this->query("ALTER TABLE `$table` ADD `$key` VARCHAR( 250 ) NULL After `id2` ");
            //}
            if($err = $this->error()){
				throw new \Exception($err);
			}
        }
        foreach ($data as $key => $value) {
            if (!$key) continue;
            if ($cols) $cols.= ",";
            $cols.= "`$key`";
            if ($value === null || $value === false) {
                if ($vals) $vals.= ",";
                $vals.= "null";
            } else {
                if (@$config["cols"][$key]["compress"]) {
                    if ($vals) $vals.= ",";
                    $vals.= "compress('" . $this->myAddSlashes($value) . "')";
                } elseif (@$config["cols"][$key]["type"] == "blob" || @$config["cols"][$key]["binary"] || @$config["cols"]["binary"]) {
                    if ($vals) $vals.= ",";
                    if (!$value) {
                        $vals.= "''";
                    } else {
                        $vals.= "0x" . bin2hex($value) . "";
                    }
                } else {
                    if ($vals) $vals.= ",";
                    $vals.= "'" . $this->myAddSlashes($value) . "'";
                }
            }
        }
        $table = str_replace("`", "", $table);
        $t = "`" . self::myAddSlashes($table) . "`";
        if (strpos($table, ".") !== false) {
            $ta = explode(".", $table);
            $t = "`" . self::myAddSlashes($ta[0]) . "`.`" . self::myAddSlashes($ta[1]) . "`";
        }
        $insert_query = "insert into $t ($cols) values ($vals)";
        if (isset($config["tracktable"])) {
            $this->u($config["tracktable"], md5(uniqid()), array("table" => $table, "id3" => $data["id2"], "type" => "newrow"));
        }
        $this->query($insert_query);
        $e = $this->error();
        if ($e && $this->tryrepair && !isset($this->spracovanet[$t1])) {
            foreach ($data as $key => $value) {
                $this->query($q = "ALTER TABLE $t ADD `$key` VARCHAR( 250 ) NULL After `id2` ");
            }
        }
        $this->spracovanet[$t1] = true;
        $ret = $this->affected_rows();
        return $ret;
        return true;
    }
    /**
     get row from table
     */
    public function gr($table, $where = array(), $order = array(), $cols = array(), $groupby = array(), $having = array(), $offset = 0, $time = null) {
        if ($this->logqueries) $start = microtime(true);
        $ret = $this->f($this->g($table, $where, $offset, 1, $order, $cols, $groupby, $having, false, $time, true));
        if (!$ret) $ret = $this->f($this->g($table, $where, $offset, 1, $order, $cols, $groupby, $having, false, $time, false));
        if ($this->logqueries) $res = file_put_contents($this->logfile, "GR: " . (number_format(microtime(true) - $start, 4)) . "\n", FILE_APPEND);
        return $ret;
    }
    public function getRow($table, $where = array(), $time = null, $offset = 0, $od = "od", $do = "do", $id2 = "id2") {
        if (is_array($time)) {
            $res = $this->get($table, $where, $offset, 1, null, $time, $od, $do, $id2);
        } else {
            $res = $this->get($table, $where, $offset, 1, $time, array(), $od, $do, $id2);
        }
        return $this->fetch_assoc($res);
    }
    /**
     get result
     */
    public function g($table, $where = array(), $offset = null, $count = null, $order = array(), $cols = array(), $groupby = array(), $having = array(), $distinct = false, $time = null, $fast = false) {
        return $this->get($table, $where, $offset, $count, $time, $order, "od", "do", "id2", $cols, $groupby, $having, $distinct, $fast);
    }
    public function get($table, $where = array(), $offset = null, $count = null, $time = null, $order = array(), $od = "od", $do = "do", $id2 = "id2", $cols = array(), $groupby = array(), $having = array(), $distinct = false, $fast = false) {
        $this->checkIndex($table, $where, $order);
        if (!$offset) $offset = 0;
        $add = "";
        $forceoddo = false;
        if ($time) $forceoddo = true;
        if (!$time) $time = Time::get();
        if ($forceoddo) $fast = false;
        $time = $this->myAddSlashes($time);
        $is_op = false;
        $use_od = true;
        $use_do = true;
        $where1 = "1";
        if (is_array($where)) {
            foreach ($where as $col => $value) {
                if (is_numeric($col) && is_array($value)) {
                    $col = @$value["col"];
                }
                if ($col == $od) $use_od = false;
                if ($col == $do) $use_do = false;
                $colsep = "`";
                if (strpos($col, " ")) $colsep = "";
                if (strpos($col, "`")) $colsep = "";
                if (strpos($col, "(")) $colsep = "";
                if ($col) switch ($col) {
                    case '-and':
                        $where1.= " and";
                        $is_op = true;
                    break;
                    case '-or':
                        $where1.= " or";
                        $is_op = true;
                    break;
                    case '-(':
                        if (!$is_op) $where1.= " and ";
                        $is_op = true;
                        $where1.= " (";
                        break;
                    case '-)':
                        $where1.= ")";
                        $is_op = false;
                        break;
                    case '-value':
                        $where1.= " " . $value["value"] . " ";
                        $is_op = false;
                        break;
                    default:
                        if (!$is_op) $where1.= " and ";
                        if (is_array($value)) {
                            $op = "=";
                            if (@$value["op"]) switch ($value["op"]) {
                                case 'is':
                                    $op = "is";
								break;
                                case 'in':
                                    $op = "in";
									if(is_array($value["value"])){
										$val = "";
										foreach($value["value"] as $valueitem){
											if($val) $val.= ',';
											$val .= "'".self::myAddSlashes($valueitem)."'";
										}
										$value["value"] = "($val)";
									}
                                break;
                                case 'isnot':
                                    $op = "is not";
                                break;
                                case 'lt':
                                    $op = "<";
                                break;
                                case 'lte':
                                    $op = "<=";
                                break;
                                case 'gt':
                                    $op = ">";
                                break;
                                case 'gte':
                                    $op = ">=";
                                break;
                                case 'e':
                                    $op = "=";
                                break;
                                case 'eq':
                                    $op = "=";
                                break;
                                case 'neq':
                                    $op = "!=";
                                break;
                                case 'like':
                                    $op = "like";
                                break;
                                case 'notlike':
                                    $op = "not like";
                                break;
                                case 'null':
                                    $op = "null";
                                break;
                            }
                            if ($op == "null") {
                                $where1.= "($colsep$col$colsep is null or `$col` = '')";
                                $is_op = false;
                                continue;
                            }
                            if (@$value["value"] !== null && $op == "in") {
                                $value1 = $value["value"];
                                $where1.= " $colsep$col$colsep $op $value1";
                            } elseif (@$value["value"] !== null) {
                                $value1 = $this->myAddSlashes(@$value["value"]);
                                $where1.= " $colsep$col$colsep $op '$value1'";
                            } elseif (@$value["col2"] !== null) {
                                $where1.= " $colsep$col$colsep $op $colsep" . $value["col2"] . "$colsep";
                            } else {
                                $where1.= " $colsep$col$colsep $op null";
                            }
                        } else {
                            $value = $this->myAddSlashes($value);
                            $where1.= " $colsep$col$colsep='$value'";
                        }
                        $is_op = false;
                    }
                }
            } else {
                $value = $this->myAddSlashes($where);
                $where1 = " id2 = '$value'";
            }
            $add = $where1;
            $od = $this->myAddSlashes($od);
            $do = $this->myAddSlashes($do);
            $id2 = $this->myAddSlashes($id2);
            $limit = "";
            if ($offset !== null && $count !== null) {
                $offset = $this->myAddSlashes($offset);
                $count = $this->myAddSlashes($count);
                $offset++;
                $offset--;
                $count++;
                $count--;
                $limit = " limit $offset,$count";
            }
            $ord = "";
            if ($order) {
                foreach ($order as $k => $data) {
                    if (is_array($data)) {
                        if ($ord) $ord.= ", ";
                        $ord.= "`" . $data["col"] . "` " . @$data["type"];
                    } else {
                        if ($ord) $ord.= ", ";
                        $ord.= "`" . $k . "` " . $data;
                    }
                }
                $ord = " order by " . $ord . "";
            }
            $add1 = "";
            if ($fast) {
                if ($use_do || $forceoddo) {
                    $add1 = "and (`$do` = 0)";
                }
            } else {
                if ($use_do || $forceoddo) {
                    $add1 = "and (`$do` <= 0 or `$do` > '$time')";
                }
                if ($use_od || $forceoddo) {
                    $add1 = "and `$od` <= '$time'" . $add1;
                }
            }
            $qcols = "";
            if (!is_array($cols)) {
                $cols = array($cols);
            }
            foreach ($cols as $name => $col) {
                if ($qcols) $qcols.= ",";
                if (is_array($col)) {
                    \AsyncWeb\Storage\Log::log("HCK", "ARRAY in SQL:table=$table:where=" . print_r($where, true) . ":name=$name:col=" . print_r($col, true), ML__TOP_SEC_LEVEL);
                    echo "Security issue has been raised! Action has been logged.";
                    exit;
                }
                $colsep = "`";
                if (strpos($col, " ")) $colsep = "";
                if (strpos($col, "`")) $colsep = "";
                if (strpos($col, "(")) $colsep = "";
                $qcols.= "$colsep$col$colsep";
                if (!is_numeric($name)) {
                    $qcols.= " as `$name`";
                }
            }
            if (!$qcols) $qcols = "*";
            $qgroupby = "";
            if (!$groupby) $groupby = array();
            foreach ($groupby as $g) {
                if ($qgroupby) $qgroupby.= ",";
                $qgroupby.= $g;
            }
            if ($qgroupby) $qgroupby = "group by ($qgroupby)";
            $qhaving = "";
            if (!$having) $having = array();
            foreach ($having as $g) {
                if ($qhaving) $qhaving.= ",";
                $qhaving.= $g;
            }
            if ($qhaving) $qhaving = "having ($qgroupby)";
            $qdistinct = "";
            if ($distinct) $qdistinct = "distinct ";
            $table = str_replace("`", "", $table);
            $t = "`" . self::myAddSlashes($table) . "`";
            if (strpos($table, ".") !== false) {
                $ta = explode(".", $table);
                $t = "`" . self::myAddSlashes($ta[0]) . "`.`" . self::myAddSlashes($ta[1]) . "`";
            }
            $info = $this->getInfo("DB::get");
            $ret = $this->query($q = "select $info $qdistinct$qcols from $t where ($add $add1) $qgroupby $qhaving $ord $limit");
            //file_put_contents("mysql.txt",$q."\n",FILE_APPEND);
            return $ret;
        }
        private function checkIndex($table, $filter = array(), $order = array(), $od = "od", $do = "do") {
            $ret = false;
            if (class_exists("DB")) {
                if (!DB::$repairIndexesImmidiently && !$this->checkIndexes) return true;
            } else {
                if (!$this->checkIndexes) return true;
            }
            if (!count($filter)) return true;
            if (!$filter) return true;
            if ($table == "ZZZMysqlUpdate") return true;
            if ($table == "ZZZMysqlTablesKeys") return true;
            $keys = array();
            if ($filter && is_array($filter)) foreach ($filter as $key => $val) {
                if (is_array($val)) {
                    if (isset($val['col'])) {
                        $keys[$val["col"]] = $val["col"];
                    }
                } else {
                    $keys[$key] = $key;
                }
            }
            if ($order && is_array($order)) foreach ($order as $key => $val) {
                if (!is_numeric($key)) {
                    $keys[$key] = $key;
                }
            }
            $schema = $this->defaultDB;
            if (class_exists("DB")) {
                if (DB::$repairIndexesImmidiently) {
                    $table = str_replace("`", "", $table);
                    $t = "`" . self::myAddSlashes($table) . "`";
                    if (strpos($table, ".") !== false) {
                        $ta = explode(".", $table);
                        $t = "`" . self::myAddSlashes($ta[0]) . "`.`" . self::myAddSlashes($ta[1]) . "`";
                    }
                    $res = $this->query("SHOW INDEX FROM $t");
                    $indexes = array();
                    while ($row = $this->fetch_assoc($res)) {
                        $indexes[$row["Key_name"]][$row["Column_name"]] = $row["Column_name"];
                    }
                    foreach ($keys as $key) {
                        if (!isset($indexes[$key])) {
                            $this->query("ALTER TABLE $table ADD INDEX ( `$key` )");
                        }
                    }
                    return;
                }
            }
            foreach ($keys as $key) {
                $id2 = md5($schema . " - " . $table . " - " . $key);
                $this->u("ZZZMysqlTablesKeys", $id2, array("schema" => $schema, "table" => $table, "key" => $key));
            }
            return true;
            foreach ($filter as $key => $val) {
                if (strpos($val, "(")) {
                    unset($filter[$key]);
                    continue;
                }
                $ar = @explode("*", $val);
                if (count($ar) > 1) {
                    foreach ($ar as $v) {
                        $filter[$v] = $v;
                    }
                }
                $ar = @explode("/", $val);
                if (count($ar) > 1) {
                    foreach ($ar as $v) {
                        $filter[$v] = $v;
                    }
                }
            }
            $table = str_replace("`", "", $table);
            $t = "`" . self::myAddSlashes($table) . "`";
            if (strpos($table, ".") !== false) {
                $ta = explode(".", $table);
                $t = "`" . self::myAddSlashes($ta[0]) . "`.`" . self::myAddSlashes($ta[1]) . "`";
            }
            $res = $this->query("SHOW INDEX FROM $t");
            $indexes = array();
            while ($row = $this->fetch_assoc($res)) {
                $indexes[$row["Key_name"]][$row["Column_name"]] = $row["Column_name"];
            }
            foreach ($indexes as $index) {
                $min = true;
                foreach ($index as $key => $value) {
                    if (!array_key_exists($value, $filter)) {
                        $min = false;
                    }
                }
                if ($min) {
                    $max = true;
                    foreach ($filter as $key => $value) {
                        if (is_numeric($key)) continue;
                        if (@!array_key_exists($key, $index)) {
                            $max = false;
                        }
                    }
                }
                if ($min && $max) {
                    $ret = true;
                }
            }
            // nenasiel sa index
            $id2 = md5($table);
            $cols = array();
            foreach ($filter as $key => $value) {
                if (is_numeric($key)) continue;
                $id2 = md5($id2 . $key);
                $cols[] = $key;
            }
            $config = array();
            $config["cols"]["data"]["type"] = "text";
            if (!$ret) {
                if ($cols) {
                    $ret1 = $this->u("ZZZMysqlUpdate", $id2, array("id2" => $id2, "table" => $table, "data" => serialize($cols)), $config);
                }
            }
            //if($err = $this->error()){ echo $err;exit;}
            $hasOd = false;
            foreach ($filter as $key => $value) {
                if ($key == $od) $hasOd = true;
            }
            $hasDo = false;
            foreach ($filter as $key => $value) {
                if ($key == $do) $hasDo = true;
            }
            if (!$hasOd) {
                $filter[$od] = $od;
            }
            if (!$hasDo) {
                $filter[$do] = $do;
            }
            if (!$hasOd || !$hasDo) {
                $ret = $ret && $this->checkIndex($table, $filter);
            }
        }
        public function cleanUp($table, $type = "deleted", $time = 0) {
            $table = $tab = str_replace("`", "", $table);
            $t = "`" . self::myAddSlashes($table) . "`";
            if (strpos($table, ".") !== false) {
                $ta = explode(".", $table);
                $t = "`" . self::myAddSlashes($ta[0]) . "`.`" . self::myAddSlashes($ta[1]) . "`";
                $tab = $ta[1];
            }
            $res = DB::query("show create table $t");
            $row = DB::f($res);
            $q = $row["Create Table"];
            $q = str_replace("CREATE TABLE `", "CREATE TABLE IF NOT EXISTS `", $q);
            $q = str_replace("`$tab", "`${tab}_archive", $q);
            $q = str_replace("`id` int(11) NOT NULL AUTO_INCREMENT,", "`id0` int(11) NOT NULL AUTO_INCREMENT,\n`id` int(11) NOT NULL,", $q);
            $q = str_replace("PRIMARY KEY (`id`),", "PRIMARY KEY (`id0`),KEY (`id`),", $q);
            DB::query($q);
        }
        public function clean($db, $dba, $table, $type = "archiveObsolete", $t = 1, $optimize = true, $where = array(), $dbg = false, $table2 = false) {
            $dbg = $dbg || $this->debug || self::$debug2;
            if ($dbg) echo "self::clean()/" . date("c") . "\n";
            $colrow = "";
            $db = $this->myAddSlashes($db);
            $dba = $this->myAddSlashes($dba);
            if (!$table2) $table2 = $table;
            $table = $this->myAddSlashes($table);
            $table2 = $this->myAddSlashes($table2);
            $res = $this->query("select * from information_schema.columns where table_schema = '" . $db . "' and table_name = '" . $table . "'");
            $in = array();
            $inarchive = array();
            if (!$res || !$this->num_rows($res)) {
                if ($dbg) echo "nemam tabulku\n";
                return false;
            }
            $types = array();
            while ($row = $this->f($res)) {
                if ($row["COLUMN_NAME"] == "id") continue;
                $col = $in[$row["COLUMN_NAME"]] = $row["COLUMN_NAME"];
                $types[$row["COLUMN_NAME"]] = $row["COLUMN_TYPE"];
                if ($colrow) $colrow.= ",";
                $colrow.= "`$col`";
            }
            $res = $this->query("select * from information_schema.columns where table_schema = '" . $dba . "' and table_name = '" . $table2 . "'");
            $inarchive = array();
            $notin = array();
            if (!$res || !$this->num_rows($res)) {
                // vytvor tabulku
                $row = $this->f($this->query("show create table $db.$table"));
                $q = $row["Create Table"];
                $q = str_replace("CREATE TABLE `$table", "CREATE TABLE $dba.`$table2", $q);
                DB::query($q);
                if ($e = $this->error()) {
                    if ($dbg) echo "unable to create table in archive\n";
                    return false;
                }
            }
            while ($row = $this->f($res)) {
                if ($row["COLUMN_NAME"] == "id") continue;
                $inarchive[$row["COLUMN_NAME"]] = $row["COLUMN_NAME"];
            }
            if ($dbg) var_dump($in);
            foreach ($in as $col) {
                if (!$inarchive[$col]) {
                    $type = $types[$col];
                    if ($dbg) echo "upravujem strukturu tabulky archivu pre stlpec $col: " . date("c") . "\n";
                    $q = "ALTER TABLE `$dba`.`$table2` ADD `$col` $type NULL DEFAULT NULL AFTER `id2` ;";
                    if ($dbg) echo $q . "\n";
                    DB::query($q);
                    if ($dbg) echo "done: " . date("c") . "\n";
                    //if($dbg) echo "table structures does not match! ($col) \n";return false;
                    
                }
            }
            if ($e = $this->error()) {
                if ($dbg) echo "$q:$e\n";
                return false;
            }
            $t = Time::get(Time::get() - Time::span($t));
            $addLog2Lock = ", log2 write";
            if ($table == "log2") {
                $addLog2Lock = "";
            }
            if ($this->checkIndexes) {
                $addLog2Lock.= ", ZZZMysqlTablesKeys write";
            }
            $this->query($q = "LOCK TABLES `$dba`.`$table2` write,`$db`.`$table` write $addLog2Lock");
            if ($e = $this->error()) {
                $this->query("UNLOCK TABLES");
                if ($dbg) echo "$q:$e\n";
                return false;
            }
            $mywhere = "";
            switch ($type) {
                case "archiveObsolete":
                    $mywhere = " where (do > 0 and do < $t)";
                break;
                case "archiveOld":
                    $mywhere = " where (od < $t)";
                break;
                case "archiveAll":
                    $mywhere = "";
                break;
                case "checkStructure":
                    $mywhere = " and 1=2";
                break;
            }
            foreach ($where as $col => $value) {
                if (is_array($value)) {
                    if ($dbg) echo "array in where\n";
                    return false;
                }
                if ($mywhere) {
                    $mywhere.= " and ";
                } else {
                    $mywhere = " where ";
                }
                $value = $this->myAddSlashes($value);
                $mywhere.= "`$col`='$value'";
            }
            $q = "insert into `$dba`.`$table2` ($colrow) select $colrow from $db.$table $mywhere";
            if ($dbg) {
                echo $q . "\n";
                //exit;
                
            }
            $res = $this->query($q);
            if ($e = $this->error()) {
                $this->query("UNLOCK TABLES");
                if ($dbg) echo "$q:$e\n";
                return false;
            }
            $rows = $this->affected_rows($res);
            if ($dbg) {
                echo "moved rows: $rows\n";
            }
            if ($rows) {
                $this->query($q = "delete from $db.$table $mywhere");
                if ($e = $this->error()) {
                    $this->query("UNLOCK TABLES");
                    if ($dbg) echo "$q:$e\n";
                    return false;
                }
            }
            $this->query($q = "UNLOCK TABLES");
            if ($optimize) $this->query($q = "OPTIMIZE TABLE `$table`");
            if ($e = $this->error()) {
                if ($dbg) echo "$q:$e\n";
                return false;
            }
            return true;
        }
    }
    