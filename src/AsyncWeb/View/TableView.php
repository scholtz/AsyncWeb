<?php
use AsyncWeb\View\ViewConfig;

namespace AsyncWeb\View;

/*
14.1.2015 Zrušený AJAX, aby to bolo kompatibilné s AsyncWeb

15.7.2014 BugFix SelectionDataViewCell ked je rovnaky nazov stlpca ako option, tak vybere stale jednu hodnotu

*/


interface DataViewCell{
	public function show($id,$datarow,$type);
	public function allowFilter();
	public function makeFilterOperant($trid,$key,$selected=null);
	public function generateFilterBox($key);
	public function setTHViewCell($THViewCell);
	public function getClass();
	public function getTableId();
	public function setInputFilter($inputFilter);
	public function modifyFilter(&$filter,$DataFilterItem);
}
interface THViewCell{
	public function getKey();
	public function getName();
	public function getTitle();
	public function getIsSorted();
	
	public function getIsDisplayable();
	public function getIsExportable();
	public function getIsSortable();
	public function getIsFilterable();
	
	public function show($islast,$type);
	public function getDVC();
	public function setTHDataViewRow($THDataViewRow);
	public function getTHDataViewRow();
	public function getTableId();
	public function getTableView();
	
}
class BasicDataViewCell implements DataViewCell{
	public $datatype = "string";
	protected $THViewCell = null;
	protected $myclass = "";
	protected $inputFilter = null;
	public function setTHViewCell($THViewCell){
		$this->THViewCell = $THViewCell;
	}
	public function getTableId(){
		if($this->THViewCell) return $this->THViewCell->getTableId();
		return null;
	}
	public function show($id,$datarow,$type="display"){
		$ret = $id;
		if(is_array($datarow) && array_key_exists($id,$datarow)){
			$ret=$datarow[$id];
		}
		if($this->inputFilter){
			$ret = $this->inputFilter->filter($ret,$datarow);
		}
		return $ret;
	}
	public function allowFilter(){
		return array(
			DV_OP_LIKE,
			DV_OP_NOT_LIKE,
			DV_OP_EQUAL,
			DV_OP_NOT_EQUAL,
			DV_OP_IS,
			DV_OP_IS_NOT,
		);
	}
	protected function makeFilterValue($trid,$key,$default=null){
		$DataTableId = $this->getTableId();
		$ret= '<input title="'.\AsyncWeb\System\Language::get("Podmienka").'" id="FilterValue_'.$trid.'" name="FilterValue['.$DataTableId.']['.$key.']['.$trid.']" value="'.$default.'" onchange="$(\'#ch'.$trid.'\').attr(\'checked\', true);" />';
		return $ret;
	}
	public function makeFilterOperant($trid,$key,$selected=null){
		$DataTableId = $this->getTableId();
		$ret= '<select title="'.\AsyncWeb\System\Language::get("Operátor").'" id="FilterOperant_'.$trid.'" name="FilterOperant['.$DataTableId.']['.$key.']['.$trid.']" onchange="$(\'#ch'.$trid.'\').attr(\'checked\', true);">';
		foreach($this->allowFilter() as $filterType){
			switch($filterType){
				case DV_OP_EQUAL: $ret.= '<option value="'.$filterType.'"';if($selected==$filterType)$ret.=' selected="selected"';$ret.='>=</option>';break;
				case DV_OP_NOT_EQUAL: $ret.= '<option value="'.$filterType.'"';if($selected==$filterType)$ret.=' selected="selected"';$ret.='>!=</option>';break;
				case DV_OP_LIKE: $ret.= '<option value="'.$filterType.'"';if($selected==$filterType)$ret.=' selected="selected"';$ret.='>'.\AsyncWeb\System\Language::get("Obsahuje").'</option>';break;
				case DV_OP_NOT_LIKE: $ret.= '<option value="'.$filterType.'"';if($selected==$filterType)$ret.=' selected="selected"';$ret.='>'.\AsyncWeb\System\Language::get("Neobsahuje").'</option>';break;
				case DV_OP_IS: $ret.= '<option value="'.$filterType.'"';if($selected==$filterType)$ret.=' selected="selected"';$ret.='>'.\AsyncWeb\System\Language::get("Nevyplnená hodnota").'</option>';break;
				case DV_OP_IS_NOT: $ret.= '<option value="'.$filterType.'"';if($selected==$filterType)$ret.=' selected="selected"';$ret.='>'.\AsyncWeb\System\Language::get("Vyplnená hodnota").'</option>';break;
				case DV_OP_GT: $ret.= '<option value="'.$filterType.'"';if($selected==$filterType)$ret.=' selected="selected"';$ret.='>&gt;</option>';break;
				case DV_OP_GTEQ: $ret.= '<option value="'.$filterType.'"';if($selected==$filterType)$ret.=' selected="selected"';$ret.='>&gt;=</option>';break;
				case DV_OP_LT: $ret.= '<option value="'.$filterType.'"';if($selected==$filterType)$ret.=' selected="selected"';$ret.='>&lt;</option>';break;
				case DV_OP_LTEQ: $ret.= '<option value="'.$filterType.'"';if($selected==$filterType)$ret.=' selected="selected"';$ret.='>&lt;=</option>';break;
			}
		}
		$ret.="</select>";
		return $ret;
	}
	protected function generateEmptyFilterRow($key){
		$DataTableId = $this->getTableId();
		$ret="";
		$ret .= '<tr id="tr%trid%"><td><input title="'.\AsyncWeb\System\Language::get("Použi podmienku").'" name="FilterCH['.$DataTableId.']['.$key.'][%trid%]" id="ch%trid%" type="checkbox" />';
		$ret.='<script type="text/javascript">$("#ch%trid%").change(function() {if($(this).is(\\\':checked\\\')){$(\\\'#FilterValue_%trid%\\\').prop(\\\'disabled\\\', false);$(\\\'#FilterOperant_%trid%\\\').prop(\\\'disabled\\\', false);}else{$(\\\'#FilterValue_%trid%\\\').prop(\\\'disabled\\\', true);$(\\\'#FilterOperant_%trid%\\\').prop(\\\'disabled\\\', true);}});</script>';
		$ret.='</td><td>'.addslashes($this->makeFilterOperant('%trid%',$key)).'</td><td>'.addslashes($this->makeFilterValue('%trid%',$key)).'</td><td><img class="clickable" onclick="$(\\\'#tr%trid%\\\').remove();" src="/img/delete.png" width="20" height="20" alt="X" /></td></tr>';
		
		$ret = htmlentities($ret,ENT_QUOTES|ENT_HTML401,"UTF-8");
		$ret = str_replace("%trid%","'+trid+'",$ret);
		return $ret;
	}
	public function generateFilterBox($key){	
		$DataTableId = $this->getTableId();
		$ret = '<form onsubmit="return false;" id="form_'.($DataTableId).'"><table class="filterbox" id="'.($tid=md5(uniqid())).'">';
		$filter = \AsyncWeb\Storage\Session::get("DV_filter");
		
		if(!$filter) $filter = array();
		$i = 0;
		if(isset($filter[$DataTableId])){
			foreach($filter[$DataTableId] as $k=>$rows){
				if($k != $key) continue;
				foreach($rows as $row){
					$i++;
						$ret .= '<tr id="tr'.($trid=md5(uniqid())).'"><td><input title="'.\AsyncWeb\System\Language::get("Použi podmienku").'" checked="checked" name="FilterCH['.$DataTableId.']['.$key.']['.$trid.']" id="ch'.$trid.'" type="checkbox" />';
						$ret.= '<script type="text/javascript">$("#ch'.$trid.'").change(function() {if($(this).is(\':checked\')){$(\'#FilterValue_'.$trid.'\').prop(\'disabled\', false);$(\'#FilterOperant_'.$trid.'\').prop(\'disabled\', false);}else{$(\'#FilterValue_'.$trid.'\').prop(\'disabled\', true);$(\'#FilterOperant_'.$trid.'\').prop(\'disabled\', true);}});</script>';
						$ret.= '</td><td>'.$this->makeFilterOperant($trid,$key,$row["op"]).'</td><td>'.$this->makeFilterValue($trid,$key,$row["value"]).'</td><td><img class="clickable" onclick="$(\'#tr'.$trid.'\').remove();" src="/img/delete.png" width="20" height="20" alt="X" /></td></tr>';
				}
			}
		}
		if(!$i){
			$ret .= '<tr id="tr'.($trid = md5(uniqid())).'"><td><input title="'.\AsyncWeb\System\Language::get("Použi podmienku").'" name="FilterCH['.$DataTableId.']['.$key.']['.$trid.']" id="ch'.$trid.'" type="checkbox" />';
			$ret.= '<script type="text/javascript">$("#ch'.$trid.'").change(function() {if($(this).is(\':checked\')){$(\'#FilterValue_'.$trid.'\').prop(\'disabled\', false);$(\'#FilterOperant_'.$trid.'\').prop(\'disabled\', false);}else{$(\'#FilterValue_'.$trid.'\').prop(\'disabled\', true);$(\'#FilterOperant_'.$trid.'\').prop(\'disabled\', true);}});</script>';
			$ret.= '</td><td>'.$this->makeFilterOperant($trid,$key).'</td><td>'.$this->makeFilterValue($trid,$key).'</td><td><img class="clickable" onclick="$(\'#tr'.$trid.'\').remove();" src="/img/delete.png" width="20" height="20" alt="X" /></td></tr>';
		}
		$ret .= '<tr><td></td><td><input style="width:100%; min-width:150px" type="submit" value="'.\AsyncWeb\System\Language::get("Nová podmienka").'" onclick="trid = Math.round(Math.random()*100000); $(\'#'.$tid.' tr:last\').before(\''.$this->generateEmptyFilterRow($key).'\');return false;"  /></td><td><input type="submit" value="'.\AsyncWeb\System\Language::get("Aplikuj filter").'" onclick="'.TSAjax::applyFilterScript($key,$DataTableId).'" style="width:100%; min-width:150px"/></td><td colspan="10"></td></tr>';
		$ret.= '</table>';
		return $ret;
		
	}
	public function getClass(){
		return $this->myclass;
	}
	public function setInputFilter($inputFilter){
		if(is_a($inputFilter,"\\AsyncWeb\\View\\InputFilter")){
			$this->inputFilter = $inputFilter;
		}
	}
	public function modifyFilter(&$filter,$DataFilterItem){
		$filter[] = $DataFilterItem;
	}
}
class DBValueDataViewCell extends BasicDataViewCell{
	private $exttable;
	private $extcol;
	private $dictionary;
	private $where = array();
	public function __construct($exttable,$extcol,$dictionary=false,$where=array()){
		$this->exttable = $exttable;
		$this->extcol = $extcol;
		$this->dictionary = $dictionary;
		if(is_array($where)){
			$this->where = $where;
		}
	}
	public function show($id,$datarow,$type="display"){
		$cols=array();
		$ret = "";
		if(is_array($this->extcol)){
			foreach($this->extcol as $col){
				if($col["type"] == "col") $cols[] = $col["value"];
			}
		}else{
			$cols[] = $this->extcol;
		}
		$where = array();
		$where[] = array("col"=>"id2","op"=>"eq","value"=>$datarow[$id]);
		
		
		foreach($this->where as $k=>$v){
			if(!is_numeric($k)){
				$where[$k] = $v;
			}else{
				$where[] = $v;
			}
		}
		$r = \AsyncWeb\DB\DB::qbr($this->exttable,array("where"=>$where,"cols"=>$cols));
		if($r){
			if(is_array($this->extcol)){
				$ret = "";
				foreach($this->extcol as $col){
					if($col["type"] == "col"){
						$ret .= $datarow[$col["value"]];
					}elseif($col["type"] == "data"){
						$ret .= $col["value"];
					}
				}
				if($this->dictionary) $ret = \AsyncWeb\System\Language::get($ret);
				
			}else{
				$ret=$r[$this->extcol];
				if($this->dictionary) $ret = \AsyncWeb\System\Language::get($ret);
				
			}
		}
		if($this->inputFilter) $ret = $this->inputFilter->filter($ret,$datarow);
		return $ret;
	}
	public function allowFilter(){
		return array(
			DV_OP_EQUAL,
			DV_OP_NOT_EQUAL,
			DV_OP_LIKE,
			DV_OP_NOT_LIKE,
			DV_OP_IS,
			DV_OP_IS_NOT,
		);
	}
}
class SelectionDataViewCell extends BasicDataViewCell{
	private $options = array();
	
