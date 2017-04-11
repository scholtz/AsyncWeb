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
			$ret = Language::get($this->item["texts"]["text"]);
			if(substr($ret,0,3)=="PHP"){
				$ret= \AsyncWeb\System\Execute::run($ret,$params,false);
			}
			return $ret;
		}
		$input = \AsyncWeb\Frontend\URLParser::v($input);
		return $input;
	}
	
}