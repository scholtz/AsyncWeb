<?php

namespace AsyncWeb\View\FormItem;
use AsyncWeb\System\Language;


class NullableValue extends \AsyncWeb\View\FormItemInstance{
	public function Validate($input = null){
	  $input = \AsyncWeb\Frontend\URLParser::v($input);
	  if(isset($this->item["data"]["allowNull"]) && $this->item["data"]["allowNull"] && !$input){
		return null;
	  }
      return $input;
	}
}