	public function __construct($options){
		if(is_array($options)) $this->options = $options;
	}
	public function show($id,$datarow,$type="display"){
		//var_dump($id);
		//var_dump($datarow);
		//var_dump($this->options);
		//var_dump($this->options);
		//exit;
		$ret = $id;
		if(array_key_exists($id,$datarow)){
			$ret = $datarow[$id];
		}
		//var_dump($ret);
		if(array_key_exists($ret,$this->options)){
			return $this->options[$ret];
		}
		//var_dump($ret);
		//if(array_key_exists($id,$this->options)){
			//return $this->options[$id];
		//}
		if($this->inputFilter) $ret = $this->inputFilter->filter($ret,$datarow);
		//var_dump($ret);exit;
		return $ret;
	}
	public function allowFilter(){
		return array(
			DV_OP_EQUAL,
			DV_OP_NOT_EQUAL,
			DV_OP_LIKE,
			DV_OP_NOT_LIKE,
			DV_OP_IS,
			DV_OP_IS_NOT,
		);
	}
}
class DateDataViewCell extends BasicDataViewCell{
	public $datatype = "string";
	protected $format = "d.m.Y";
	public function __construct($format = "d.m.Y"){
		$this->format = $format;
		\AsyncWeb\HTML\Headers::add_script(null,"/js/date.js");
	}
	public function show($id,$datarow,$type="display"){
		$ret = $id;
		if(is_array($datarow) && array_key_exists($id,$datarow) && is_numeric($datarow[$id])){
			if($datarow[$id]) return date($this->format,$datarow[$id]);
			$ret="";
		}
		if(array_key_exists($id,$datarow)){
			$ret= $datarow[$id];
		}
		if($this->inputFilter) $ret = $this->inputFilter->filter($ret,$datarow);
		return $ret;
	}
	public function allowFilter(){
		return array(
			DV_OP_EQUAL,
			DV_OP_GTEQ,
			DV_OP_LTEQ,
			DV_OP_IS,
			DV_OP_IS_NOT,
		);
	}
	protected function makeFilterValue($trid,$key,$default=null){
		$DataTableId = $this->getTableId();
		if(!$default) $default = date("d.m.Y");
		$ret= '<input title="'.\AsyncWeb\System\Language::get("Podmienka").'" id="FilterValue_'.$trid.'" name="FilterValue['.$DataTableId.']['.$key.']['.$trid.']" value="'.$default.'" onchange="$(\'#ch'.$trid.'\').attr(\'checked\', true);" /></td><td>';
		
		if(ViewConfig::$useFontAwesome){
			$ret.='<i class="fa fa-calendar"></i>';
		}elseif(\AsyncWeb\IO\File::exists("img/icons/calendar.png")){
			$ret.='<a class="clickable button" onclick="select_date(\'FilterValue_'.$trid.'\');return false;"><img src="/img/icons/calendar.png" alt="'.\AsyncWeb\System\Language::get("Vyberte dátum").'" width="20" height="20" /></a>';
		}else{
			$ret.=\AsyncWeb\System\Language::get("Vyberte dátum");
		}
		return $ret;
	}
	public function modifyFilter(&$filter,$DataFilterItem){
		
		if($DataFilterItem->getOperator() == DV_OP_EQUAL){
			$col = $DataFilterItem->getCol();
			$time = \AsyncWeb\Date\Time::get(strtotime($DataFilterItem->getValue()));
			if($time>0){
				$date = getdate(\AsyncWeb\Date\Time::getUnix($time));
				$from = mktime(0,0,0,$date["mon"],$date["mday"],$date["year"]);
				$to = mktime(0,0,0,$date["mon"],$date["mday"]+1,$date["year"]);
				$filter[] = new DataFilterItem($col,DV_OP_GTEQ,$from,DV_BINDING_AND);
				$filter[] = new DataFilterItem($col,DV_OP_LT,$to,DV_BINDING_AND);
			}else{
				\AsyncWeb\Text\Msg::err(\AsyncWeb\System\Language::get("Wrong date format in filter"));
			}
		}else{
			$time = \AsyncWeb\Date\Time::get(strtotime($DataFilterItem->getValue()));
			if($time > 0){
				$filter[] = new DataFilterItem($DataFilterItem->getCol(),$DataFilterItem->getOperator(),$time,$DataFilterItem->getBinding());
			}else{
				\AsyncWeb\Text\Msg::err(\AsyncWeb\System\Language::get("Wrong date format in filter"));
			}
		}		
	}
}
class NumberDataViewCell extends BasicDataViewCell{
	public $datatype = "number";
	private $NumberFormatter = null;
	public function __construct($NumberFormatter=null,$class="number"){
		if(is_a($NumberFormatter,"\\AsyncWeb\\View\\NumberFormatter")){
			$this->NumberFormatter = $NumberFormatter;
		}
		$this->myclass = $class;
	}
	public function show($id,$datarow,$type="display"){
		$ret = $id;
		if(is_array($datarow) && array_key_exists($id,$datarow)){
			$ret= $datarow[$id];
		}
		if(!$ret) $ret = 0;
		if($this->NumberFormatter) $ret = $this->NumberFormatter->format($ret);
		if($this->inputFilter) $ret = $this->inputFilter->filter($ret,$datarow);
		return $ret;
	}
	public function allowFilter(){
		return array(
			DV_OP_EQUAL,
			DV_OP_GT,
			DV_OP_GTEQ,
			DV_OP_LT,
			DV_OP_LTEQ,
			DV_OP_NOT_EQUAL,
			DV_OP_IS,
			DV_OP_IS_NOT,
		);
	}
}
class CheckBoxDataViewCell extends BasicDataViewCell{
	private $options = array();
	private $optionsb = array();
	public function __construct($options=array()){
		$this->options = $options;
		foreach($options as $k=>$v){
			$this->optionsb[$v] = $k;
		}
	}
	public $datatype = "boolean";
	public function show($id,$datarow,$type="display"){
		$ret = $id;
		if(is_array($datarow) && array_key_exists($id,$datarow)){
			$ret= '<input disabled="disabled" type="checkbox" ';
			if(array_key_exists($datarow[$id],$this->optionsb)){
				if($this->optionsb[$datarow[$id]]) $ret.=' checked="checked"';
			}elseif(array_key_exists($datarow[$id],$this->options)){
				if($this->options[$datarow[$id]]) $ret.=' checked="checked"';
			}else{
				if($datarow[$id]) $ret.=' checked="checked"';
			}
			$ret.='/>';
		}
		if($this->inputFilter) $ret = $this->inputFilter->filter($ret,$datarow);
		return $ret;
	}
	public function allowFilter(){
		return array(
			DV_OP_EQUAL,
			DV_OP_NOT_EQUAL,
			DV_OP_IS,
			DV_OP_IS_NOT,
		);
	}
}
class FunctionItemDataViewCell extends BasicDataViewCell{
	protected $myclass = "function";
	protected $text = "";
	protected $href = "";
	protected $vars = array();
	protected $icon = null;
	protected $requiresconfirmation = "function";
	
