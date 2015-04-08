<?php
namespace AsyncWeb\DefaultBlocks;


class ExampleBlock extends \AsyncWeb\Frontend\Block{
	public static $USE_BLOCK = true;
	protected function initTemplate(){
		$this->template = '<i>MY EXAMPLE BLOCK with data "{{data}}".</i>';
	}
	
	public function init(){
		$data = array("data"=>"MY EXAMPLE DATA");
		$this->setData($data);
	}
}