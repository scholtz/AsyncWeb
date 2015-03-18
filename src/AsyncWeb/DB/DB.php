<?php
namespace AsyncWeb\DB;
/**
 * This class manages static connection to the database
 * 
 * It must be setted up first 
 * $res = DB::query("insert into table values('a','b')");
 * echo DB::affected_rows($res);
 * 
 * 
 * @author Ludovit Scholtz
 * @version 1.0.1.20150129
 * 
 * 1.0.1 2015-01-29				update to use namespaces
 * 1.0.0 2007-01-08				vytvorena trieda pre napojenie na lubovolnu db (zatial iba podpora mysqlServer
 */

class DB{
 private static $connection = null;
 public static $CONNECTED = false;
 public static $CONNECTING = false;
 public static $repairIndexesImmidiently = false; // repairs indexes if needed
 public static $DB_TYPE = '\AsyncWeb\DB\MysqlServer';
 private static function connect(){
	if(!DB::$connection){
		$db = '\AsyncWeb\DB\MysqlServer';
		DB::$CONNECTING = true;
		if(DB::$DB_TYPE) $db = DB::$DB_TYPE;
		DB::$connection = new $db();
		DB::$CONNECTED = true;
		DB::$CONNECTING = false;
	}
 }
 
 public static function query($query,$link=null,$params=array()){
  DB::connect();
  return DB::$connection->query($query,$link,$params);
 }
 public static function fetch_assoc($res){
  DB::connect();
  return DB::$connection->fetch_assoc($res);
 }
 public static function fetch_array($res){
  DB::connect();
  return DB::$connection->fetch_array($res);
 }
 public static function fetch_object($res){
  DB::connect();
  return DB::$connection->fetch_object($res);
 }
 public static function num_rows($res){
  DB::connect();
  return DB::$connection->num_rows($res);
 }
 public static function affected_rows(){
  DB::connect();
  return DB::$connection->affected_rows();
 }
 public static function error($showQuery=false){
  DB::connect();
  return DB::$connection->error($showQuery);
 }
 
 public static function insert_id(){
  DB::connect();
  return DB::$connection->insert_id();
 }
 
 public static function fetch_assoc_q($query){
  DB::connect();
  return DB::$connection->fetch_assoc_q($query);
 }
 
 public static function myAddSlashes($string){
  DB::connect();
  return DB::$connection-> myAddSlashes($string);
 }
 /**
  * Funkcia vracia tabulkovu hodnotu akcie
  * @param string $table Tabulka
  * @param string $id Id polozky databaze
  * @param string $col Datovy stlpec
  * @param string $od Od stlpec
  * @param string $do Do stlpec
  * @param string $id2 ID stlpec
  */
 public static function getOdDoTableData($table,$col="name",$id,$timestamp=null,$id2="id2",$od="od",$do="do"){
  DB::connect();
  return DB::$connection->getOdDoTableData($table,$col,$id,$timestamp,$id2,$od,$do);
 }
 /**
  * Funkcia sa stara o aktualizovanie tabulky s datami 
  *
  * @param string $table Tabulka
  * @param string $id Id polozky databaze
  * @param array $data Data, vo formate array("col"=>"new_value") tieto data nemusia byt este osetrene
  * @param string $od Od stlpec
  * @param string $do Do stlpec
  * @param string $id2 ID stlpec
  */
 public static function updateOdDoTable($table,$id,$data,$od="od",$do="do",$id1="id",$id2="id2",$insert_new=false){
  DB::connect();
  return DB::$connection->updateOdDoTable($table,$id,$data,$od,$do,$id1,$id2,$insert_new);
 }

 /**
  * Funkcia vlozi data do tab, skontroluje ci struktura je spravna, snazi sa ju modifikovat
  * @param string $table Tabulka
  * @param array $data Hodnoty, col=>value
  */
 public static function insert($table,$data=array(),$config=array()){
 	DB::connect();
 	return DB::$connection->insert($table,$data,$config);
 }
 
 public static function update($table,$id2,$data=array(),$config=array(),$insert_new=false){
 	DB::connect();
 	return DB::$connection->update($table,$id2,$data,$config,$insert_new);
 }
 public static function getRow($table,$where=array(),$time = null,$offset=0,$od="od",$do="do",$id2="id2"){
 	DB::connect();
 	return DB::$connection->getRow($table,$where,$time,$offset,$od,$do,$id2);
 }
 public static function get($table,$where=array(),$offset=null,$count=null,$time = null,$order=array(),$od="od",$do="do",$id2="id2"){
 	DB::connect();
 	return DB::$connection->get($table,$where,$offset,$count,$time,$order,$od,$do,$id2);
 }
 public static function deleteAll($table,$where){
 	DB::connect();
 	return DB::$connection->deleteAll($table,$where);
 }

