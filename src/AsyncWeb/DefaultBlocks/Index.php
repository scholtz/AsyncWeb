<?php
namespace AsyncWeb\DefaultBlocks;
class Index extends \AsyncWeb\Frontend\Block {
    protected function initTemplate() {
        $this->template = '<!DOCTYPE html>
<html lang="{{LANG}}">
{{{Layout_Head}}}
<body>
<div class="page">
{{{Layout_Header}}}
<div id="wrapper">
{{{Layout_SideBar}}}
<div class="container page-content-wrapper">
{{{Content_Msg}}}
{{{Content_Cat}}}
{{{Layout_Footer}}}
</div>
</div>
</div>
{{{Layout_Scripts}}}
</body>
</html>';
    }
}
