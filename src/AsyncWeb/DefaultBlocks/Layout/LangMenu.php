<?php
namespace AsyncWeb\DefaultBlocks\Layout;
use AsyncWeb\System\Language;


class LangMenu extends \AsyncWeb\Frontend\Block{
	protected function initTemplate(){
		if(count(Language::$SUPPORTED_LANGUAGES) <= 1){
			$this->template = '<!-- Only one language is active: not showing the lang menu -->';
			return;
		}
		$menu = \AsyncWeb\Menu\MainMenu::showLangMenu();
		$this->template = $menu;
	}
}