<?php
namespace AsyncWeb\DefaultBlocks;


class Index extends \AsyncWeb\Frontend\Block{
	public static $USE_BLOCK = true;
	protected function initTemplate(){
		$this->template = '<!DOCTYPE html>
<html lang="{{LANG}}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{{HeaderTitle}}}{{{HeaderDescription}}}{{{HeaderKeywords}}}
    <link href="//netdna.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.css" rel="stylesheet">
	<script src="//code.jquery.com/jquery-latest.min.js" type="text/javascript"></script>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css" rel="stylesheet">
    <script async type="text/javascript" src="//netdna.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jasny-bootstrap/3.1.3/js/jasny-bootstrap.min.js"></script>

	<!-- SideBar -->
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/metisMenu/2.0.0/metisMenu.min.css">
	<script async src="//cdnjs.cloudflare.com/ajax/libs/metisMenu/2.0.0/metisMenu.min.js"></script>
	<link rel="stylesheet" href="/assets/css/metisMenu.css">
	
	<!-- DataTable -->
	<script src="/assets/js/datatable.js"></script>
    <link rel="stylesheet" href="/assets/css/datatable.css">


	<!-- AsyncWeb scripts -->
	
    <script src="/assets/js/reconnecting-websocket.js"></script>
    <script src="/assets/js/mustache.js"></script>
    <script src="/assets/js/AWLibrary.js"></script>
	
    <link rel="stylesheet" href="/assets/css/sidebar.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
{{{Header}}}
<div id="wrapper">
{{{SideBar}}}
<div class="container page-content-wrapper">
{{{Cat}}}
</div>
</div>
{{{Footer}}}
</body>
</html>';
	}
}