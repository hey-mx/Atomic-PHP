<?php
abstract class DatabaseErrorHandler {
    abstract public function exceptionTrigger($activeRecodConfig, 
        ActiveRecord\DatabaseException $e);
}