	public function __construct($text,$href,$vars=array(),$icon=null,$requiresconfirmation=false){
		$this->text = $text;
		$this->href = $href;
		$this->vars = $vars;
		$this->icon = $icon;
		$this->requiresconfirmation = $requiresconfirmation;
	}
	public function show($id,$datarow,$type="display"){
		$ret = "";
		$confirm = '';
		$text = $this->text;
		$href = $this->href;
		$requiresconfirmation = $this->requiresconfirmation;
		
		foreach($this->vars as $k=>$v){
			if($k=="uTableId%"){
				$text = str_replace($k,$this->getTableId(),$text);
				$text = str_replace(urlencode($k),urlencode($this->getTableId()),$text);
				$href = str_replace($k,$this->getTableId(),$href);
				$href = str_replace(urlencode($k),urlencode($this->getTableId()),$href);
				$requiresconfirmation = str_replace($k,$this->getTableId(),$requiresconfirmation);
				$requiresconfirmation = str_replace(urlencode($k),urlencode($this->getTableId()),$requiresconfirmation);
			}else{
				if(isset($datarow[$v])){
					$text = str_replace($k,$datarow[$v],$text);
					$text = str_replace(urlencode($k),urlencode($datarow[$v]),$text);
				}
				if(isset($datarow[$v])){
					$href = str_replace($k,$datarow[$v],$href);
					$href = str_replace(urlencode($k),urlencode($datarow[$v]),$href);
				}
				if(isset($datarow[$v])){
					$requiresconfirmation = str_replace($k,$datarow[$v],$requiresconfirmation);
					$requiresconfirmation = str_replace(urlencode($k),urlencode($datarow[$v]),$requiresconfirmation);
				}
			}
		}
		if($k = "%getTableId%"){
				$text = str_replace($k,$this->getTableId(),$text);
				$text = str_replace(urlencode($k),urlencode($this->getTableId()),$text);
				$href = str_replace($k,$this->getTableId(),$href);
				$href = str_replace(urlencode($k),urlencode($this->getTableId()),$href);
				$requiresconfirmation = str_replace($k,$this->getTableId(),$requiresconfirmation);
				$requiresconfirmation = str_replace(urlencode($k),urlencode($this->getTableId()),$requiresconfirmation);
		}
		if($requiresconfirmation) $confirm = ' onclick="confirm(\''.$requiresconfirmation.'\')?ret=true:ret=false;return ret;"';
		$ret.='<a'.$confirm.' href="'.$href.'">';
		if($this->icon){
		
			if(ViewConfig::$useFontAwesome){
				switch($this->icon){
					case 'img/icons/blog_compose.png': $ret.= '<i class="fa fa-pencil-square-o"></i>';break;
					case 'img/delete.png': $ret.= '<i class="fa fa-times"></i>';break;
				}
			}else{
				if(\AsyncWeb\IO\File::exists($this->icon)){$ret.='<img src="/'.$this->icon.'" width="20" height="20" alt="'.$text.'" />';}else{$ret.=$text;}$ret.='</a>';
			}
		}else{
			$ret.=$text;
		}
		if($this->inputFilter) $ret = $this->inputFilter->filter($ret,$datarow);
		return $ret;
	}
	public function allowFilter(){
		return array();
	}
}
class UpdateItemDataViewCellV1 extends BasicDataViewCell{
	protected $myclass = "function";
	public function show($id,$datarow,$type="display"){
		$ret = "";
		$ret.='<a href="'.\AsyncWeb\System\Path::make(array($this->getTableId()."___ID"=>$datarow["id"],$this->getTableId()."___UPDATE1"=>"1","AJAX"=>"")).'">';
		if(ViewConfig::$useFontAwesome){
			$ret.='<i class="fa fa-plus-circle"></i>';
		}elseif(\AsyncWeb\IO\File::exists("img/icons/blog_compose.png")){$ret.='<img src="/img/icons/blog_compose.png" width="20" height="20" alt="'.\AsyncWeb\System\Language::get("Upraviť záznam").'" />';}else{$ret.=\AsyncWeb\System\Language::get("Upraviť záznam");}$ret.='</a>';
		if($this->inputFilter) $ret = $this->inputFilter->filter($ret,$datarow);
		return $ret;
	}
	public function allowFilter(){
		return array();
	}
}
class UpdateItemDataViewCell extends FunctionItemDataViewCell{
	public function __construct(){

		parent::__construct(
			\AsyncWeb\System\Language::get("Upraviť záznam"),
			\AsyncWeb\System\Path::make(array("%getTableId%___ID"=>"%id%","%getTableId%___UPDATE1"=>"1","AJAX"=>"")),
			array("%id%"=>"id","%getTableId%"=>"%getTableId%"),
			"img/icons/blog_compose.png",
			false);
	}
}
class DeleteItemDataViewCellV1 extends BasicDataViewCell{
	protected $myclass = "function";
	public function show($id,$datarow,$type="display"){
		$ret = "";
		$ret.='<a onclick="confirm(\''.\AsyncWeb\System\Language::get("confirm delete").'\')?ret=true:ret=false;return ret;" href="'.\AsyncWeb\System\Path::make(array($this->getTableId()."___ID"=>$datarow["id"],$this->getTableId()."___DELETE"=>"1","AJAX"=>"")).'">';
		if(ViewConfig::$useFontAwesome){
			$ret.='<i title="'.\AsyncWeb\System\Language::get("delete").'" class="fa fa-times"></i>';
		}elseif(\AsyncWeb\IO\File::exists("img/delete.png")){$ret.='<img src="/img/delete.png" width="20" height="20" alt="'.\AsyncWeb\System\Language::get("delete").'" />';}else{$ret.=\AsyncWeb\System\Language::get("delete ");}$ret.='</a>';
		if($this->inputFilter) $ret = $this->inputFilter->filter($ret,$datarow);
		return $ret;
	}
	public function allowFilter(){
		return array();
	}
}
class DeleteItemDataViewCell extends FunctionItemDataViewCell{
	public function __construct(){
		parent::__construct(
			\AsyncWeb\System\Language::get("delete"),
			\AsyncWeb\System\Path::make(array("%getTableId%___ID"=>"%id%","%getTableId%___DELETE"=>"1","AJAX"=>"")),
			array("%id%"=>"id","%getTableId%"=>"%getTableId%"),
			"img/delete.png",
			\AsyncWeb\System\Language::get("confirm delete"));
	}
}
class TextDataViewCell extends BasicDataViewCell{
	public $datatype = "text";
	public function show($id,$datarow,$type="display"){
		$ret = $id;
		if(is_array($datarow) && array_key_exists($id,$datarow)){
			$ret = $datarow[$id];
		}
		if($this->inputFilter) $ret = $this->inputFilter->filter($ret,$datarow);
		return $ret;
	}
	public function allowFilter(){
		return array(
			DV_OP_LIKE,
			DV_OP_NOT_LIKE,
		);
	}
}
class THViewCellConfig{
	protected $key = null;
	public function getKey(){
		return $this->key;
	}
	public function setKey($arg){
		if(is_string($arg)){
			$this->key = $arg;
			return true;
		}
		return false;
	}
	protected $name = null;
	public function getName(){
		return $this->name;
	}
	public function setName($arg){
		if(is_string($arg)){
			$this->name = $arg;
			$this->title = $arg;
			return true;
		}
		return false;
	}
	protected $title = null;
	public function getTitle(){
		return $this->title;
	}
	public function setTitle($arg){
		if(is_string($arg)){
			$this->title = $arg;
			return true;
		}
		return false;
	}
	protected $display = null;
	public function getDisplay(){
		return $this->display;
	}
	public function setDisplay($arg){
		if(is_bool($arg)){
			$this->display = $arg;
			$this->exportable = $arg;
			return true;
		}
		return false;
	}
	protected $sortable = null;
	public function getSortable(){
		return $this->sortable;
	}
	public function setSortable($arg){
		if(is_bool($arg)){
			$this->sortable = $arg;
			return true;
		}
		return false;
	}
	protected $filterable = null;
	public function getFilterable(){
		return $this->filterable;
	}
	public function setFilterable($arg){
		if(is_bool($arg)){
			$this->filterable = $arg;
			return true;
		}
		return false;
	}
	protected $priority = 1;
	public function getPriority(){
		return $this->priority;
	}
	public function setPriority($arg){
		if(is_bool($arg)){
			$this->priority = $arg;
			return true;
		}
		return false;
	}
	protected $exportable = null;
	public function getExportable(){
		return $this->exportable;
	}
	public function setExportable($arg){
		if(is_bool($arg)){
			$this->exportable = $arg;
			return true;
		}
		return false;
	}
	protected $dvc = null;
	public function getDVC(){
		return $this->dvc;
	}
	public function setDVC($arg){
		if(is_a($arg,"\\AsyncWeb\\View\\DataViewCell")){
			$this->dvc = $arg;
			return true;
		}
		return false;
	}
	public function __construct(){
		$args = func_get_args();
		while(($arg = array_shift($args)) !== null){
			if(is_string($arg)){
				if($this->key === null){$this->key = $arg;continue;}
				if($this->name === null){$this->name = $arg;continue;}
				if($this->title === null){$this->title = $arg;continue;}
			}
			if(is_bool($arg)){	
				if($this->display === null){$this->display = $arg;continue;}
				if($this->sortable === null){$this->sortable = $arg;continue;}
				if($this->filterable === null){$this->filterable = $arg;continue;}
				if($this->exportable === null){$this->exportable = $arg;continue;}
			}
			if(is_a($arg,"\\AsyncWeb\\View\\DataViewCell")){
				$this->dvc = $arg;continue;
			}
		}
		if($this->name === null) $this->name = $this->key;
		if($this->title === null) $this->title = $this->name;
		if($this->display === null) $this->display = true;
		if($this->sortable === null) $this->sortable = true;
		if($this->filterable === null) $this->filterable = true;
		if($this->exportable === null) $this->exportable = $this->display;
		if(!$this->dvc) $this->dvc = new BasicDataViewCell();
	}
}
class BasicTHViewCell implements THViewCell{
	protected $config;
	
	public function getTableId(){
		if($this->THDataViewRow){
			$TableView = $this->THDataViewRow->getTableView();
			if($TableView) return $TableView->getId();
			return null;
		}
	}
	public function __construct(){
		$i = 0;
		$this->config = new THViewCellConfig();
		$args = func_get_args();		
		while(($arg = array_shift($args)) !== null){
			if(is_a($arg,"\\AsyncWeb\\View\\THViewCellConfig")){
				$this->config = $arg;
			}elseif(is_a($arg,"\\AsyncWeb\\View\\DataViewCell")){
				$this->config->setDVC($arg);
			}else{
				$i++;
				if($i==1){
					$this->config->setKey($arg);
				}
				if($i==2){
					$this->config->setName($arg);
				}
				if($i==3){
					$this->config->setTitle($arg);
				}
				if($i==4){
					$this->config->setSortable($arg);
				}
				if($i==5){
					$this->config->setFilterable($arg);
				}
				if($i==6){
					$this->config->setKey($arg);
				}
				if($i==7){
					$this->config->setPriority($arg);
				}
			}
		}
		if($this->config->getDVC()){$this->config->getDVC()->setTHViewCell($this);}
	}
	protected $THDataViewRow;
	public function setTHDataViewRow($THDataViewRow){
		$this->THDataViewRow = $THDataViewRow;
	}
	public function getTableView(){
		if($this->THDataViewRow){
			return $this->THDataViewRow->getTableView();
		}
		return null;
	}
	public function getTHDataViewRow(){
		return $this->THDataViewRow;
	}
	public function getKey(){
		return $this->config->getKey();
	}
	public function getName(){
		return $this->config->getName();
	}
	public function getTitle(){
		return $this->config->getTitle();
	}
	private $isSorted = false;
	public function getIsSorted(){
		return $this->isSorted;
	}
	public function getIsDisplayable(){
		return $this->config->getDisplay();
	}
	public function getIsExportable(){
		return $this->config->getExportable();
	}
	public function getIsSortable(){
		return $this->config->getSortable();
	}
	public function setIsSortable($v){
		$this->config->setSortable($v);
	}
	
	public function getIsFilterOn(){
		$filter = \AsyncWeb\Storage\Session::get("DV_filter");
		if(!$filter) $filter = array();
		$form = $this->getTableId();
		$key = $this->getKey();
		$ret = false;
		if(isset($filter[$form][$key])) $ret=  (count($filter[$form][$key]) > 0);
		
		return $ret;
	}
	
