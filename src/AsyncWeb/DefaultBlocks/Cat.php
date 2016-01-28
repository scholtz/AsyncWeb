<?php

namespace AsyncWeb\DefaultBlocks;

class Cat extends \AsyncWeb\Frontend\Block{
	public static $USE_BLOCK = true;
	protected $usesparams = array("c");
	protected function initTemplate(){
		$this->template = \AsyncWeb\Article\CategoryArticle::make();
	}
	public function init(){
		
	}
}