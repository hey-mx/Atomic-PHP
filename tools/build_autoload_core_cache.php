<?php
require dirname(__FILE__) . '/../Atomic.php';

$app = Atomic::getInstance('bootstrap.php');
try {
    $app->run();
} catch(AtRouteNotFound $e) {

}