	public function getIsFilterable(){
		return $this->config->getFilterable();
	}
	public function setIsFilterable($v){
		$this->config->setFilterable($v);
	}
	public function setIsSorted($sortType){
		if($sortType != SORT_ASC && $sortType != SORT_DESC) throw new \Exception("Wrong sort type supplied");
		$this->isSorted = $sortType;
	}
	public function show($islast=false,$type="display"){
		$parentid = $this->getTableId();
		\AsyncWeb\HTML\Headers::add_link("/css/datatable.css","stylesheet","text/css");
		$dir = "asc";
		if($this->isSorted == SORT_ASC) $dir = "desc";
		$ret= '<th title="'.htmlspecialchars($this->config->getTitle(),ENT_COMPAT,"UTF-8").'" data-priority="'.$this->config->getPriority().'">';
		if($this->config->getName()){
			$ret.='<a href="'.\AsyncWeb\System\Path::make(array('sort_'.($parentid)=>md5($this->config->getKey()),"dir"=>$dir,"AJAX"=>"")).'" onclick="'.TSAjax::makeScript('DTTV_tablediv_'.($parentid)."",\AsyncWeb\System\Path::make(array('sort_'.($parentid)=>md5($this->config->getKey()),"dir"=>$dir,'AJAX'=>'TABLE_'.$parentid))).'" class="button">'.$this->config->getName();
			if($this->config->getSortable()){
				if($this->isSorted == SORT_ASC){
					if(ViewConfig::$useFontAwesome){
						$ret.= ' <i class="fa fa-sort-alpha-asc"></i>';
					}else{
						$ret.=' ▲';
					}
				}
				if($this->isSorted == SORT_DESC){
					if(ViewConfig::$useFontAwesome){
						$ret.= ' <i class="fa fa-sort-alpha-desc"></i>';
					}else{
						$ret.=' ▼';
					}
				}
			}
			$ret.='</a>';
		}
		if($this->config->getFilterable()){
			$icon = "F";
			if(ViewConfig::$useFontAwesome){
				$icon = '<i class="fa fa-filter"></i>';
			}elseif(\AsyncWeb\IO\File::exists("img/icons/filter.png")){
				$icon = '<img src="/img/icons/filter.png" width="20" height="20" alt=" '.\AsyncWeb\System\Language::get("Filter").'" title="'.\AsyncWeb\System\Language::get("Filter").'" />';
			}
			if($this->getIsFilterOn()){
				if(ViewConfig::$useFontAwesome){
					$icon = '<i class="fa fa-filter"></i>';
				}elseif(\AsyncWeb\IO\File::exists("img/icons/filter_on.png")){
					$icon = '<img src="/img/icons/filter_on.png" width="20" height="20" alt=" '.\AsyncWeb\System\Language::get("Filter").'" title=" '.\AsyncWeb\System\Language::get("Filter is on").'" />';
				}
			}
			$ret.='<a id="filter_'.$this->config->getKey().'" onclick="'.TSAjax::makeFilterScript($this).'" class="button clickable">'.$icon.'</a><span id="filterbox_'.$this->getTableId().'_'.$this->config->getKey().'" class="filterboxdiv">'.TSAjax::makeFilterScript($this,true).'</span>';
		}
		if($islast){
			if(count($this->getTableView()->getTableMenuItems()) > 0){
				$icon = \AsyncWeb\System\Language::get("Menu");
				if(ViewConfig::$useFontAwesome){
					$icon = '<i class="fa fa-folder"></i>';
				}elseif(\AsyncWeb\IO\File::exists("img/icons/folder.png")){
					$icon = '<img src="/img/icons/folder.png" width="20" height="20" alt=" '.\AsyncWeb\System\Language::get("Menu").'" title="'.\AsyncWeb\System\Language::get("Table menu").'" />';
				}
				
				$ret.='<a id="menuboxicon_'.$parentid.'" onclick="'.TSAjax::makeTableMenuScript($this).'"  class="clickable float_right button" title="'.\AsyncWeb\System\Language::get("Table menu").'">'.$icon.'</a>'.'<div class="menuboxbox" id="menuboxbox_'.$parentid.'">'.TSAjax::makeTableMenuScript($this,true).'</div>';
			}
		}
		$ret.='</th>';
		return $ret;
	}
	public function getDVC(){
		return $this->config->getDVC();
	}
}
interface DataViewRow{
	public function show($datarow);
	public function setTableView($TableView);
	public function getDVC($key);
}
class BasicDataViewRow implements DataViewRow{
	protected $cells = array();
	protected $basicDVC;
	protected $TableView = null;
	public function setTableView($TableView){
		$this->TableView = $TableView;
	}
	public function __construct($cellsconfig = array(),$basicDVC=null){
		if(is_array($cellsconfig)){
			foreach($cellsconfig as $k=>$v){
				if($v && is_a($v,"\\AsyncWeb\\View\\DataViewCell")){
					$this->cells[$k] = $v;
				}
			}
		}
		if($basicDVC && is_a($basicDVC,"\\AsyncWeb\\View\\DataViewCell")){
			$this->basicDVC = $basicDVC;
		}else{
			$this->basicDVC = new BasicDataViewCell();
		}
	}
	public function setCellConfig($key,$dataViewCell){
		if($dataViewCell && is_a($basicDVC,"\\AsyncWeb\\View\\DataViewCell")){
			$this->cells[$key] = $dataViewCell;
			return true;
		}
		return false;
	}
	public function getDVC($key){
		if(isset($this->cells[$key])) return $this->cells[$key];
		return false;
	}
	
	public function show($datarow){
		$ret = "";
		foreach($datarow as $k=>$v){
			if(isset($this->cells[$k])){
				$ret.= $this->cells[$k]->show($k,$datarow);
			}else{
				$ret.=$this->basicDVC->show($k,$datarow);
			}
		}
		return $ret;
	}
}
class THDataViewRow{
	private $cols = array();
	public function count(){
		return count($this->cols);
	}
	protected $TableView = null;
	public function setTableView($TableView){
		$this->TableView = $TableView;
	}
	public function getTableView(){
		return $this->TableView;
	}
	public function __construct(){
		//var_dump("new THDataViewRow");
		$args = func_get_args();
		//var_dump($args);
		while(($arg = array_shift($args)) !== null){
			if(is_a($arg,"\\AsyncWeb\\View\\THViewCell")){
				if($arg->getKey()){
					$this->cols[$arg->getKey()] = $arg;
				}else{
					$this->cols[] = $arg;
				}
				//echo 'setting $arg->setTHDataViewRow($this);'."\n";
				$arg->setTHDataViewRow($this);
			}
			if(is_array($arg)){			
				foreach($arg as $k=>$v){
					if(is_a($v,"\\AsyncWeb\\View\\THViewCell")){
						if($v->getKey()){
							$this->cols[$v->getKey()] = $v;
						}else{
							$this->cols[] = $v;
						}
						//echo 'setting $v->setTHDataViewRow($this);'."\n";
						$v->setTHDataViewRow($this);
					}
				}
			}
		}
	}
	public function check($key,$name,$title=""){
		if(!$title) $title = $name;
		if(is_a($name,"\\AsyncWeb\\View\\THViewCell")){
			$this->cols[$k] = $name;
		}else{
			if(!array_key_exists($key,$this->cols)){
				$this->cols[$key] = new BasicTHViewCell($key,$name,$title);
			}
		}
	}
	public function getCell($key){
		if(array_key_exists($key,$this->cols)) return $this->cols[$key];
		return null;
	}
	public function getCells(){
		return $this->cols;
	}
	public function getCellDVC($key){
		if($cell = $this->getCell($key)){
			return $cell->getDVC();
		}else{
			return new BasicDataViewCell();
		}
	}
	public function getCols(){
		$ret = array();
		foreach($this->cols as $k=>$v){
			if($v->getKey()){
				$ret[] = $v->getKey();
			}
		}
		return $ret;
	}
	public function show(){
		if(!$this->cols) return null;
		$ret='<thead><tr>';
		$i = 0;
		$lastitem = null;
		foreach($this->cols as $k=>$v){$i++;
			if($v->getIsDisplayable()){
				$lastitem = $k;
			}
		}
		$i=0;
		foreach($this->cols as $k=>$v){$i++;
			$last = $lastitem == $k;
			if(!$v->getIsDisplayable()) continue;
			$ret.=$v->show($last);
		}
		$ret.='</tr></thead>';
		return $ret;
	}
	
}
class TDDataViewRow implements DataViewRow{
	private $thr = null;
	public function __construct(){
		$args = func_get_args();
		$thcells = array();
		while(($arg = array_shift($args)) !== null){
			if(is_a($arg,"\\AsyncWeb\\View\\ViewConfig")){
				foreach($arg->get() as $k=>$v){
					
					if(is_a($v,"\\AsyncWeb\\View\\THViewCell")){
						$thcells[$k] = $v;
					}
				}
			}
			if(is_a($arg,"\\AsyncWeb\\View\\THDataViewRow")){
				$this->thr = $arg;
			}
		}
		if($this->thr){
			foreach($thcells as $k=>$v){
				$this->thr->check($k,$v->getName(),$v->getTitle());
			}
		}else{
			$this->thr = new THDataViewRow();
		}
		

	}
	protected $TableView = null;
	public function setTableView($TableView){
		$this->TableView = $TableView;
	}
	public function show($datarow,$config=array()){
		$ret = "";
		$i=0;
		
		$dr = array();
		if($cols = $this->thr->getCols()){
			$updating = false;
			foreach($cols as $col){
				$dr[$col] = @$datarow[$col];
			}
		}else{
		
			$dr = $datarow;
			$updating = true;
		}
		
		foreach($dr as $k=>$v){$i++;
			if($updating) if($this->thr) $this->thr->check($k,$k,$k);
			$classes = array();
			$classes[$ck = "C".$i] = $ck;
			$classes[$ck = "C".\AsyncWeb\Text\Texts::clear_($k)] = $ck;
			if(isset($config["class"])) $classes[$config["class"]] = $config["class"];
			$title = "";
			if($this->thr && $cell = $this->thr->getCell($k)){
				$title=' title="'.htmlspecialchars($cell->getTitle(),ENT_COMPAT,"UTF-8").'"';
				if(!$cell->getIsDisplayable()) continue;
			}
			
			$class=$this->thr->getCellDVC($k)->getClass();
			if($class){$classes[$class] = $class;}
			
			$cstr = "";
			foreach($classes as $v){
				$cstr .= $v." ";
			}
			$cstr = trim($cstr);
			$value = $this->thr->getCellDVC($k)->show($k,$datarow);
			
			$ret.='<td class="'.$cstr.'"'.$title.'>'.$value.'</td>';
		}
		return $ret;
	}
	public function getDVC($key){
		if(isset($this->thr)) return $this->thr->getCellDVC($key);
		return false;
	}
}
interface View{
	public function show();
	public function getId();
}
interface DataSource{
	public function count();
	public function next();
	public function setLimits($from,$count);
	public function setDistinct($cols);
	public function sort($DataSort);
	public function filter($DataFilter);
}
class DBDataSource implements DataSource{
	private $table;
	private $qb = array();
	private $res;
	public function setDistinct($cols){
		$this->qb["distinct"] = $cols;
	}
	public function sort($DataSort){
		if(!is_a($DataSort,"\\AsyncWeb\\View\\DataSort")){
			throw new \Exception("Sort function expects object of type DataSort");
		}
		foreach($DataSort->get() as $sortcol){
			$dir="asc";
			if($sortcol->getDir() == SORT_DESC) $dir = "desc";
			if($sortcol->getDir() == SORT_ASC) $dir = "asc";
			$this->qb["order"][$sortcol->getKey()] = $dir;
		}
	}
	public function __construct($table,$qb){
		$this->table = $table;
		$this->qb = $qb;
	}
	public function count(){
		$res = \AsyncWeb\DB\DB::qb($this->table,$this->qb);
		return \AsyncWeb\DB\DB::num_rows($res);
	}
	public function next(){
		if(!$this->res){
			$this->res = \AsyncWeb\DB\DB::qb($this->table,$this->qb);
		}
		$ret= \AsyncWeb\DB\DB::f($this->res);
		return $ret;
	}
	public function setLimits($from,$count){
		if(!is_numeric($from)) throw new \Exception("Invalid from number");
		if(!is_numeric($count)) throw new \Exception("Invalid count number");
		if($this->res) throw new \Exception("DB already initialised");
		$this->qb["offset"] = $from;
		$this->qb["limit"] = $count;
	}
	public function filter($DataFilter){
		if(!is_a($DataFilter,"\\AsyncWeb\\View\\DataFilter")){
			throw new \Exception("Filter function expects obect of type DataFilter");
		}
		$cols = $DataFilter->get();
		if($cols){
			$qb["where"][]=array("col"=>"-(");
			foreach($cols as $filter){
				$qb["where"][] = array("col"=>$filter->getCol(),"op"=>$filter->getOperator(),"value"=>$filter->getValue());
				switch($filter->getBinding()){
					case DV_BINDING_AND:
						$qb["where"][]=array("col"=>"-and");
					break;
					case DV_BINDING_OR:
						$qb["where"][]=array("col"=>"-or");
					break;
					case DV_BINDING_AND_LB:
						$qb["where"][]=array("col"=>"-and");
						$qb["where"][]=array("col"=>"-(");
					break;
					case DV_BINDING_RB_AND:
						$qb["where"][]=array("col"=>"-)");
						$qb["where"][]=array("col"=>"-and");
					break;
					case DV_BINDING_OR_LB:
						$qb["where"][]=array("col"=>"-or");
						$qb["where"][]=array("col"=>"-(");
					break;
					case DV_BINDING_RB_OR:
						$qb["where"][]=array("col"=>"-)");
						$qb["where"][]=array("col"=>"-or");
					break;
					case DV_BINDING_NONE:
					break;
				}
			}
		}
	}
}
class ArrayDataSource implements DataSource{
	private $data;
	public function __construct($data){
		$this->data = $data;
		//var_dump($this->data);
	}
	public function count(){
		return count($this->data);
	}
	private $pos = 0;
	public function next(){	
		if($this->limit !== null) if($this->from+$this->limit <= $this->pos) return null;
		$ret=current($this->data);
		next($this->data);
		$this->pos++;
		return $ret;
	}
	private $from = 0;
	private $limit = null;
	public function setDistinct($cols){
		$done = array();
		foreach($this->data as $rk => $row){
			$hash = "H";
			if($cols === true){
				foreach($row as $k=>$v){
					$hash = md5($k."-".$v."-".$hash);
				}
			}elseif(is_array($cols)){
				foreach($cols as $k=>$v){
					if(!is_numeric($k)) $v = $k;
					$hash = md5($v."-".@$row[$v]."-".$hash);
				}
			}
			if(array_key_exists($hash,$done)){
				unset($this->data[$rk]);
			}
			$done[$hash] = true;
		}		
	}

