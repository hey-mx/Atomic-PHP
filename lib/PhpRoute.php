<?php
require_once 'php-route/Route.php';
class PhpRoute extends Route
{

    protected $staticElements=array();

    public function addStaticElement($key,$value){
        $this->staticElements[$key] = $value;
        return $this;
    }
    public function getStaticElements()
    {
        return $this->staticElements;
    }
    
    public function getMapArguments()
    {        
        
        return array_merge(parent::getMapArguments(),  $this->staticElements);
    }
}
