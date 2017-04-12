<?php
namespace AsyncWeb\Helpers;
class ValueObject {
    protected $value = "";
    public function __construct($value) {
        $this->value = $value;
    }
    public function get() {
        return $this->value;
    }
}
?>