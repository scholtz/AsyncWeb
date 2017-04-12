<?php
namespace AsyncWeb\DefaultBlocks\Errors;
use AsyncWeb\System\Language;
class E404 extends \AsyncWeb\Frontend\Block {
    public static $USE_BLOCK = true;
    protected function initTemplate() {
        $this->template = '<h1>' . Language::get("404 - Not found") . '</h1>';
    }
    public function init() {
    }
}
