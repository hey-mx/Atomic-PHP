<?php
require_once 'php-route/Route.php';
class PhpRoute extends Route
{

    protected $staticElements=array();
	/**
	* Funcion para agregar variables estáticas en los argumentos
	*/
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
        // combina lo que regresa el router +  las variables estáticas
        return array_merge(parent::getMapArguments(),  $this->staticElements);
    }
}
