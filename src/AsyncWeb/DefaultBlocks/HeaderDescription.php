<?php
namespace AsyncWeb\DefaultBlocks;

class HeaderDescription extends \AsyncWeb\Frontend\Block{
	public function overRideOuterBlock(){
		return true;
	}
	public function initTemplate(){
		$this->template='{{#description}}
		<meta name="description" content="{{description}}" />{{/description}}';
	}
	public function init(){
		$data = array("description"=>"");
		$this->setData($data);
	}
}