<?php
namespace AsyncWeb\Process\Blocks;
use AsyncWeb\System\Language;
use AsyncWeb\View\MakeForm;
use AsyncWeb\Frontend\URLParser;

class ListProcessObjects extends \AsyncWeb\Frontend\Block{
	protected $usesparams = array("expertmode");
	protected function initTemplate(){
		$ret = "";
		$data2 = array(
			"table" => "processobject",
			"col" => array( 
			 array("name"=>"Name","data"=>array("col"=>"name","dictionary"=>true),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe"),
			  "filter"=>array("type"=>"urlparser","src"=>array("tmpl"=>array("ListProcessObjects"=>"ListProcesses"),"var"=>array("id2"=>"processobject")))),
			),
			"order" => array("name"=>"asc",),
			 "prefix"=>"ls",
			 "uid"=>"processobject",
			 "popis"=>Language::get("List of process objects"),
			 "no_data"=>"-",
			 "show_export"=>true,
			 "rights"=>array("insert"=>"ProcessEditor","update"=>"ProcessEditor","delete"=>"ProcessEditor",),
			 "allowInsert"=>true,"allowUpdate"=>true,"allowDelete"=>true,"useForms"=>true,
			 "iter"=>array("per_page"=>"30"),
			 "MakeDVView"=>5,
		);
		if(URLParser::v("expertmode")){
			$data2["col"][] = array("name"=>Language::get("Prog. name"),"data"=>array("col"=>"id3"),"usage"=>array("MFi","MFu","MFd","DBVs","DBVe"));
			$data2["col"][] = array(
						"name"=>"Group",
						"form"=>array("type"=>"selectDB"),
						"data"=>array(
						    "col"=>"group",
							"allowNull"=>true,
							"fromTable"=>"groups",
							"fromColumn"=>"name",
							"dictionary"=>true,
						),
						"texts"=>array(
							"nullValue"=>"Select",
						),
						"usage"=>array("MFi","MFu","MFd","DBVs","DBVe"));
			$data2["col"][] = array(
						"name"=>"Process",
						"form"=>array("type"=>"selectDB"),
						"data"=>array(
						    "col"=>"process",
							"allowNull"=>true,
							"fromTable"=>"process",
							"fromColumn"=>"name",
							"dictionary"=>true,
							"where"=>array("flagentrypoint"=>"1"),
						),
						"texts"=>array(
							"nullValue"=>"Select",
						),
						"usage"=>array("MFi","MFu","MFd","DBVs","DBVe"));/**/
		}
		$form = new MakeForm($data2);$ret.=$form->show("ALL");
		
		$this->template = " ".$ret;
	}
	public function init(){
		
	}
}