	public function setLimits($from,$limit){// from starts from zero
		if(!is_numeric($from)) throw new \Exception("Invalid from number");
		if(!is_numeric($limit)) throw new \Exception("Invalid limit number");
		
		reset($this->data);
		
		for($i=0;$i<($from+1-1);$i++){
			$n = next($this->data);
			$this->pos++;
		}
		$this->from = $from;
		$this->limit = $limit;
		return true;
	}
	public function sort($DataSort){
		if(is_a($DataSort,"\\AsyncWeb\\View\\DataSort")){
			
			$cols = $DataSort->get();
			if(!$cols) return ;
			if(!$DataSort->getDoNotUseCollate() && class_exists("Collator")){
				$coll = collator_create(\AsyncWeb\System\Language::getLang());
				$col = array_shift($cols);
				
				$tmp = array();
				$tmp2 = array();
				
				$type = Collator::SORT_REGULAR;
				if($col->getType() == SORT_NUMERIC) $type = Collator::SORT_NUMERIC;
				foreach($this->data as $k=>$v){
					if($type == Collator::SORT_NUMERIC){
						$tmp[] = 10000*$v[$col->getCol()];
						$tmp2[10000*$v[$col->getCol()]][] = $v;
					}else{
						$tmp[] = $v[$col->getCol()];
						$tmp2[$v[$col->getCol()]][] = $v;
					}
				}

				
				$ok =  collator_sort($coll,$tmp,$type);
				//var_dump($tmp);exit;
				$tmp3 = array();
				//var_dump($tmp2);exit;
				$done = array();
				foreach($tmp as $v){
					if(isset($done[$v])) continue;
					$done[$v] = true;
					foreach($tmp2[$v] as $v2){
						$tmp3[] = $v2;
					}
				}
				//var_dump($tmp);
				$this->data = $tmp3;
				if($col->getDir() == SORT_DESC){
					$this->data = array_reverse($this->data);
				}
				
			}else{
				$par =array();
				while($col = array_shift($cols)){	
					$tmp = Array(); 
					foreach($this->data as &$ma) $tmp[] = &$ma[$col->getCol()]; 
					$p[] = $tmp;
					$p[] = $col->getDir();
					$p[] = $col->getType();
				}
				$p[] = &$this->data;
				
				call_user_func_array("array_multisort",$p);
			}
		}
	}
	public function filter($DataFilter){
		
		if(!is_a($DataFilter,"\\AsyncWeb\\View\\DataFilter")){
			throw new \Exception("Filter function expects object of type DataFilter");
		}
		
		$cols = $DataFilter->get();
		if($cols){
			$data2 = array();
			foreach($this->data as $row){
				$cols = $DataFilter->get();
				if($this->evaluateSibling($row,$cols,true)){
					$data2[] = $row;
				}
			}
			$this->data = $data2;
		}
	}
	private function evaluateSibling($row,$filters,$first = false){
		if(!is_array($row)){
			throw new \Exception("EvaluateInner function expects array data row");
		}
		$ret = null;
		$depth = 0;
		$lastbinding = null;
		$c=0;
		while($filter = array_shift($filters)){$c++;
			$filters2 = $filters;
			array_unshift($filters2,$filter);
			$r = true;
			if(!is_a($filter,"\\AsyncWeb\\View\\DataFilterItem")){
				throw new \Exception("Evaluate function expects array of objects of type DataFilterItem");
			}
			$new = false;
			switch($lastbinding){
				case DV_BINDING_NONE:
				case DV_BINDING_AND:
				case DV_BINDING_OR:
				
				break;
				case DV_BINDING_AND_LB:
				case DV_BINDING_OR_LB:
					$depth++;
					$new = true;
				break;
				case DV_BINDING_RB_AND:
				case DV_BINDING_RB_OR:
					$depth--;
					if(!$first && $depth < 0) return $ret;
				break;
			}
			if($depth == 0 || $new){
				
				switch($filter->getOperator()){
					case DV_OP_EQUAL:
						if($row[$filter->getCol()] == $filter->getValue()){$r = true;}else{$r=false;}
					break;
					case DV_OP_NOT_EQUAL:
						if($row[$filter->getCol()] != $filter->getValue()){$r = true;}else{$r=false;}
					break;
					case DV_OP_LTEQ:
						if($row[$filter->getCol()] <= $filter->getValue()){$r = true;}else{$r=false;}
					break;
					case DV_OP_LT:
						if($row[$filter->getCol()] < $filter->getValue()){$r = true;}else{$r=false;}
					break;
					case DV_OP_GTEQ:
						if($row[$filter->getCol()] >= $filter->getValue()){$r = true;}else{$r=false;}
					break;
					case DV_OP_GT:
						if($row[$filter->getCol()] > $filter->getValue()){$r = true;}else{$r=false;}
					break;
					case DV_OP_LIKE:
						if(stripos(\AsyncWeb\Text\Texts::clear($row[$filter->getCol()]),\AsyncWeb\Text\Texts::clear($filter->getValue())) !== false){$r = true;}else{$r=false;}
					break;
					case DV_OP_NOT_LIKE:
						if(!stripos(\AsyncWeb\Text\Texts::clear($row[$filter->getCol()]),\AsyncWeb\Text\Texts::clear($filter->getValue())) !== false){$r = true;}else{$r=false;}
					break;
					case DV_OP_NULL:
					case DV_OP_IS:
						if(!$row[$filter->getCol()]){$r = true;}else{$r=false;}
					break;
					case DV_OP_IS_NOT:
						if($row[$filter->getCol()]){$r = true;}else{$r=false;}
					break;
				}
				switch($lastbinding){
					case null:
						$ret = $r;
					break;
					case DV_BINDING_NONE:
					case DV_BINDING_AND:
					case DV_BINDING_RB_AND:
						$ret = $ret && $r;
					break;
					case DV_BINDING_RB_OR:
					case DV_BINDING_OR:
						
						$ret = $ret || $r;
					break;
					break;
					case DV_BINDING_AND_LB:
						$ret = $ret && $this->evaluateSibling($row,$filters2);
					break;
					case DV_BINDING_OR_LB:
						$ret = $ret || $this->evaluateSibling($row,$filters2);
					break;
				}
			}
			$lastbinding = $filter->getBinding();
		}
		if($ret===null && $r!==null) return $r;
		return $ret;
	}
	
//	a && (b || c) = (a && b) || (a && c)
//	a && (b || (c && d)) = a && b || c && b || d
	
}
class ColSort{
	protected $key;
	protected $dir = SORT_ASC;
	protected $type = SORT_REGULAR;
	
