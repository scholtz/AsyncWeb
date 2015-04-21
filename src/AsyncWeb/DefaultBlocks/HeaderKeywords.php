<?php
namespace AsyncWeb\DefaultBlocks;

class HeaderKeywords extends \AsyncWeb\Frontend\Block{
	public function overRideOuterBlock(){
		return true;
	}
	public function initTemplate(){
		$this->template='{{#keywords}}<meta name="keywords" content="{{keywords}}" />{{/keywords}}';
	}
	public function init(){
		$data = array("keywords"=>"");
		$this->setData($data);
	}
}