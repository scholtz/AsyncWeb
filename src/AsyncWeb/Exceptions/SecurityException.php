<?php
namespace AsyncWeb\Exceptions;
use AsyncWeb\Storage\Log;
class SecurityException extends \Exception {
    public function __construct($message, $code = 0, Exception $previous = null) {
        Log::log("SecurityException", $message, ML__TOP_SEC_LEVEL);
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }
}
?>