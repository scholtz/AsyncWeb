<?php
namespace AsyncWeb\Storage;
public interface LogInterface {
    public function log($name, $text, $priority);
}
?>