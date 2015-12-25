<?php
namespace AsyncWeb\DefaultBlocks\Content\HTMLHeader;

class Description extends \AsyncWeb\Frontend\Block{
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