<?php
namespace AsyncWeb\DefaultBlocks\Content;
class Msg extends \AsyncWeb\Frontend\Block {
    public function initTemplate() {
        $this->template = '<div class="messages">' . \AsyncWeb\Text\Messages::getInstance()->show() . "</div>";
    }
}
