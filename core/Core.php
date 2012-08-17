<?php
class Core
{
    private static $coreInstance = null;
    private $configValues = array();
    private function  __construct($systemInfo) {
        $this->readConfig($systemInfo);
    }
    private function readConfig($systemInfo)
    {
        $this->configValues = $systemInfo;
    }
    public function Value($key)
    {
        if(is_array($this->configValues))
        {
            if(!empty($this->configValues))
            {
                if(array_key_exists($key, $this->configValues))
                {
                    return $this->configValues[$key];
                }
            }
        }
        return NULL;
    }
    public static function getInstance($systemInfo)
    {
        if(empty(self::$coreInstance))
        {
            self::$coreInstance = new Core($systemInfo);
        }
        return self::$coreInstance;
    }
}
?>