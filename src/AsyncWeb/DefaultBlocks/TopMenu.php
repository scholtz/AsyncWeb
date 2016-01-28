<?php
namespace AsyncWeb\DefaultBlocks;

class TopMenu extends \AsyncWeb\Frontend\Block{
	public static $USE_BLOCK = true;
	protected function initTemplate(){
		$menu = \AsyncWeb\Menu\MainMenu::showTopMenu();
		$this->template = " ".$menu;
	}
}