	public function __construct($key,$dir="",$type=""){
		if(!$key) throw new \Exception("Invalid key for sorting");
		
		$this->key = $key;
		switch($dir){
			case "asc":
				$this->dir = SORT_ASC;
			break;
			case "desc":
				$this->dir = SORT_DESC;
			break;
			case "":
			break;
			default:
				throw new \Exception("Sort direction not defined!");

		}
		switch($type){
			case "regular":
				$this->type = SORT_REGULAR;
			break;
			case "string":
				$this->type = SORT_STRING;
			break;
			case "numeric":
				$this->type = SORT_NUMERIC;
			break;
			case "":
			break;
			default:
				throw new \Exception("Sort type not defined!");
		}
	}
	public function getCol(){
		return $this->key;
	}
	public function getType(){
		return $this->type;
	}
	public function getDir(){
		return $this->dir;
	}
}
class DataSort{
	protected $cols = array();
	public function __construct(){
		$args = func_get_args();
		while(($arg = array_shift($args)) !== null){
			if(is_a($arg,"\\AsyncWeb\\View\\ColSort")){
				$this->cols[] = $arg;
			}
			if($arg == "doNotUseCollate"){
				$this->doNotUseCollate=true;
			}
		}
	}
	public function get(){
		return $this->cols;
	}
	private $doNotUseCollate = false;
	public function add($ColSort){
		if(is_a($ColSort,"\\AsyncWeb\\View\\ColSort")){
			$this->cols[] = $ColSort;
			return true;
		}
		return false;
	}
	public function getDoNotUseCollate(){
		return $this->doNotUseCollate;
	}
	public function remove($k){
		if(isset($this->cols[$k])) unset($this->cols[$k]);
	}
}
class DataFilterItem{
	protected $col;
	public function getCol(){
		return $this->col;
	}
	public function remove($key){
		unset($this->col[$key]);
	}
	protected $operator;
	public function getOperator(){
		return $this->operator;
	}
	protected $value;
	public function getValue(){
		return $this->value;
	}
	protected $binding;
	public function getBinding(){
		return $this->binding;
	}
	public function __construct($col,$operator,$value,$binding){
		if(!$col) throw new \Exception("Column has not been selected");
		$this->col = $col;
		if(	$operator != DV_OP_EQUAL && 
			$operator != DV_OP_NOT_EQUAL && 
			$operator != DV_OP_LTEQ && 
			$operator != DV_OP_LT && 
			$operator != DV_OP_GTEQ && 
			$operator != DV_OP_GT && 
			$operator != DV_OP_LIKE && 
			$operator != DV_OP_NOT_LIKE && 
			$operator != DV_OP_IS && 
			$operator != DV_OP_IS_NOT && 
			$operator != DV_OP_NULL){
				throw new \Exception("Operator $operator has not been recognized");
			}
		if(	$binding != DV_BINDING_AND && 
			$binding != DV_BINDING_OR &&  
			$binding != DV_BINDING_AND_LB &&  
			$binding != DV_BINDING_RB_AND &&  
			$binding != DV_BINDING_OR_LB &&  
			$binding != DV_BINDING_RB_OR &&  
			$binding != DV_BINDING_NONE){
				throw new \Exception("Binding condition has not been recognized");
			}
		$this->operator = $operator;
		$this->value = $value;
		$this->binding = $binding;
	}
}
class DataFilter{
	protected $items = array();
	public function __construct(){
		$args = func_get_args();
		while(($arg = array_shift($args)) !== null){
			if(is_a($arg,"\\AsyncWeb\\View\\DataFilterItem")){
				$this->items[] = $arg;
			}
			if(is_array($arg)){
				foreach($arg as $v){
					if(is_a($v,"\\AsyncWeb\\View\\DataFilterItem")){
						$this->items[] = $v;
					}
				}
			}
		}
	}
	protected $DataViewRow = null;
	public function setDataViewRow($DataViewRow){
		if(is_a($DataViewRow,"\\AsyncWeb\\View\\DataViewRow")){
			$this->DataViewRow = $DataViewRow;
		}
	}
	public function get(){
		$ret = array();
		foreach($this->items as $item){
			if($this->DataViewRow && ($dvc = $this->DataViewRow->getDVC($item->getCol()))){
				$dvc->modifyFilter($ret,$item);
			}else{
				$ret[] = $item;
			}
		}
		return $ret;
	}
	public function add($v){
		if(is_a($v,"\\AsyncWeb\\View\\DataFilterItem")){
			$this->items[] = $v;
			return true;
		}
		return false;
	}
}
class TSAjax{
	public static function makeScript($elementid,$addr){
		return '$(\'#'.$elementid.'\').toggle();return true;';
	}
	public static function makeFilterScript($THViewCell,$show=false){
		$DataTableId = $THViewCell->getTableId();
		if($show){
			return $THViewCell->getDVC()->generateFilterBox($THViewCell->getKey());
		}
		/*
		
		return "return showFilterBox('".$DataTableId."','".$THViewCell->getKey()."','".\AsyncWeb\System\Path::make(array('showfilterbox'=>md5($DataTableId."-".$THViewCell->getKey()),"AJAX"=>""))."');";
		//return showFilterBox(\''.$parentid.'\',\''.md5($this->key).'\');
		/**/
		return '$(\'#filterbox_'.$DataTableId.'_'.$THViewCell->getKey().'\').css({\'position\':\'absolute\',\'top\':($(this).position().top + $(this).height() + 6) + \'px\',\'left\':($(this).position().left+4) + \'px\',}).toggle();return true;';
	}
	public static function makeTableMenuScript($THViewCell,$showmenu = false){
		$DataTableId = $THViewCell->getTableId();
		$ret = "";
		if($showmenu){
			$ret.='<div class="menubox">';
			$tv = $THViewCell->getTableView();
			$i=0;
			if($tv){
				foreach($tv->getTableMenuItems() as $tmi){$i++;
					$ret.=$tmi->showMenu();
				}
			}
			if(!$ret){
				$ret.=\AsyncWeb\System\Language::get("Menu neobsahuje žiadnu položku");
			}
			$ret.= '</div>';
			return $ret;
			//$THViewCell->getDVC()->generateFilterBox($THViewCell->getKey());
		}//alert(JSON.stringify($(this).position()));
		return '$(\'#menuboxbox_'.$DataTableId.'\').css({\'position\':\'absolute\',\'top\':($(this).position().top + $(this).height() + 6) + \'px\',\'left\':($(this).position().left+4) + \'px\',}).toggle();return true;';
		//return "return showMenuBox('".$DataTableId."','".\AsyncWeb\System\Path::make(array('showmenubox'=>md5($DataTableId),"AJAX"=>""))."');";
		//return showFilterBox(\''.$parentid.'\',\''.md5($this->key).'\');
	}
	public static function applyFilterScript($key,$DataTableId){
		if(isset($_REQUEST["applyfilterbox"]) && $_REQUEST["applyfilterbox"] == md5($DataTableId."-".$key)){
			
			$filter = \AsyncWeb\Storage\Session::get("DV_filter");
			$filter[$DataTableId] = array();
			if(isset($_REQUEST["FilterCH"])){
				foreach($_REQUEST["FilterCH"] as $form=>$v2){
					$filter[$form] = array();
					foreach($v2 as $key=>$v3){
						foreach($v3 as $trid=>$v4){
							if($v4 == "on"){
								$row = array("op"=>$_REQUEST["FilterOperant"][$form][$key][$trid],"value"=>$_REQUEST["FilterValue"][$form][$key][$trid]);
								$filter[$form][$key][] = $row;
							}
						}
					}
				}
			}
			$filter = \AsyncWeb\Storage\Session::set("DV_filter",$filter);
			$tid = 'DTTV_tablediv_'.$DataTableId;
			$ttid = 'TABLE_'.$DataTableId;
			$addr = \AsyncWeb\System\Path::make(array('ITER_'.($DataTableId).'_PAGE'=>'0',"applyfilterbox"=>"","showfilterbox"=>"","AJAX"=>$ttid),$moveparams=true,$uri=null,$paramsAreSafe=false,$getIsSafe=false,$js=true);
			$script = "TSLoad('".$tid."','".$addr."');";
			//$script = 'window.location.href = \''.\AsyncWeb\System\Path::make(array('ITER_'.($DataTableId).'_PAGE'=>'0')).'\';';
			//echo $script;
			echo '<script type="text/javascript">'.$script.'</script>';
			exit;
		}
		return "return applyFilterBox('".$DataTableId."','".$key."','".\AsyncWeb\System\Path::make(array('applyfilterbox'=>md5($DataTableId."-".$key),"AJAX"=>""))."');";
		//return showFilterBox(\''.$parentid.'\',\''.md5($this->key).'\');
	}
	
}
interface TableMenuItem {
	public function setTableView($TableView);
	public function showMenu();
	public function check();
}
class BasicTableMenuItem implements TableMenuItem {
	protected $TableView;
	public function setTableView($TableView){
		$this->TableView = $TableView;
	}
	public function showMenu(){}
	public function check(){}
}
interface TableMenuItems{
	public function get();
}
class ExportTableMenuItems implements TableMenuItems{
	public function get(){
		$menuitems = array();
		$menuitems[] = new TableMenuExportXML();
		$menuitems[] = new TableMenuExportCSV();
		$menuitems[] = new TableMenuExportHTML();
		return $menuitems;
	}
}
class TableMenuItemIconHref extends BasicTableMenuItem {
	private $icon;
	private $href;
	private $text;
	
	public function __construct($href,$text,$icon=null){
		$this->icon = $icon;
		$this->href = $href;
		$this->text = $text;
	}
	public function showMenu(){
		$ret = '<div class="table_menu_item">';
		$id = "";
		if($this->TableView) $id = $this->TableView->getId();
		if($this->icon){
			$ret .= '<a title="'.$this->text.'" href="'.$this->href.'">';
			$ret.='<img src="'.$this->icon.'" width="30" height="30" alt="" />';
			$ret.='</a> ';
		}
		$ret .= '<a title="'.$this->text.'" href="'.$this->href.'">';
		$ret.= $this->text;
		$ret.='</a>';
		$ret.='</div>';
		return $ret;
	}
}
class TableMenuExportXML extends BasicTableMenuItem{
	
