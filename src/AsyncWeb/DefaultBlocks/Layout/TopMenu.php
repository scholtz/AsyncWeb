<?php
namespace AsyncWeb\DefaultBlocks\Layout;

class TopMenu extends \AsyncWeb\Frontend\Block{
	protected function initTemplate(){
		$menu = \AsyncWeb\Menu\MainMenu::showTopMenu();
		$this->template = " ".$menu;
	}
}