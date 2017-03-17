<?php

namespace AsyncWeb\View\FormItem;
use AsyncWeb\System\Language;


class File extends \AsyncWeb\View\FormItemInstance{
	public function TagName(){
		return "file";
	}
	
	protected $newFilename = null;
	protected $name = null;
	
	public function Validate($input = null){
		$allowedExt = array("jpg","png","gif","pdf","xls","xlsx","doc","docx","txt","zip","rar");
		if(isset($this->item["data"]["allowed"])) $allowedExt = $this->item["data"]["allowed"];

		$this->name = $input;

		if(isset($this->item["data"]["allowNull"]) && $this->item["data"]["allowNull"] && (!isset($_FILES[$this->name]['name']) || !$_FILES[$this->name]['name'])){
			return null;
		}

		$this->newFilename = $_FILES[$this->name]['name'];

		$info = \pathinfo($_FILES[$this->name]['name']);
		$ext = $info["extension"];
		if(!in_array($ext,$allowedExt)){
			$text = "fileNotAllowed";
			if(isset($this->item["texts"][$text])) $text = $this->item["texts"][$text];
			throw new \Exception(Language::get($text));
		}
		if(isset($this->item["data"]["dir"]) && $this->item["data"]["dir"] && !is_dir($this->item["data"]["dir"])){
			mkdir($this->item["data"]["dir"],true);
		}
		if($this->item["data"]["makeunique"]){
			$this->newFilename = md5_file($_FILES[$this->name]['tmp_name'])."_".Texts::clear(substr($_FILES[$this->name]['name'],0,-1*strlen($ext)-1)).".$ext";
		}
		while($this->item["data"]["makeunique"] && is_file($this->item["data"]["dir"].$this->newFilename)){
			$this->newFilename = md5(uniqid())."_".Texts::clear(substr($_FILES[$this->name]['name'],0,-1*strlen($ext)-1)).".$ext";
		}
		
		$info = \pathinfo($this->newFilename);

		if(is_file($this->item["data"]["dir"].$this->newFilename)){
			if($this->item["data"]["overwrite"]){
			//DB::query("delete from `$table` where (name = '$this->newFilename' and ".$this->aditional_where.")");
			}else{
				$text = "fileExistsException";
				if(isset($this->item["texts"][$text])) $text = $this->item["texts"][$text];
				throw new \Exception(Language::get($text));
			}
		}

		$this->MoveFile();
		return $this->SaveFileInfoToDB();
	  
	}
	protected function MoveFile(){
		$uploadfile = $this->item["data"]["dir"].$this->newFilename;
		if(!move_uploaded_file($_FILES[$this->name]['tmp_name'], $uploadfile)){
			$text = "errorWhileMovingFile";
			if(isset($this->item["texts"][$text])) $text = $this->item["texts"][$text];
			throw new \Exception(Language::get($text));
		}	  
	}
	protected function SaveFileInfoToDB(){
		$table = $this->item["data"]["tableForFiles"];
		$uploadfile = $this->item["data"]["dir"].$this->newFilename;
		DB::u($table,$pid = md5(uniqid()),array("md5"=>md5_file($uploadfile),"size"=>filesize($uploadfile),"type"=>$_FILES[$this->name]['type'],"name"=>$_FILES[$this->name]['name'],"path"=>$uploadfile,"fullpath"=>str_replace("\\","/",realpath($uploadfile))));
		return $pid;
	}
	
	public function InsertForm($SubmittedValue = null){
		$Template = "View_FormItem_InputBox"; if(isset($this->item["form"]["template"]) && $this->item["form"]["template"]) $Template = Language::get($this->item["form"]["template"]);
		$ID = $this->MakeItemId();
		$LabelText = false;		if(isset($this->item["texts"]["name"]) && $this->item["texts"]["name"]) $LabelText = Language::get($this->item["texts"]["name"]);
		if(!$LabelText)			if(isset($this->item["name"]) && $this->item["name"]) $LabelText = Language::get($this->item["name"]);
		$Help = false;			if(isset($this->item["texts"]["help"]) && $this->item["texts"]["help"]) $Help = Language::get($this->item["texts"]["help"]);
		$Placeholder = false;			if(isset($this->item["texts"]["placeholder"]) && $this->item["texts"]["placeholder"]) $Help = Language::get($this->item["texts"]["placeholder"]);if(!$Placeholder) $Placeholder = $Help;
		$After = false;			if(isset($this->item["texts"]["after"]) && $this->item["texts"]["after"]) $After = Language::get($this->item["texts"]["after"]);
		$Prepend = false;		if(isset($this->item["texts"]["prepend"]) && $this->item["texts"]["prepend"]) $Prepend = Language::get($this->item["texts"]["prepend"]);
		$AddClass = false;		if(isset($this->item["form"]["class"]) && $this->item["form"]["class"]) $AddClass = $this->item["form"]["class"];
		$Type = $this->item["data"]["datatype"];
		$MFType = "MFFile";
		
		$Value = false; // file needs to be resent

		$MinLength = false;		if(isset($this->item["data"]["minlength"]) && $this->item["data"]["minlength"]) $MinLength = $this->item["data"]["minlength"];
		$MaxLength = false;		if(isset($this->item["data"]["maxlength"]) && $this->item["data"]["maxlength"]) $MaxLength = $this->item["data"]["maxlength"];
		$Min = false;			if(isset($this->item["data"]["minnum"]) && $this->item["data"]["minnum"]) $Min = $this->item["data"]["minnum"];
		$Max = false;			if(isset($this->item["data"]["maxnum"]) && $this->item["data"]["maxnum"]) $Max = $this->item["data"]["maxnum"];
		$Step = false;			if(isset($this->item["data"]["step"]) && $this->item["data"]["step"]) $Step = $this->item["data"]["step"];
		
		$DataPlacement = false;	if($Help) $DataPlacement = "bottom";

		$Editable = true;		if(isset($this->item["editable"]) && !$this->item["editable"]){ $Editable = false; }
		
		$Name = $ID;
		if(isset($this->item["data"]["var"]) && $this->item["data"]["var"]) $Name = $this->item["data"]["var"];
		
		$DataValidation = $this->DataValidation();
		
		return \AsyncWeb\Text\Template::loadTemplate($Template,array(
			"LabelText"=>$LabelText,
			"Prepend"=>$Prepend,
			"After"=>$After,
			"ID"=>$ID,
			"AddClass"=>$AddClass,
			"Type"=>$Type,
			"Name"=>$Name,
			"Value"=>$Value,
			"MinLength"=>$MinLength,
			"MaxLength"=>$MaxLength,
			"Min"=>$Min,
			"Max"=>$Max,
			"Step"=>$Step,
			"Title"=>$Help,
			"Placeholder"=>$Placeholder,
			"DataContent"=>$Help,
			"DataPlacement"=>$DataPlacement,
			"Disabled"=>!$Editable,
			"MFType"=>$MFType,
			"BT_SIZE"=>$this->item["BT_SIZE"],
			"BT_WIDTH_OF_LABEL"=>$this->item["BT_WIDTH_OF_LABEL"],
			"BT_WIDTH_9"=>$this->item["BT_WIDTH_9"],
			"BT_WIDTH_10"=>$this->item["BT_WIDTH_10"],			
			"DataValidation"=>$DataValidation,
		),false,false);		
	}
}