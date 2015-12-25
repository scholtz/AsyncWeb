<?php
namespace AsyncWeb\DefaultBlocks\Content;

class Msg extends \AsyncWeb\Frontend\Block{
	public function initTemplate(){
		$this->template = \AsyncWeb\Text\Messages::getInstance()->show()." ";
	}
}