<?php
/*
 * Atomic Controller abstract class
 */
abstract class AtController
{
    protected $config;
    protected $view;
    protected $router;
    private $helpers = array();

    public function setConfigInstance(Core $config)
    {
        $this->config = $config;
        $this->InitModule();
    }

    protected function InitModule()
    {
        $this->view = ViewManager::GetInstance($this->config);
        $this->router = RouterManager::GetInstance($this->config)
            ->GetPhpRouterInstance();
    }

    protected function Display($template, $vars=array(), $display=true)
    {
        $vars['module'] = $this;
        $this->view->GetSmartyInstance()->assign($vars);
        if(!$display) {
            return $this->view->GetSmartyInstance()->fetch($template);
        } else {
            $this->view->GetSmartyInstance()->display($template);
        }
    }

    protected function redirect($module, $action='index', $arguments=array(), $type = '')
    {
        $url = '/' . $module . '/' . $action;
        if(!empty($arguments)) {
            $url .= join('/', $arguments);
        }
        $this->redirectToUrl($url, $type);
    }

    protected function redirectToUrl($url, $type='') {
        throw new AtRedirectRequestException($url, $type);
    }

    protected function LoadHelper($helperName, $accessName)
    {
        if(!array_key_exists($accessName, $this->helpers))
        {
            $this->helpers[$accessName] = new $helperName();
            $this->helpers[$accessName]->setConfig($this->config);
        }
    }

    public function SetConfigToObject($object)
    {
        if($object instanceof IConfigurable)
            $object->setConfig($this->config);
        else
            throw new Exception($object . ' can\'t add the config system variables');
    }

    public function __get($name)
    {
        if(array_key_exists($name, $this->helpers))
            return $this->helpers[$name];
    }

    public function __toString()
    {
        return get_class($this);
    }
}
?>