	public function showMenu(){
		$ret = '<div class="table_menu_item">';
		$id = "";
		if($this->TableView) $id = $this->TableView->getId();
		
		$ret .= '<a title="'.\AsyncWeb\System\Language::get("Export do XML").'" href="'.\AsyncWeb\System\Path::make(array('DTV_export'=>md5($id."-xml"),"showmenubox"=>"","AJAX"=>"")).'">';
		if(\AsyncWeb\IO\File::exists("img/icons/file_download_xml.png")){
			$ret.='<img src="/img/icons/file_download_xml.png" width="30" height="30" alt="XML" />';
		}
		$ret.='</a> ';
		$ret .= '<a title="'.\AsyncWeb\System\Language::get("Export do XML").'" href="'.\AsyncWeb\System\Path::make(array('DTV_export'=>md5($id."-xml"),"showmenubox"=>"","AJAX"=>"")).'">';
		$ret.= \AsyncWeb\System\Language::get("Export do XML");
		$ret.='</a>';
		$ret.='</div>';
		return $ret;
	}
	public function check(){
		$id = "";
		if($this->TableView) $id = $this->TableView->getId();
		if($this->TableView && $id && isset($_REQUEST["DTV_export"]) && $_REQUEST["DTV_export"]){
			if($_REQUEST["DTV_export"]==md5($id."-xml")){
				return $this->make_export();
			}
		}
	}
	private function make_export(){
		$i = 0;
		if(!$this->TableView) exit;
		$thr = $this->TableView->getTHR();
		if(!$thr) exit;
		$ret = '<Data>';
		while($datarow = $this->TableView->getDataSource()->next()){$i++;
			$ret.='<Item>';
			foreach($thr->getCells() as $col){
				//var_dump($col);exit;
				if($col->getIsExportable()){
					$key = $col->getKey();
					if(!$key) continue;
					$key = \AsyncWeb\Text\Texts::clear_($key);
					//if(!array_key_exists($key,$datarow)) throw new \Exception("Key :$key: does not exists in datarow");
					$value = $datarow[$key];
					$ret.="<$key>$value</$key>";
				}
			}
			$ret.='</Item>';
		}
		$ret .= '</Data>';


		
		$filename = $this->TableView->getId()."-".date("Ymd").".xml";
	
		\AsyncWeb\HTTP\Header::send("Cache-Control: public");
		\AsyncWeb\HTTP\Header::send("Content-Description: File Transfer");
		\AsyncWeb\HTTP\Header::send("Content-Disposition: attachment; filename=$filename");
		\AsyncWeb\HTTP\Header::send("Content-Type: text/xml");
		\AsyncWeb\HTTP\Header::send("Content-Transfer-Encoding: binary");
		\AsyncWeb\HTTP\Header::send("Content-length: ".strlen($ret));/**/
		echo $ret;
		exit;
	}
}
class TableMenuExportHTML extends BasicTableMenuItem{
	public function showMenu(){
		$ret = '<div class="table_menu_item">';
		$id = "";
		if($this->TableView) $id = $this->TableView->getId();
		
		$ret .= '<a title="'.\AsyncWeb\System\Language::get("Export do HTML").'" href="'.\AsyncWeb\System\Path::make(array('DTV_export'=>md5($id."-html"),"showmenubox"=>"","AJAX"=>"")).'">';
		if(\AsyncWeb\IO\File::exists("img/icons/file_download_html.png")){
			$ret.='<img src="/img/icons/file_download_html.png" width="30" height="30" alt="HTML" />';
		}
		$ret.='</a> ';
		$ret .= '<a title="'.\AsyncWeb\System\Language::get("Export do HTML").'" href="'.\AsyncWeb\System\Path::make(array('DTV_export'=>md5($id."-html"),"showmenubox"=>"","AJAX"=>"")).'">';
		$ret.= \AsyncWeb\System\Language::get("Export do HTML");
		$ret.='</a>';
		$ret.='</div>';
		return $ret;
	}
	public function check(){
		$id = "";
		if($this->TableView) $id = $this->TableView->getId();
		
		if($this->TableView && $id && isset($_REQUEST["DTV_export"]) && $_REQUEST["DTV_export"]){
			if($_REQUEST["DTV_export"]==md5($id."-html")){
				return $this->make_export();
			}
		}
	}
	private function make_export(){
		$i = 0;
		if(!$this->TableView) exit;
		$thr = $this->TableView->getTHR();
		if(!$thr) exit;
		$id = $this->TableView->getId();
		$prefix = "ls";
		$ret = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.\AsyncWeb\System\Language::getLang().'">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title>'.$id.'</title>
</head>
<body><table id="data_table_'.$id.'" class="table data_table_'.$prefix.'">';
		
		$ret.= '<thead><tr>';
		foreach($thr->getCells() as $col){
			if($col->getIsExportable()){
				$value = $col->getName();
				$ret.='<th>'.$value.'</th>';
			}
		}
		$ret.= '</tr></thead><tbody>';
		while($datarow = $this->TableView->getDataSource()->next()){$i++;
			$ret.='<tr>';
			foreach($thr->getCells() as $col){
				if($col->getIsExportable()){
					$key = $col->getKey();
					if(!$key) continue;
					$key = \AsyncWeb\Text\Texts::clear_($key);
					$value = $datarow[$key];
					$ret.="<td>$value</td>";
				}
			}
			$ret.='</tr>';
		}
		$ret .= '</tbody></table></body></html>';


		$filename = $this->TableView->getId()."-".date("Ymd").".html";
	
		\AsyncWeb\HTTP\Header::send("Cache-Control: public");
		\AsyncWeb\HTTP\Header::send("Content-Description: File Transfer");
		\AsyncWeb\HTTP\Header::send("Content-Disposition: attachment; filename=$filename");
		\AsyncWeb\HTTP\Header::send("Content-Type: text/html");
		\AsyncWeb\HTTP\Header::send("Content-Transfer-Encoding: binary");
		\AsyncWeb\HTTP\Header::send("Content-length: ".strlen($ret));/**/
		echo $ret;
		exit;
	}
}
class TableMenuExportCSV extends BasicTableMenuItem{
	public function showMenu(){
		$ret = '<div class="table_menu_item">';
		$id = "";
		if($this->TableView) $id = $this->TableView->getId();
		
		$ret .= '<a title="'.\AsyncWeb\System\Language::get("Export do Excelu").'" href="'.\AsyncWeb\System\Path::make(array('DTV_export'=>md5($id."-csv"),"showmenubox"=>"","AJAX"=>"")).'">';
		if(\AsyncWeb\IO\File::exists("img/icons/file_download_excel.png")){
			$ret.='<img src="/img/icons/file_download_excel.png" width="30" height="30" alt="Excel" />';
		}
		$ret.='</a> ';
		$ret .= '<a title="'.\AsyncWeb\System\Language::get("Export do Excelu").'" href="'.\AsyncWeb\System\Path::make(array('DTV_export'=>md5($id."-csv"),"showmenubox"=>"","AJAX"=>"")).'">';
		$ret.= \AsyncWeb\System\Language::get("Export do Excelu");
		$ret.='</a>';
		$ret.='</div>';
		return $ret;
	}
	public function check(){
		$id = "";
		if($this->TableView) $id = $this->TableView->getId();
		if($this->TableView && $id && isset($_REQUEST["DTV_export"]) && $_REQUEST["DTV_export"]){
			if($_REQUEST["DTV_export"]==md5($id."-csv")){
				return $this->make_export();
			}
		}
	}
	private function checkCSVInput($input){
		$outEncoding= "windows-1250";
		$input = iconv("utf-8",$outEncoding,$input);
		$input = str_replace("\n","",$input);
		$input = strip_tags($input);
		$input = addslashes($input);
		return $input;
	}
	private function make_export(){
		$i = 0;
		if(!$this->TableView) exit;
		$thr = $this->TableView->getTHR();
		if(!$thr) exit;
		$sep1 = '"';
		$sep2 = ';';
		$line = "\n";

		$ret = '';
		foreach($thr->getCells() as $col){
			if($col->getIsExportable()){
				$value = $this->checkCSVInput($col->getName());
				$ret.=$sep1.$value.$sep1.$sep2;
			}
		}

			$ret.=$line;

		while($datarow = $this->TableView->getDataSource()->next()){$i++;
			$ret.='';
			foreach($thr->getCells() as $col){
				if($col->getIsExportable()){
					$value = $this->checkCSVInput($datarow[$col->getKey()]);
					$ret.=$sep1.$value.$sep1.$sep2;
				}
			}
			$ret.=$line;
		}

		$filename = $this->TableView->getId()."-".date("Ymd").".csv";
	
		\AsyncWeb\HTTP\Header::send("Cache-Control: public");
		\AsyncWeb\HTTP\Header::send("Content-Description: File Transfer");
		\AsyncWeb\HTTP\Header::send("Content-Disposition: attachment; filename=$filename");
		\AsyncWeb\HTTP\Header::send("Content-Type: text/csv");
		\AsyncWeb\HTTP\Header::send("Content-Transfer-Encoding: binary");
		\AsyncWeb\HTTP\Header::send("Content-length: ".strlen($ret));/**/
		echo $ret;
		exit;
	}
}
class TextAppend{
	protected $text = "";
	public function __construct($text){
		$this->text = $text;
	}
	public function show(){
		return $this->text;
	}
}
class Mobile{}
class TableView implements View{
	public static $INIT = false;
	protected $id = null;
	public function getId(){
		return $this->id;
	}
	protected $cols = 1;
	protected $rows = 30;
	protected $datasource;
	protected $dvr;
	protected $thr;
	public function getTHR(){
		return $this->thr;
	}
	protected $filter;
	protected $sort;
	protected $utime;
	protected $TableMenuItems = array();
	protected $ViewConfig;
	protected $mobile = null;
	protected $append = null;
	
