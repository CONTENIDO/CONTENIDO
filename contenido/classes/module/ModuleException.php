<?php
class ModuleException extends Exception {
    
    public function __construct($message, $code, $previous) {
        parent::__construct($message, $code, $previous);
    }
}