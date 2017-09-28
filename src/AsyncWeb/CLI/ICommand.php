<?php
namespace AsyncWeb\CLI;
interface ICommand {
    public function execute();
    public function help();
}
