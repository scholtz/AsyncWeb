<?php
namespace AsyncWeb\View;
define("DV_OP_EQUAL", "eq");
define("DV_OP_NOT_EQUAL", "neq");
define("DV_OP_LTEQ", "lte");
define("DV_OP_LT", "lt");
define("DV_OP_GTEQ", "gte");
define("DV_OP_GT", "gt");
define("DV_OP_LIKE", "like");
define("DV_OP_NOT_LIKE", "notlike");
define("DV_OP_NULL", "null");
define("DV_OP_IS", "is");
define("DV_OP_IS_NOT", "isnot");
define("DV_BINDING_AND", "and");
define("DV_BINDING_OR", "or");
define("DV_BINDING_AND_LB", "andlz");
define("DV_BINDING_RB_AND", "pzand");
define("DV_BINDING_OR_LB", "orlz");
define("DV_BINDING_RB_OR", "pzor");
define("DV_BINDING_NONE", "not");
class ViewConfig {
    public static $useFontAwesome = true;
    private $config = array();
    public function __construct($config = array()) {
        $this->config = $config;
    }
    public function set($k, $v) {
        $this->config[$k] = $v;
    }
    public function get() {
        return $this->config;
    }
    public function getValue($key, $default = null) {
        if (array_key_exists($key, $this->config)) return $this->config[$key];
        return $default;
    }
}
?>