 public static function delete($table,$id2){
 	DB::connect();
 	return DB::$connection->delete($table,$id2);
 }
 
 public static function u($table,$id2,$data=array(),$config=array(),$insert_new=true,$useOdDoSystem=true){
  DB::connect();
  return DB::$connection->u($table,$id2,$data,$config,$insert_new,$useOdDoSystem);
 }
 public static function uall($table,$id2,$data=array(),$config=array()){
  DB::connect();
  return DB::$connection->uall($table,$id2,$data,$config);
 }
 public static function gr($table,$where=array(),$order=array(),$cols=array(),$groupby=array(),$having=array(),$offset=0,$time=null){
  DB::connect();
  return DB::$connection->gr($table,$where,$order,$cols,$groupby,$having,$offset,$time);
 }
 public static function g($table,$where=array(),$offset=null,$count=null,$order=array(),$cols=array(),$groupby=array(),$having=array(),$distinct=false,$time=null){
  DB::connect();
  return DB::$connection->g($table,$where,$offset,$count,$order,$cols,$groupby,$having,$distinct,$time);
 }
 public static function f($res){
  DB::connect();
  return DB::$connection->f($res);
 }
 public static function cleanUp($table,$type="deleted",$time=0){
  DB::connect();
  return DB::$connection->cleanUp($table,$type,$time);
 }
 public static function clean($db,$dba,$table,$type="archiveObsolete",$t=1,$optimize=true,$where=array(),$dbg=false){
  DB::connect();
  return DB::$connection->clean($db,$dba,$table,$type,$t,$optimize,$where,$dbg);
 }
 /**
  Query builder - gets the res of the query
  DB::qb("table"=>$table,"where"=>$where=array(),"offset"=>$offset=null,"count"=>$count=null,"order"=>$order=array(),"cols"=>$cols=array(),"groupby"=>$groupby=array(),"having"=>$having=array(),"distinct"=>$distinct=false,"time"=>$time=null)
 */
 public static function qb($table,$mixed=array()){
  DB::connect();
  $where=array();
  $offset=null;
  $count=null;
  $order=array();
  $cols=array();
  $groupby=array();
  $having=array();
  $distinct=false;
  $time=null;
  if(!$table) throw new \AsyncWeb\Exceptions\DBException("DB Error: Table name required! (0x0019249148)");
  if(isset($mixed["where"])) $where = $mixed["where"];
  if(isset($mixed["offset"])) $offset = $mixed["offset"];
  if(isset($mixed["count"])) $count = $mixed["count"];
  if(isset($mixed["limit"])) $count = $mixed["limit"];
  if(isset($mixed["order"])) $order = $mixed["order"];
  if(isset($mixed["cols"])) $cols = $mixed["cols"];
  if(isset($mixed["groupby"])) $groupby = $mixed["groupby"];
  if(isset($mixed["having"])) $having = $mixed["having"];
  if(isset($mixed["distinct"])) $distinct = $mixed["distinct"];
  if(isset($mixed["time"])) $time = $mixed["time"];
  
  return DB::$connection->g($table,$where,$offset,$count,$order,$cols,$groupby,$having,$distinct,$time);
 }
 /**
  Query builder - gets the row for the query
  DB::qbr("table"=>$table,"where"=>$where=array(),"order"=>$order=array(),"cols"=>$cols=array(),"groupby"=>$groupby=array(),"having"=>$having=array(),"offset"=>$offset=0,"time"=>$time=null)
 */
 public static function qbr($table,$mixed=array()){
  DB::connect();
  if(!$table) throw new \AsyncWeb\Exceptions\DBException("DB Error: Table name required! (0x0019249149)");
  
  
  $where=array();
  $order=array();
  $cols=array();
  $groupby=array();
  $having=array();
  $offset=0;
  $time=null;
  
  if(!$table) throw new \AsyncWeb\Exceptions\DBException("DB Error, Table name required! (0x0019249149)");
  if(isset($mixed["where"])) $where = $mixed["where"];
  if(isset($mixed["order"])) $order = $mixed["order"];
  if(isset($mixed["cols"])) $cols = $mixed["cols"];
  if(isset($mixed["groupby"])) $groupby = $mixed["groupby"];
  if(isset($mixed["having"])) $having = $mixed["having"];
  if(isset($mixed["offset"])) $offset = $mixed["offset"];
  if(isset($mixed["time"])) $time = $mixed["time"];
 
  return DB::$connection->gr($table,$where,$order,$cols,$groupby,$having,$offset,$time);
 }
 /* skontroluje, ci aspon jeden taky zaznam existuje */
 public static function c($table,$where){
  $row=DB::qbr($table,array("where"=>$where,"cols"=>"id"));
  if(!$row) return false;
  return $row["id"];
 }
}

?>