<?php
namespace AsyncWeb\DefaultBlocks;
class Logout extends \AsyncWeb\Frontend\Block {
    protected function initTemplate() {
        \AsyncWeb\HTTP\Header::s("location", "/logout=1");
        exit;
    }
}
