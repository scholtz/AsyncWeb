<?php
namespace AsyncWeb\DefaultBlocks;


class LangMenu extends \AsyncWeb\Frontend\Block{
	public static $USE_BLOCK = true;
	protected function initTemplate(){
		$menu = \AsyncWeb\Menu\MainMenu::showLangMenu();
		$this->template = $menu;
	}
}