	public function getTableMenuItems(){
		return $this->TableMenuItems;
	}
	public function getDataSource(){
		return $this->datasource;
	}
	public function __construct(){
		$this->utime = microtime(true);
		$args = func_get_args();
		while(($arg = array_shift($args)) !== null){
		
			if(is_a($arg,"\\AsyncWeb\\View\\ViewConfig")){
				$this->ViewConfig = $arg;
				$this->cols = $arg->getValue("cols",1);
				$this->rows = $arg->getValue("rows",30);
				$this->id = \AsyncWeb\Text\Texts::clear_($arg->getValue("id"));
			}
			if(is_a($arg,"\\AsyncWeb\\View\\DataSource")){
				$this->datasource = $arg;
			}
			if(is_a($arg,"\\AsyncWeb\\View\\THDataViewRow")){
				$this->thr = $arg;
				$arg->setTableView($this);
			}
			if(is_a($arg,"\\AsyncWeb\\View\\DataViewRow")){
				$this->dvr = $arg;
				$arg->setTableView($this);
			}
			if(is_a($arg,"\\AsyncWeb\\View\\TextAppend")){
				$this->append = $arg;
			}
			if(is_a($arg,"\\AsyncWeb\\View\\DataFilter")){
				$this->filter = $arg; 
			}
			if(is_a($arg,"\\AsyncWeb\\View\\DataSort")){
				$this->sort = $arg; 
			}
			if(is_a($arg,"\\AsyncWeb\\View\\TableMenuItem")){
				$this->TableMenuItems[] = $arg; 
				$arg->setTableView($this);
				
			}
			if(is_a($arg,"\\AsyncWeb\\View\\Mobile")){
				$this->mobile = $arg; 
			}
			if(is_a($arg,"\\AsyncWeb\\View\\TableMenuItems")){
				$items = $arg->get();
				
				if($items){
					foreach($items as $item){
						if(is_a($item,"\\AsyncWeb\\View\\TableMenuItem")){
							$this->TableMenuItems[] = $item; 
							$item->setTableView($this);
						}
					}
				}
			}
			$argv = $arg;
			if(is_array($argv)){
				foreach($argv as $arg){
					
							
					if(is_a($arg,"\\AsyncWeb\\View\\ViewConfig")){
						$this->ViewConfig = $arg;
						$this->cols = $arg->getValue("cols",1);
						$this->rows = $arg->getValue("rows",30);
						$this->id = \AsyncWeb\Text\Texts::clear_($arg->getValue("id"));
					}
					if(is_a($arg,"\\AsyncWeb\\View\\DataSource")){
						$this->datasource = $arg;
					}
					if(is_a($arg,"\\AsyncWeb\\View\\THDataViewRow")){
						$this->thr = $arg;
						$arg->setTableView($this);
					}
					if(is_a($arg,"\\AsyncWeb\\View\\DataViewRow")){
						$this->dvr = $arg;
						$arg->setTableView($this);
					}
					if(is_a($arg,"\\AsyncWeb\\View\\DataFilter")){
						$this->filter = $arg; 
					}
					if(is_a($arg,"\\AsyncWeb\\View\\DataSort")){
						$this->sort = $arg; 
					}
					if(is_a($arg,"\\AsyncWeb\\View\\TableMenuItem")){
						$this->TableMenuItems[] = $arg; 
						$arg->setTableView($this);
						
					}
					if(is_a($arg,"\\AsyncWeb\\View\\Mobile")){
						$this->mobile = $arg; 
					}
					
					if(is_a($arg,"\\AsyncWeb\\View\\TableMenuItems")){
						$items = $arg->get();
						
						if($items){
							foreach($items as $item){
								if(is_a($item,"\\AsyncWeb\\View\\TableMenuItem")){
									$this->TableMenuItems[] = $item; 
									$item->setTableView($this);
								}
							}
						}
					}
				}
			}
			
			if(!$this->ViewConfig) $this->ViewConfig = new ViewConfig();
		}
		if(!$this->id) throw new \Exception("Table ID must be defined!");
		if(!$this->datasource){ throw new \Exception("No datasource!");}
		
		if(!$this->thr) $this->thr = new THDataViewRow();
		if(!$this->dvr){
			$this->dvr = new TDDataViewRow($this->thr);
		}
		
		
		
		
		$filtercols = array();
		if($filter = \AsyncWeb\Storage\Session::get("DV_filter")){
			if(!$this->filter) $this->filter = new DataFilter();
			
			if(isset($filter[$this->id])){
				foreach($filter[$this->id] as $key=>$rows){
					foreach($rows as $row){
						$this->filter->add(new DataFilterItem($key,$row["op"],$row["value"],DV_BINDING_AND));
					}
				}
			}
		}
		
		if($this->filter){
			$this->filter->setDataViewRow($this->dvr);
			$this->datasource->filter($this->filter);
		}
		
		
		
		if(isset($_REQUEST["sort_".$this->id])){
			foreach($this->thr->getCols() as $k=>$v){
				
				if(!$this->thr->getCell($v)->getIsSortable()) continue;
				if($_REQUEST["sort_".$this->id] == md5($v)){
					
					if($this->thr->getCellDVC($v)->datatype == "number"){
						
						$this->sort = new DataSort(new ColSort($v,$_REQUEST["dir"],"numeric"));
					}else{
						$this->sort = new DataSort(new ColSort($v,$_REQUEST["dir"]));
					}
				}
			}
		}
		
		if($this->sort){
			if($this->thr){
				foreach($this->sort->get() as $k=>$v){
					if(is_a($v,"\\AsyncWeb\\View\\ColSort")){
						if($cell = $this->thr->getCell($v->getCol())){
							if($cell->getIsSortable()){
								$cell->setIsSorted($v->getDir());
							}else{
								$this->sort->remove($k);
							}
						}else{
							$this->sort->remove($k);
						}
					}
				}
			}
			
		}
		
		if($this->sort){$this->datasource->sort($this->sort);}
		
		
	}
	public function show(){
		if(!$this->datasource) throw new \Exception("DataSource not yet initialised!");
		if($this->cols <= 0) throw new \Exception("Wrong amount of cols!");
		if($this->rows <= 0) throw new \Exception("Wrong amount of rows!");
	
		
		foreach($this->TableMenuItems as $menuitems){
			$menuitems->check();
		}
		
		$count = $this->datasource->count();
		
		$iter = \AsyncWeb\View\Iterator::getIterator($iterid=$this->id,$this->cols*$this->rows,$start_from_zero = true,$typ="jquery");
		if(!isset($_REQUEST["ITER_${iterid}_PAGE"])) $iter->reset();
		$this->datasource->setLimits($iter->getStart(),$iter->getPerPage());
				
		$ret = '<table class="TableView ui-responsive table-stroke" id="'.$this->id.'"';
		if($this->mobile) $ret.= ' data-role="table" data-mode="columntoggle"';
		$ret.= '>';
		$c = 0;
		$r = 1;
		$tbody = '<tr id="row_'.$r.'_'.$this->id.'" class="row'.($r%2).' rp'.($r).'">';
		while($datarow = $this->datasource->next()){$c++;
			$class='col'.(($c-1)%($this->cols)+1);
			$tbody .= $this->dvr->show($datarow,array("class"=>$class));
			if($c%$this->cols == 0){
				$r++;
				if($r > $this->rows){}else{
					$tbody.='</tr><tr class="row'.($r%2).' rp'.($r).'">';
				}
			}
		}
		if(!$c){
			$tbody .= '<td colspan="'.($this->thr->count()).'">'.\AsyncWeb\System\Language::get($this->ViewConfig->getValue("no_data",'Tabuľka neobsahuje žiadny záznam')).'</td>';
		}
		$tbody.='</tr>';
		
		$tfoot = ' <tfoot>
    <tr>
      <td colspan="'.($this->thr->count()).'">'.\AsyncWeb\System\Language::get("Počet záznamov").': '.$count.'; '.\AsyncWeb\System\Language::get("Strana").': '.($iter->getPage()+1).'/'.($iter->getPagesCount($count)-1)."; ".\AsyncWeb\System\Language::get("Vygenerované za").": ".(round(1000*(microtime(true)-$this->utime))/1000)." s";
	  if($iter->getPagesCount($count)>2) $tfoot .= $iter->show_bar($count,5,array(),"DTTV_bottom");
	  $tfoot.='</td>
    </tr>
  </tfoot>';
		
		if($this->thr && $thead = $this->thr->show($this->id)){
			$ret.=$thead.$tfoot;
			
			$tbody = '<tbody id="DTTV_tbody_'.$this->id.'">'.$tbody.'</tbody>';
		}
		
		$ret.=$tbody;
		
		$ret.='</table>';
		if($this->append){
			$ret.=$this->append->show();
		}
		if(isset($_REQUEST["AJAX"]) && $_REQUEST["AJAX"] == "TABLE_".$this->id){
				echo $ret;
				exit;
		}
		
		$ret = '<div id="DTTV_tablediv_'.$this->id.'">'.$ret.'</div>';
		return $ret;
	}
	public function isMobile(){
		return $this->mobile != null;
	}
}

class InputFilter{
	private $params = array();
	private $inner = array();
	public function __construct($arr){
		foreach($arr as $k=>$v){
			if($k == "filter"){
				$this->inner = new InputFilter($v);
			}else{
				$this->params[$k] = $v;
			}
		}
	}
	public function filter($data,&$row){
		$ret = "";
		$filter=$this->params;
		switch (@$filter["type"]){
			case "php":
				if(!isset($filter["params"]) || !is_array($filter["params"])) $filter["params"] = array();
				$filter["params"]["row"] = $row;
				$ret = \AsyncWeb\System\Execute::run($filter["function"],$filter["params"]);
			break;
			case "db":
				/**
					"filter"=>array("type"=>"db","table"=>"mytable","conds"=>array(),"where"=>"mycol","col"=>"select");
				*/
				$filter["conds"][$filter["where"]] = $data;
				$row2 = \AsyncWeb\DB\DB::gr($filter["table"],$filter["conds"]);
				
				if($row2){
					$ret = $row2[$filter["col"]];
				}
			break;
			case "or":
				/**
					"filter"=>array("type"=>"or","filters"=>array(
						array("type"=>"db","table"=>"mytable","conds"=>array(),"where"=>"mycol","col"=>"select"),
						array("type"=>"db","table"=>"mytable","conds"=>array(),"where"=>"mycol","col"=>"select"),
					));
				*/
				foreach($filter["filters"] as $f){
					$f = new InputFilter($f);
					$r = $f->filter($data,$row);
					if($r){$ret=$r;break;}
				}
			break;
			case 'date':
				if($data){
					$ret.= date($filter["format"],$data);
				}else{
					$ret.= "-";
				}
				break;
			case 'sprintf':
				$ret.=sprintf($filter["format"],$data);
				break;
			case 'number_format':
				$ret.= number_format($data,$filter["decimal"],$filter["desat_oddelocac"],$filter["oddelovac_tisicov"]);
				break;
			case 'path':
				if(!$data) $data = "-";
				$path = $filter["src"];
				if(is_array($filter["src"])){
					$move = array();
					foreach($filter["src"] as $k=>$v){
						if(isset($data[$k])){
							$move[$v] = $data[$k];
						}elseif(isset($data[$v])){
							$move[$v] = $data[$v];
						}
					}
					$path = \AsyncWeb\System\Path::make($move);
				}
				$ret.= '<a href="'.$path.'">'.$data.'</a>';
				break;
			case 'href':
				if(!$data)$data = "-";
				$ret.= '<a href="'.$filter["src"].$row["id"].'">'.$data.'</a>';
				break;
			case "urlparser":
				if(!$data) $data = "-";
				$path = $filter["src"];
				if(is_array($filter["src"])){
					$move = array();
					if(isset($filter["src"]["var"]))
					foreach($filter["src"]["var"] as $k=>$v){
						if(isset($row[$k])){
							$move[$v] = $row[$k];
						}if(isset($row[$v])){
							$move[$v] = $row[$v];
						}
					}
					$filter["src"]["var"] = $move;
					$path = \AsyncWeb\Frontend\URLParser::merge2($filter["src"],\AsyncWeb\Frontend\URLParser::parse());
				}
				$ret.= '<a href="'.$path.'">'.$data.'</a>';
				
			break;
			case 'hrefID2':
				if(!$data)$data = "-";
				if(!($col = @$filter["col"])){
					$col = "id2";
				}
				if(!isset($row[$col]) || !$row[$col]){
					$ret = $data;
				}else{
					$ret.= '<a href="'.$filter["src"].$row[$col].'">'.$data.'</a>';
				}
				
				break;
			case 'hrefID3':
				if(!$data)$data = "-";
				if(!($col = @$filter["col"])){
					$col = "id2";
				}
				$ret.= '<a href="'.$filter["src"].$row[$col].'/">'.$data.'</a>';
				break;
			case 'text':
				$ret.= nl2br($data);
				break;
			case 'extra_htmlspecialchars':
				$ret.= htmlspecialchars($data);
				break;
			case 'option':
				if(@$filter["option"][$data]){
					$ret.= @$filter["option"][$data];
				}else{
					$ret.= $data;
				}
				break;
			case 'add_after':
				$ret.= $data.$filter["data"];
				break;
			case 'add_before':
				$ret.= $filter["data"].$data;
				break;
			default:
				$ret.= $data;
		}
		if($this->inner){
			$ret = $this->inner->filter($ret,$row);
		}
		return $ret;
	}
}
