<?php
namespace AsyncWeb\DefaultBlocks;

class Msg extends \AsyncWeb\Frontend\Block{
	public function initTemplate(){
		$this->template = \AsyncWeb\Text\Messages::getInstance()->show()." ";
	}
}