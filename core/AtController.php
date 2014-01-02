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
    
    protected function IsViewOnCache($template, $cacheId='') {
        return $this->view->GetSmartyInstance(true)->isCached($template, 
            (!empty($cacheId) ? $cacheId : null));
    }

    protected function ClearViewCache($template, $cacheId='') {
        $this->view->GetSmartyInstance(true)->clearCache($template, 
                (!empty($cacheId) ? $cacheId : null));
    }

    protected function InitModule()
    {
        $this->view = ViewManager::GetInstance($this->config);
        $this->router = RouterManager::GetInstance($this->config)
            ->GetPhpRouterInstance();
    }

    protected function Display($template, $vars=array(), $display=true, $usingCache=false, $cacheId = '',
        $cacheTime = 0, $clearCache = false)
    {
        if($usingCache && $cacheTime > 0) {
            $this->view->GetSmartyInstance($usingCache)->setCacheLifetime($cacheTime);
        }
        if($usingCache && $clearCache) {
            $this->ClearViewCache($template, $cacheId);
        }
        $vars['module'] = array('value' => $this, 'nocache' => false);
        foreach($vars as $key => $value) {
            $noCache = false;
            $realValue = null;
            if (is_array($value) && array_key_exists('nocache', $value)) {
                $noCache = $value['nocache'];
                $realValue = array_key_exists('value', $value)
                    ? $value['value'] : null;
            } else {
                $realValue = $value;
            }
            $this->view->GetSmartyInstance($usingCache)->assign($key, $realValue, $noCache);
        }
        if(!$display) {
            return $this->view->GetSmartyInstance($usingCache)->fetch($template,
                (!empty($cacheId) && $usingCache ? $cacheId : null ));
        } else {
            $this->view->GetSmartyInstance($usingCache)->display($template, 
                (!empty($cacheId) && $usingCache ? $cacheId : null ));
        }
    }

    protected function redirect($module, $action='index', $arguments=array(), $type = '')
    {
        $url = '/' . $module . '/' . $action;
        if(!empty($arguments)) {
            $url .= '/'. join('/', $arguments);
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
