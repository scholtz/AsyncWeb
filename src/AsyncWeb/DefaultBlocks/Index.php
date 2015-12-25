<?php
namespace AsyncWeb\DefaultBlocks;


class Index extends \AsyncWeb\Frontend\Block{
	protected function initTemplate(){
		$this->template = '<!DOCTYPE html>
<html lang="{{LANG}}">
<head>
{{{Layout_Head}}}
</head>
<body>
<div class="page">
{{{Layout_Header}}}
<div id="wrapper">
{{{Layout_SideBar}}}
<div class="container page-content-wrapper">
{{{Cat}}}
{{{Layout_Footer}}}
</div>
</div>
</div>
</body>
</html>';
	}
}