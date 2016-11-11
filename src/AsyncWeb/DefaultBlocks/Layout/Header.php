<?php
namespace AsyncWeb\DefaultBlocks\Layout;


class Header extends \AsyncWeb\Frontend\Block{
	public static $USE_BLOCK = true;
	public static $USE_FLUID = false;
	public static $LOGO = false;
	public static $LOGO_TITLE = false;
	protected function initTemplate(){
		
		$title = Header::$LOGO_TITLE;
		if(!$title) $title = "Logo";
		
		$this->template = '<header class="navbar navbar-inverse" id="top" role="banner">
  <div class="container'.(Header::$USE_FLUID?'-fluid':'').'">
    <div class="navbar-header">
      <button class="navbar-toggle collapsed" type="button" data-toggle="collapse" data-target=".bs-navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a href="/" class="logo">'.(Header::$USE_FLUID|| !Header::$LOGO?'<i style="font-size:40px; margin:3px 20px 3px 0" class="fa fa-rocket"></i>':'<img class="header_logo" height="30px" src="'.Header::$LOGO.'" title="'.$title.'" alt="'.$title.'">').'</a>
    </div>
    <nav class="collapse navbar-collapse bs-navbar-collapse">
	{{{Layout_TopMenu}}}
	{{{Layout_LangMenu}}}
    </nav>
  </div>
</header>';
	}
}
