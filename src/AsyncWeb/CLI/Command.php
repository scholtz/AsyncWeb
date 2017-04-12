<?php
namespace AsyncWeb\CLI;
interface Command {
    public function execute();
    public function help();
}
