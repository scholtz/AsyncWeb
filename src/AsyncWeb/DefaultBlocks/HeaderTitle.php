<?php
namespace AsyncWeb\DefaultBlocks;

class HeaderTitle extends \AsyncWeb\Frontend\Block{
	public function overRideOuterBlock(){
		return true;
	}
	public function initTemplate(){
		$this->template='<title id="T_HeaderTitle">{{title}}</title>';
	}
	public function init(){
		$data = array("title"=>"My website");
		$this->setData($data);
	}
}