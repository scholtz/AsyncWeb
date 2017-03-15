<?php

namespace AsyncWeb\View\FormItem;
use AsyncWeb\System\Language;


class SelectDB extends \AsyncWeb\View\FormItem\NullableValue{
	public function TagName(){
		return "selectDB";
	}
	
	public function InsertForm($SubmittedValue = null){
		$Template = "View_FormItem_Select"; if(isset($this->item["form"]["template"]) && $this->item["form"]["template"]) $Template = Language::get($this->item["form"]["template"]);
		$ID = $this->MakeItemId();
		$LabelText = false;		if(isset($this->item["texts"]["name"]) && $this->item["texts"]["name"]) $LabelText = Language::get($this->item["texts"]["name"]);
		if(!$LabelText)			if(isset($this->item["name"]) && $this->item["name"]) $LabelText = Language::get($this->item["name"]);
		$Help = false;			if(isset($this->item["texts"]["help"]) && $this->item["texts"]["help"]) $Help = Language::get($this->item["texts"]["help"]);
		$After = false;			if(isset($this->item["texts"]["after"]) && $this->item["texts"]["after"]) $After = Language::get($this->item["texts"]["after"]);
		$Prepend = false;		if(isset($this->item["texts"]["prepend"]) && $this->item["texts"]["prepend"]) $Prepend = Language::get($this->item["texts"]["prepend"]);
		$AddClass = false;		if(isset($this->item["form"]["class"]) && $this->item["form"]["class"]) $AddClass = $this->item["form"]["class"];
		$Type = "password";
		
		$DataPlacement = false;	if($Help) $DataPlacement = "bottom";

		$Editable = true;		if(isset($this->item["editable"]) && !$this->item["editable"]){ $Editable = false; }
		
		$Options = array();
		foreach($this->item["filter"]["option"] as $k=>$v){
			$v = Language::get($v);
			$option = array();
			$option["OptionID"] = $k;
			if($SubmittedValue == $k){
				$option["OptionSelected"] = true;
			}else if(isset($this->item["texts"]["default"]) && ($this->item["texts"]["default"] == $k || $this->item["texts"]["default"] == $v)){
				$option["OptionSelected"] = true;
			}
			$option["OptionText"] = strip_tags($v);
			
			$Options[] = $option;
		}
		

		$options = array();
		$col = $this->item["data"]["fromColumn"];
		$index = "id2";
		if(!isset($this->item["data"]["where"])) $this->item["data"]["where"] = array();
		$res = DB::g($this->item["data"]["fromTable"],$this->item["data"]["where"],null,null,@$this->item["data"]["order"]);

		while($row = DB::fetch_assoc($res)){
			if((isset($this->item["data"]["isText"]) && $this->item["data"]["isText"]) || (isset($this->item["data"]["dictionary"]) && $this->item["data"]["dictionary"])){
				$options[$row[$index]] = strip_tags(Language::get($row[$col],true));
			}else{
				$options[$row[$index]] = $this->getInnerDBColConfig($row,$col);
			}
		}
		asort($options);
		$reto = "";
		$sel = false;
		foreach($options as $k=>$v){
			$k = "".$k;
			$option["OptionID"] = $k;
			if($SubmittedValue == $k){
				$option["OptionSelected"] = true;
			}else if(isset($this->item["texts"]["default"]) && ($this->item["texts"]["default"] == $k || $this->item["texts"]["default"] == $v || Language::get($this->item["texts"]["default"]) == $k || Language::get($this->item["texts"]["default"]) == $v)){
				$option["OptionSelected"] = true;
			}
			$option["OptionText"] = strip_tags($v);
			
			
			$Options[] = $option;
		}
		
		
		if(isset($this->item["data"]["allowNull"]) && $this->item["data"]["allowNull"]){
			if(isset($this->item["texts"]["nullValue"]) && $this->item["texts"]["nullValue"]){
				$Options[] = array("OptionID"=>"0","OptionText"=>Language::get( $this->item["texts"]["nullValue"]));
			}else{
				$Options[] = array("OptionID"=>"0","OptionText"=>Language::get("nullValue"));
			}
		}
		
		
		$Name = $ID;
		if(isset($this->item["data"]["var"]) && $this->item["data"]["var"]) $Name = $this->item["data"]["var"];
		
		$DataValidation = $this->DataValidation();
		
		return \AsyncWeb\Text\Template::loadTemplate($Template,array(
			"LabelText"=>$LabelText,
			"Prepend"=>$Prepend,
			"After"=>$After,
			"ID"=>$ID,
			"AddClass"=>$AddClass,
			"Name"=>$Name,
			"Title"=>$Help,
			"Placeholder"=>$Help,
			"DataContent"=>$Help,
			"DataPlacement"=>$DataPlacement,
			"Disabled"=>!$Editable,
			"Options"=>$Options,
			"Multiple"=>false,
			"BT_SIZE"=>$this->item["BT_SIZE"],
			"BT_WIDTH_OF_LABEL"=>$this->item["BT_WIDTH_OF_LABEL"],
			"BT_WIDTH_9"=>$this->item["BT_WIDTH_9"],
			"BT_WIDTH_10"=>$this->item["BT_WIDTH_10"],
			"DataValidation"=>$DataValidation,
		),false,false);		
	}
}