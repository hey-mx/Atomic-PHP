<?php
abstract class DatabaseErrorHandler {
    abstract public function exceptionTriger(ActiveRecord\DatabaseException $e);
}