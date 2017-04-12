<?php
namespace AsyncWeb\Menu;
abstract class MenuBuilder {
    abstract public function makeTopMenu(&$menu);
    abstract public function makeLeftMenu(&$menu);
    abstract public function makeNavigator(&$menu);
    abstract public function getCurrent();
    abstract public function check();
    abstract public function installDefaultValues();
}
