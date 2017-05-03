<?php
namespace AsyncWeb\DefaultBlocks\Layout;
class Head extends \AsyncWeb\Frontend\Block {
    public $blockElement = "head";
    public static $USE_SIDEBAR = true;
    public static $USE_DATATABLE = true;
    public static $USE_WEBSOCKET = false;
    public static $USE_BOWER = false;
    protected function initTemplate() {
        $this->template = '<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{{Content_HTMLHeader_Title}}}{{{Content_HTMLHeader_Description}}}{{{Content_HTMLHeader_Keywords}}}' . (Head::$USE_BOWER ? '
    <link href="/bower_components/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<script src="/bower_components/jquery/dist/jquery.min.js" type="text/javascript"></script>
    <link href="/bower_components/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <script async type="text/javascript" src="/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>'.(file_exists("/bower_components/jasny-bootstrap/dist/css/jasny-bootstrap.min.css")?'<link href="/bower_components/jasny-bootstrap/dist/css/jasny-bootstrap.min.css" rel="stylesheet">
    <script async type="text/javascript" src="/bower_components/jasny-bootstrap/dist/js/jasny-bootstrap.min.js"></script>':'') : '
    <link href="//netdna.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.css" rel="stylesheet">
	<script src="//code.jquery.com/jquery-latest.min.js" type="text/javascript"></script>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
    <script async type="text/javascript" src="//netdna.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jasny-bootstrap/3.1.3/js/jasny-bootstrap.min.js"></script>
	') . (HEAD::$USE_SIDEBAR ? '
	<!-- SideBar -->
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/metisMenu/2.0.0/metisMenu.min.css">
	<script async src="//cdnjs.cloudflare.com/ajax/libs/metisMenu/2.0.0/metisMenu.min.js"></script>
	<link rel="stylesheet" href="/assets/css/metisMenu.css">
	' : '') . '
	
	' . (HEAD::$USE_DATATABLE ? '
	<!-- DataTable -->
	<script src="/assets/js/datatable.js"></script>
    <link rel="stylesheet" href="/assets/css/datatable.css">
	' : '') . '
	<!-- Templating engine -->
    <script src="/assets/js/mustache.js"></script>
	' . (HEAD::$USE_WEBSOCKET ? '
	<!-- AsyncWeb scripts -->
	
    <script src="/assets/js/reconnecting-websocket.js"></script>
    <script src="/assets/js/AWLibrary.js"></script>
	' : '') . '
	' . (HEAD::$USE_SIDEBAR ? '
    <link rel="stylesheet" href="/assets/css/sidebar.css">
	' : '') . \AsyncWeb\HTML\Headers::show() . '
    <link rel="stylesheet" href="/css/style.css">
	';
    }
}
