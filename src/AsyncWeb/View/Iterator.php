<?php
/**
 * 
 * Tato trieda sa stara o rozdelenie viacerych objektov na viacej stranok tym, ze sa vytvori jednoznacny iterator
 * ktory sa stara o zmenu strany, a dotazuje sa nanho od kolkeho obj sa ma zacat, po kolky, a kolko
 *
 * @author Ludovit Scholtz
 * @version 1.0.0.2005.12.19
 */
namespace AsyncWeb\View; 
 
class Iterator{
	private $per_page; // kolko sa zobrazi na stranu
	private $start_from; // od kolkatky ma zacat
	private $end; // po kade ma ist
	private $id; // po kade ma ist
	private $page; // aktualna stranka
	private $zero = false;
	private $typ = "old";
	private $redir_c = 1;
	public static $use_session = true;
	/**
	 * Tato funkcia vrati iterator daneho mena.
	 *
	 * @param string $id Identifikator iteratoru
	 * @return Iterator Iterator
	 */
	public static function getIterator($id, $per_page = 10, $start_from_zero = true,$typ="old",$redir_c=1){
		static $iterators = array();
		if(!isset($iterators[$id]) 
		  || !array_key_exists($id,$iterators) 
		  || !$iterators[$id]){
			$iterators[$id] = new Iterator($id,$per_page,$start_from_zero,$typ,$redir_c);
		}
		return $iterators[$id];
	}
	
	private function __construct($id,$per_page,$zero,$typ,$redir_c){
		$this->typ = $typ;
		$this->redir_c = $redir_c;
		$this->id = $id;
		$this->zero = $zero;
		$this->check_update();
		$this->set_object_variables($id,$per_page,$zero);
	}
	/**
	 * Tato funkcia vytvori premenne objektu pri vzniku tohoto objektu
	 *
	 */
	private function set_object_variables($id,$per_page,$zero){
		if(!$per_page || !is_numeric($per_page)){
			\AsyncWeb\Text\Messages::getInstance()->warning(\AsyncWeb\System\Language::get("error_iterator_per_page_wrong"),false);
			$per_page = 10;
		}
		if($this->typ == "old" || $this->typ == "jquery"){
			if(Iterator::$use_session){
				
				if(\AsyncWeb\Storage\Session::get("ITER_${id}_ON")){
					// iterator uz existuje
					$this->start_from = \AsyncWeb\Storage\Session::get("ITER_${id}_PAGE")*\AsyncWeb\Storage\Session::get("ITER_${id}_PER_PAGE");
					// ak by sme dostali novu hodnotu per page, tak musime vypocitat na ktorej sme strane.
					\AsyncWeb\Storage\Session::set("ITER_${id}_PAGE", floor($this->start_from / $per_page));
					\AsyncWeb\Storage\Session::set("ITER_${id}_PER_PAGE", $per_page);
					
					if(!$zero) $this->start_from ++;
					$this->end = $this->start_from + $per_page;
					$this->per_page = $per_page;
				}else{
					// musime vytvorit iterator
					$this->start_from = 0;
					if(!$zero) $this->start_from++;
					$this->end = $per_page;
					$this->per_page = $per_page;
					\AsyncWeb\Storage\Session::set("ITER_${id}_PAGE", $this->start_from);
					\AsyncWeb\Storage\Session::set("ITER_${id}_PER_PAGE", $per_page);
					\AsyncWeb\Storage\Session::set("ITER_${id}_ON", true);
				}
				$this->page = \AsyncWeb\Storage\Session::get("ITER_".$this->id."_PAGE");
			}else{
				$this->start_from = $this->page*$per_page;
				if(!$zero) $this->start_from ++;
				$this->end = $this->start_from + $per_page;
				$this->per_page = $per_page;
			}
		}elseif ($this->typ == "redir"){
		 	$this->start_from = $this->page*$per_page;
		 	if(!$zero) $this->start_from ++;
		 	$this->end = $this->start_from + $per_page;
			$this->per_page = $per_page;
		}
		
	}
	/**
	 * Skontroluje, ci bola zmenena strana
	 *
	 */
	private function check_update(){
		// skontroluje ci bola zmena strany
		
		if($this->typ == "old" || $this->typ == "jquery"){
			if(Iterator::$use_session){
				if(isset($_REQUEST["ITER_".$this->id."_PAGE"])){
					\AsyncWeb\Storage\Session::set("ITER_".$this->id."_PAGE", $_REQUEST["ITER_".$this->id."_PAGE"]);
				}
			}else{
				if(isset($_REQUEST["ITER_".$this->id."_PAGE"])) $this->page = $_REQUEST["ITER_".$this->id."_PAGE"];
			}
		}elseif ($this->typ == "redir"){
			$adr = $_SERVER['REQUEST_URI'];
			$adra = explode("/",$adr);
			$this->page = $adra[$this->redir_c];
		}
	}
	
