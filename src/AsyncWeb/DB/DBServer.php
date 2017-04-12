<?php
namespace AsyncWeb\DB;
/**
 * Táto trieda sa stará o databázu mysql
 *
 * @author Ludovit Scholtz
 * @version 1.0.3.20060312
 *
 * 1.0.2 			neuklada si do tohoto suboru heslo.. bere ho z config/db.php
 * 1.0.3.20060312 	error() opravena a nedava do logu ked sa nevyskytne ziadna chyba
 */
abstract class DBServer {
    abstract public function query($query, $link = null, $params = array());
    abstract public function fetch_assoc($res);
    abstract public function fetch_array($res);
    abstract public function fetch_object($res);
    abstract public function num_rows($res);
    abstract public function affected_rows();
    abstract public function error();
    abstract public function fetch_assoc_q($query);
    abstract public function insert_id();
}
?>
