<?php
namespace AsyncWeb\DefaultBlocks\Content;
class Cat extends \AsyncWeb\Frontend\Block {
    protected $usesparams = array("c");
    protected function initTemplate() {
        if (\AsyncWeb\Menu\DBMenu5::$USES) {
            $this->template = \AsyncWeb\Article\CategoryArticle::make();
        } else {
            $this->template = "{{{Content_Main}}}";
        }
    }
    public function init() {
    }
}
