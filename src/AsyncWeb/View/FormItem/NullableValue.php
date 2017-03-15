<?php

namespace AsyncWeb\View\FormItem;
use AsyncWeb\System\Language;


class NullableValue extends \AsyncWeb\View\FormItemInstance{
	public function Validate($input = null){
	  if(isset($this->item["data"]["allowNull"]) && $this->item["data"]["allowNull"] && !$input){
		return null;
	  }
      return $input;
	}
}