	public function getStart(){
		return $this->start_from;
	}
	/**
	 * Vracia cislo objektu, pred ktorym to ma skoncit.. last + 1
	 *
	 * @return unknown
	 *
	 */
	public function getEnd(){
		return $this->end;
	}
	public function getLast(){
		return $this->end - 1;
	}
	public function getPerPage(){
		return $this->per_page;
	}
	public function getPage(){
		if($this->zero){
			return $this->page;
		}
		return ($this->page+1);
	}
	
	public function getPagesCount($elementsCount){
		return ceil($elementsCount/$this->per_page)+1;
	}
	
	public function reset(){
		$id = $this->id;
		if(Iterator::$use_session) \AsyncWeb\Storage\Session::set("ITER_${id}_PAGE", 0);
		$this->page = 0;
		$this->start_from = 0;
		if(!$this->zero){
			$this->start_from++;
		}
		$this->end = $this->start_from + $this->per_page;
		
	}
	public function show_bar($count, $r = 5,$vars_to_transfer = array(),$id = ""){
		if($this->typ == "old"){
			
			if(!$r)$r = 5;
			$last_page=$this->getPagesCount($count)-1;
			$ret = "\n	<div class=\"ITER_bar";
			if($id){ $ret.= ' '.$id;}
			$ret.="\">\n".\AsyncWeb\System\Language::get("iter_page").":";
			if($this->page != (0)){
				$ret .= ' <span><a href="'.\AsyncWeb\System\Path::make(array('ITER_'.$this->id.'_PAGE'=>"0")).'">&lt;&lt;</a></span>';
			}
			if($this->page != (0)){
				$ret .= ' <span><a href="'.\AsyncWeb\System\Path::make(array('ITER_'.$this->id.'_PAGE'=>($this->page-1))).'">&lt;</a></span>';
			}
			for($i=$this->page-$r;$i<$this->page+$r;$i++){
				if($i<0) continue;
				if($i>=$last_page) break;
				$ret .= '		<span';
				if(($i) == $this->page){
					$ret.= ' class="ITER_actual"';
				}
				$ret .= '><a href="'.\AsyncWeb\System\Path::make(array('ITER_'.$this->id.'_PAGE'=>$i)).'">'.($i+1).'</a></span>'."\n";
			}
			if($this->page != ($last_page-1)){
				$ret .= ' <span><a href="'.\AsyncWeb\System\Path::make(array('ITER_'.$this->id.'_PAGE'=>$this->page+1)).'">&gt;</a></span>';
			}
			if($this->page != ($last_page-1)){
				$ret .= ' <span><a href="'.\AsyncWeb\System\Path::make(array('ITER_'.$this->id.'_PAGE'=>$last_page-1)).'">&gt;&gt;</a></span>';
			}
			$ret .= "	</div>\n";
			return $ret;
		}else
		if($this->typ == "jquery"){
			if(!$r)$r = 5;
			
			$last_page=$this->getPagesCount($count)-1;
			$ret = "\n	<div class=\"ITER_bar";
			if($id){ $ret.= ' '.$id;}
			$ret.="\">\n".\AsyncWeb\System\Language::get("iter_page").":";
			$tid = 'DTTV_tablediv_'.$this->id;
			$ttid = 'TABLE_'.$this->id;
			if($this->page != (0)){
				$ret .= ' <span><a href="'.($p=\AsyncWeb\System\Path::make(array('ITER_'.($this->id).'_PAGE'=>'0'))).'" onclick="'.TSAjax::makeScript($tid,\AsyncWeb\System\Path::make(array("AJAX"=>$ttid,'ITER_'.($this->id).'_PAGE'=>'0'))).'">&lt;&lt;</a></span>';
			}
			if($this->page != (0)){
				$ret .= ' <span><a href="'.($p=\AsyncWeb\System\Path::make(array('ITER_'.($this->id).'_PAGE'=>($this->page-1)))).'" onclick="'.TSAjax::makeScript($tid,\AsyncWeb\System\Path::make(array("AJAX"=>$ttid,'ITER_'.($this->id).'_PAGE'=>($this->page-1)))).'">&lt;</a></span>';
			}
			for($i=$this->page-$r;$i<$this->page+$r;$i++){
				if($i<0) continue;
				if($i>=$last_page) break;
				$ret .= '		<span';
				if(($i) == $this->page){
					$ret.= ' class="ITER_actual"';
				}
				$ret .= '><a href="'.($p=\AsyncWeb\System\Path::make(array('ITER_'.($this->id).'_PAGE'=>($i)))).'" onclick="'.TSAjax::makeScript($tid,\AsyncWeb\System\Path::make(array("AJAX"=>$ttid,'ITER_'.($this->id).'_PAGE'=>($i)))).'">'.($i+1).'</a></span>'."\n";
			}
			if($this->page != ($last_page-1)){
				$ret .= ' <span><a href="'.($p=\AsyncWeb\System\Path::make(array('ITER_'.($this->id).'_PAGE'=>($this->page+1)))).'" onclick="'.TSAjax::makeScript($tid,\AsyncWeb\System\Path::make(array("AJAX"=>$ttid,'ITER_'.($this->id).'_PAGE'=>($this->page+1)))).'">&gt;</a></span>';
			}
			if($this->page != ($last_page-1)){
				$ret .= ' <span><a href="'.($p=\AsyncWeb\System\Path::make(array('ITER_'.($this->id).'_PAGE'=>($last_page-1)))).'" onclick="'.TSAjax::makeScript($tid,\AsyncWeb\System\Path::make(array("AJAX"=>$ttid,'ITER_'.($this->id).'_PAGE'=>($last_page-1)))).'">&gt;&gt;</a></span>';
			}
			$ret .= "	</div>\n";
			
			return $ret;
		}else
		if($this->typ == "redir"){
		
	
			$adra = explode("/",$_SERVER['REQUEST_URI']);
			
			if(!$r)$r = 5;
			$last_page=$this->getPagesCount($count)-1;
			$ret = "\n	<div class=\"ITER_bar\">\nStrana:";
			if($this->page != (0)){
				$adra[$this->redir_c] = "0";
				$src = implode("/",$adra);
				$ret .= ' <span><a href="'.$src.'">&lt;&lt;</a></span>';
			}
			if($this->page != (0)){
				$adra[$this->redir_c] = $this->page-1;
				$src = implode("/",$adra);
				$ret .= ' <span><a href="'.$src.'">&lt;</a></span>';
			}
			for($i=$this->page-$r;$i<$this->page+$r;$i++){
				if($i<0) continue;
				if($i>=$last_page) break;
				$ret .= '		<span';
				if(($i) == $this->page){
					$ret.= ' class="ITER_actual"';
				}
				$adra[$this->redir_c] = $i;
				$src = implode("/",$adra);
				$ret .= '><a href="'.$src.'">'.($i+1).'</a></span>'."\n";
			}
			if($this->page != ($last_page-1)){
				$adra[$this->redir_c] = $this->page+1;
				$src = implode("/",$adra);
				$ret .= ' <span><a href="'.$src.'">&gt;</a></span>';
			}
			if($this->page != ($last_page-1)){
				$adra[$this->redir_c] = $last_page-1;
				$src = implode("/",$adra);
				$ret .= ' <span><a href="'.$src.'">&gt;&gt;</a></span>';
			}
			$ret .= "	</div>\n";
			
			return $ret;

		}

	}
		
}
