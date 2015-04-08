<?php
namespace AsyncWeb\DefaultBlocks;


class SideBar extends \AsyncWeb\Frontend\Block{
	public static $USE_BLOCK = true;
	protected function initTemplate(){
		$menu = \AsyncWeb\Menu\MainMenu::showLeftMenu();
		$this->template = $menu;
	}
}