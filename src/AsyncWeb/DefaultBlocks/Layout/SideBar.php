<?php
namespace AsyncWeb\DefaultBlocks\Layout;


class SideBar extends \AsyncWeb\Frontend\Block{
	protected function initTemplate(){
		if(!\AsyncWeb\Menu\MainMenu::countBuilders()){
			$this->template = " ";
			return;
		}
		$menu = \AsyncWeb\Menu\MainMenu::showLeftMenu();
		$menu .='<script>
$(document).ready(function(){
	$("#menu2").metisMenu();
	$(".sidebar").css("min-height",($( document ).height() - $(".sidebar").position().top)+"px");
});
</script>';
		$this->template = $menu;
	}
}