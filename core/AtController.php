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
        return $this->view->GetSmartyInstance()->isCached($template, 
            (!empty($cacheId) ? $cacheId : null));
    }

    protected function ClearViewCache($template, $cacheId='') {
        $this->view->GetSmartyInstance()->clearCache($template, 
                (!empty($cacheId) ? $cacheId : null));
    }

    protected function InitModule()
    {
        $this->view = ViewManager::GetInstance($this->config);
        $this->router = RouterManager::GetInstance($this->config)
            ->GetPhpRouterInstance();
    }

    protected function Display($template, $vars=array(), $display=true, $cacheTime = 0, $cacheId = '',
        $clearCache = false)
    {
        if ($cacheTime > 0 && $clearCache) {
            $this->ClearViewCache($template, $cacheId);
        }
        if ($cacheTime > 0) {
            $this->view->GetSmartyInstance()->caching = 1;
            $this->view->GetSmartyInstance()->setCacheLifetime($cacheTime);
        } else {
            $this->view->GetSmartyInstance()->caching = 0;
        }
        $vars['module'] = array('value' => $this, 'onviewcache' => true);
        foreach($vars as $key => $value) {
            if (is_array($value) && array_key_exists('onviewcache', $value)) {
                $onCache = $value['onviewcache'];
                $realValue = array_key_exists('value', $value)
                    ? $value['value'] : null;
                if ($realValue != null) {
                    $this->view->GetSmartyInstance()->assign($key, $realValue, $onCache);
                }
            } elseif ($cacheTime == 0 || !$this->IsViewOnCache($template, $cacheId)) {
                $this->view->GetSmartyInstance()->assign($key, $value);
            }
        }
        if(!$display) {
            return $this->view->GetSmartyInstance()->fetch($template,
                (!empty($cacheId) && $cacheTime > 0 ? $cacheId : null ));
        } else {
            $this->view->GetSmartyInstance()->display($template, 
                (!empty($cacheId) && $cacheTime > 0 ? $cacheId : null ));
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
