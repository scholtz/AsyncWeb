<?php
namespace AsyncWeb\Bank\Statement;
interface ProcessorInterface {
    public function NewTransactionCallback($callback);
    public function ProcessStatement();
}
