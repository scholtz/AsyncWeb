<?php
namespace AsyncWeb\DefaultBlocks;

use \AsyncWeb\DB\DB;
use \AsyncWeb\View\MakeForm;
use \AsyncWeb\View\MakeDBView;
use \AsyncWeb\System\Language;

class FAQ extends \AsyncWeb\Frontend\Block{
	public function getNextFAQOrder(){
		$r = DB::qbr("faq",array("cols"=>array("c"=>"max(`order`)")));
		return $r["c"]+1;
	}
	public function initTemplate(){
		$ret = '';
		
		if(\AsyncWeb\Objects\Group::is_in_group("admin")){
			$ret.='<h1>Správa FAQ</h1>';
			
			$data2 = array(
					"table" => "faq",
					"col" => array( 
						array("name"=>"Otázka","data"=>array("col"=>"question","dictionary"=>true),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe")),
						array("name"=>"Odpoveï","form"=>array("type"=>"tinyMCE","theme"=>"advanced"),"data"=>array("col"=>"answer","dictionary"=>true),"usage"=>array("MFi","MFu","MFd","DBVe")),
						array("name"=>"Poradie","data"=>array("col"=>"order"),"texts"=>array("default"=>'PHP::\FAQ::getNextFAQOrder'),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe")),
						array("form"=>array("type"=>"value"),"data"=>array("col"=>"created"),"texts"=>array("text"=>"PHP::time()"),"usage"=>array("MFi",)),
					),
					 "bootstrap"=>"1",
					 "uid"=>"balik-".$pckprice["id"],
					 "show_export"=>true,
					 "rights"=>array("insert"=>"admin","update"=>"admin","delete"=>"admin"),
					 "allowInsert"=>true,"allowUpdate"=>true,"allowDelete"=>true,"useForms"=>true,
					 "iter"=>array("per_page"=>"30"),
					 "MakeDVView"=>5,
					 "show_filter"=>true,
				);
				
			MakeDBView::$repair = true;
			$form = new MakeForm($data2);		
			$ret .= $form->show("ALL");	
			
		}
		
		$ret.='<h1>FAQ</h1>';
		$res = DB::qb("faq",array("order"=>array("order"=>"asc")));
		$ret.='				<div class="panel-group" id="accordion">
					<div class="panel panel-default">
';
		while($row=DB::f($res)){
			$ret.='<div class="panel-heading">
      <h2 class="panel-title">
        <a data-toggle="collapse" data-parent="#accordion" href="#ID'.$row["id2"].'">
          '.(Language::get($row["question"])).'
        </a>
      </h2>
	  </div>
    
    <div id="ID'.$row["id2"].'" class="panel-body panel-collapse collapse out"><div class="inner">'.html_entity_decode(Language::get($row["answer"])).'</div></div>
';
			
		}
		$ret.='</div></div>';
		$this->template =  $ret;
	}
}