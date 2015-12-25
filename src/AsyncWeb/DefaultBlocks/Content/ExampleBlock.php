<?php
namespace AsyncWeb\DefaultBlocks\Content;


class ExampleBlock extends \AsyncWeb\Frontend\Block{
	protected function initTemplate(){
		$this->template = '<i>MY EXAMPLE BLOCK with data "{{data}}".</i>';
	}
	
	public function init(){
		$data = array("data"=>"MY EXAMPLE DATA");
		$this->setData($data);
	}
}