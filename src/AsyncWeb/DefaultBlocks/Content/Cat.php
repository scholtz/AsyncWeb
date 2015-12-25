<?php

namespace AsyncWeb\DefaultBlocks\Layout;

class Cat extends \AsyncWeb\Frontend\Block{
	protected $usesparams = array("c");
	protected function initTemplate(){
		$this->template = \AsyncWeb\Article\CategoryArticle::make();
	}
	public function init(){
		
	}
}