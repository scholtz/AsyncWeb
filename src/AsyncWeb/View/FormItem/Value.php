<?php

namespace AsyncWeb\View\FormItem;
use AsyncWeb\System\Language;

class Value extends \AsyncWeb\View\FormItemInstance{
	public function TagName(){
		return "value";
	}
	public function Validate($input = null){
		if(isset($this->item["texts"]["value"])){//value neprehodnocuj podla Language::get
			return $this->item["texts"]["value"];
		}else{
			return Language::get($this->item["texts"]["text"]);
		}
		return $input;
	}
	
}