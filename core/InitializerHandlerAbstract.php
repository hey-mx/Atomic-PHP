<?php
abstract class InitializerHandlerAbstract implements IConfigurable {
    protected $config;

    public function setConfig(Core $configObject) {
        $this->config = $configObject;
    }

    abstract public function execute();
}
