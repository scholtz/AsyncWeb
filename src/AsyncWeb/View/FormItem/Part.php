<?php

namespace AsyncWeb\View\FormItem;
use AsyncWeb\System\Language;


class Part extends \AsyncWeb\View\FormItemInstance{
	public function TagName(){
		return "part"; 
	}
	public function InsertForm($SubmittedValue = null){
		$Template = "View_FormItem_Part"; if(isset($this->item["form"]["template"]) && $this->item["form"]["template"]) $Template = Language::get($this->item["form"]["template"]);
		$ID = $this->MakeItemId();
		$LabelText = false;		if(isset($this->item["texts"]["name"]) && $this->item["texts"]["name"]) $LabelText = Language::get($this->item["texts"]["name"]);
		if(!$LabelText)			if(isset($this->item["name"]) && $this->item["name"]) $LabelText = Language::get($this->item["name"]);
		$Help = false;			if(isset($this->item["texts"]["help"]) && $this->item["texts"]["help"]) $Help = Language::get($this->item["texts"]["help"]);
		$After = false;			if(isset($this->item["texts"]["after"]) && $this->item["texts"]["after"]) $After = Language::get($this->item["texts"]["after"]);
		$Prepend = false;		if(isset($this->item["texts"]["prepend"]) && $this->item["texts"]["prepend"]) $Prepend = Language::get($this->item["texts"]["prepend"]);
		$AddClass = false;		if(isset($this->item["form"]["class"]) && $this->item["form"]["class"]) $AddClass = $this->item["form"]["class"];
		
		$Name = $ID;
		if(isset($this->item["data"]["var"]) && $this->item["data"]["var"]) $Name = $this->item["data"]["var"];
		
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
			"BT_SIZE"=>$this->item["BT_SIZE"],
			"BT_WIDTH_OF_LABEL"=>$this->item["BT_WIDTH_OF_LABEL"],
			"BT_WIDTH_9"=>$this->item["BT_WIDTH_9"],
			"BT_WIDTH_10"=>$this->item["BT_WIDTH_10"],
		),false,false);		
	}
}