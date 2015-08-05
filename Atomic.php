<?php
class Atomic {
    
    private static $PhpPhantInstance;
    private $router;
    private static $system;
    private $config;
    private $cfgAR;
    private $controller;
    private $action;
    private $autoloadManager;

    public static function activerecord_lib_autoload($class_name)
    {
        $lib_path = CORE_LIB_PATH . DIRECTORY_SEPARATOR . 'activerecord/';
        if (strpos($class_name, 'ActiveRecord') !== FALSE) 
        {
            $class = substr($class_name, strpos($class_name, '\\')+1);
            if (file_exists($lib_path.$class.'.php'))
                require $lib_path.$class.'.php';
        }
    }

    private function  __construct($bootstrap) {
        require_once($bootstrap);
        if(!isset($system))
        {
            throw new Exception('The system configuration could not be load');
        }
        define('MODULE_PATH', $system['module_path']);
        if(isset($system['model_path'])) {
            define('MODEL_PATH', $system['model_path']);
        }
        define('TEMPLATE_PATH', $system['template_path']);
        define('CORE_PATH', $system['core_path'] . 
            DIRECTORY_SEPARATOR . 'core');
        define('CORE_LIB_PATH', $system['core_path'] . 
            DIRECTORY_SEPARATOR . 'lib');
        define('LIB_PATH', $system['lib_path']);
        define('HELPERS_PATH', $system['helpers']);
        if(isset($system['custom_paths'])) {
            define('CUSTOM_PATHS', serialize($system['custom_paths']));
        }
        self::$system = $system;
        require_once CORE_LIB_PATH . DIRECTORY_SEPARATOR . 'AtomicAutoload.php';
        $this->autoloadManager = new AtomicAutoload();
        if (isset(self::$system['autoload_file'])) {
            $this->autoloadManager->setScanOptions(autoloadManager::SCAN_CACHE);
        }
        if (array_key_exists('autoload_file', $system)) {
            $this->autoloadManager->setSaveFile($system['autoload_file']);
        }
        if (array_key_exists('excludeNameSpaces', $system) && 
            !empty($system['excludeNameSpaces'])) {
            foreach ($system['excludeNameSpaces'] as $namespace) {
                $this->autoloadManager->excludeNamspace($namespace);
            }
        }
        if (array_key_exists('excludeClasses', $system) && 
            !empty($system['excludeClasses'])) {
            foreach ($system['excludeClasses'] as $classNameExclude) {
                $this->autoloadManager->excludeClass($classNameExclude);
            }
        }
        if (array_key_exists('autoloadExcludeFolder', $system)) {
            foreach ($system['autoloadExcludeFolder'] as $excludeFolder) {
                $this->autoloadManager->excludeFolder($excludeFolder);
            }
        }
        $this->autoloadManager->addFolder(CORE_PATH);
        $this->autoloadManager->addFolder(CORE_LIB_PATH);
        $this->autoloadManager->addFolder(LIB_PATH);
        $this->autoloadManager->addFolder(MODULE_PATH);
        $this->autoloadManager->addFolder(HELPERS_PATH);
        if (defined('MODEL_PATH')) {
            $this->autoloadManager->addFolder(MODEL_PATH);
        }
        if(defined('CUSTOM_PATHS')) {
            $customPaths = unserialize(CUSTOM_PATHS);
            foreach ($customPaths as $key => $path) {
                $this->autoloadManager->addFolder($path);
            }
        }
        $this->autoloadManager->excludeFolder(CORE_LIB_PATH . DIRECTORY_SEPARATOR . 
            'activerecord');
        $this->autoloadManager->excludeFolder(CORE_LIB_PATH . DIRECTORY_SEPARATOR .
            'smarty');
        $this->autoloadManager->excludeNamspace('activerecord');
        $this->autoloadManager->register();
        if(isset($db) && defined('MODEL_PATH')) {
            spl_autoload_register("Atomic::activerecord_lib_autoload");
            $lib_path = CORE_LIB_PATH . DIRECTORY_SEPARATOR . 'activerecord/';
            require_once $lib_path . 'Utils.php';
            require_once $lib_path . 'Exceptions.php';
            $this->cfgAR = ActiveRecord\Config::instance();
            if (array_key_exists('ar_cache', $system)) {
                $this->cfgAr->set_cache($system['ar_cache']);
            }
            try {
                $this->cfgAR->set_connections($db);
            } catch(ActiveRecord\DatabaseException $e) {
                if(array_key_exists('DatabaseErrorHandler', self::$system)) {
                    $class = self::$system['DatabaseErrorHandler'];
                    $handler = new $class();
                    $handler->exceptionTrigger($this->cfgAR, $e);
                } else {
                    echo "Database Error";
                }
            }
            $this->cfgAR->set_model_directory(MODEL_PATH);
        }
        $this->config = Core::getInstance(self::$system);
    }

    private function loadRoutes() {
        if(array_key_exists('router', self::$system)) {
            require_once self::$system['router'];
            if(isset($system) && array_key_exists('routes', $system) 
                && !empty($system['routes']))
                self::$system['routes'] = $system['routes'];
        }
        $this->router = RouterManager::GetInstance($this->config)
            ->GetPhpRouterInstance();
        if(array_key_exists('routes', self::$system)) {
            $count = 0;
            foreach(self::$system['routes'] as $route => $info) {
                $customRoute = new PhpRoute($route);
                if(array_key_exists('static', $info)) {
                        foreach($info['static'] as $key => $value) {
                            $customRoute->addStaticElement($key, $value);
                        }
                    }
                if(array_key_exists('content', $info)) {
                    $customRoute->setMapClass($info['content']);
                    if(array_key_exists('action', $info)) {
                        $customRoute->setMapMethod($info['action']);
                    }
                }
                if(array_key_exists('dynamic', $info)) {
                    foreach($info['dynamic'] as $key => $value) {
                        $customRoute->addDynamicElement($key, $value);
                    }
                }
                $this->router->addRoute('cstRt' . $count, $customRoute);
                $count++;
            }
        }
        $standardRoute = new Route('/:class');
        $standardRoute->addDynamicElement(':class', ':class');
        $this->router->addRoute('standard_class', $standardRoute);

        $standardRoute = new Route('/:class/:method');
        $standardRoute->addDynamicElement(':class', ':class')
            ->addDynamicElement(':method', ':method');
        $this->router->addRoute('standard_class_method', $standardRoute);

        $route = new Route( '/:class/:method/:id' );
        $route->addDynamicElement( ':class', ':class' )->addDynamicElement( ':method', ':method' )
              ->addDynamicElement( ':id', ':id' );
        $this->router->addRoute('standard_class_method_id', $route);
    }

    public function run()
    {
        //Loading routes
        $this->loadRoutes();
        if(array_key_exists('InitializerHandler', self::$system)) {
            if (!empty(self::$system['InitializerHandler']) &&
                class_exists(self::$system['InitializerHandler'])) {
                $initalizr = new self::$system['InitializerHandler'];
                if ($initalizr instanceof InitializerHandlerAbstract) {
                    $initalizr->setConfig($this->config);
                    $initalizr->execute($this);
                }
            }
        }
        try{
            //Get the route destination
            if (isset($_SERVER['REQUEST_URI'])) {
                $url = urldecode($_SERVER['REQUEST_URI']);
            } else {
                throw new AtPageNotFoundException("Route Not Found", 0);
            }
            try {
                $foundRoute = $this->router->findRoute($url);
            }catch (RouteNotFoundException $e) {
                throw new AtPageNotFoundException("Route Not Found", 0);
            }
            $class = $foundRoute->getMapClass();
            if(strpos($class, '_') !== FALSE) {
                $elements = explode('_', $class);
                array_walk($elements, function($value, $key) use (&$elements){
                    $elements[$key] = ucfirst($elements[$key]);
                });
                $class = join('', $elements);
            } else {
                $class = ucfirst($class);
            }
            $class .= (!isset(self::$system['controller_suffix']) ? '' :
                self::$system['controller_suffix']);
            $method = $foundRoute->getMapMethod();
            $arguments = $foundRoute->getMapArguments();
            if (isset(self::$system['autoload_file'])) {
                $classNameCache = strtolower($class);
                if ($this->autoloadManager->classExists(
                        $classNameCache, True) == 1) {
                    throw new AtPageNotFoundException("Class Not Found", 1);
                }
            } else {
                if(!class_exists($class)) {
                    throw new AtPageNotFoundException("Class Not Found", 1);
                }
            }
            $this->controller = $class;
            $content = new $class;
            if(!$content instanceof AtController) {
                  throw new AtPageNotFoundException('The request is no a valid Content. Nothing to do.', 2);
            }
            else
            {
                $content->setConfigInstance($this->config);
            }
            if(strpos($method, '_') !== FALSE) {
                $elements = explode('_', $method);
                array_walk($elements, function($key, $value) use (&$elements){
                    $elements[$key] = ucfirst($elements[$key]);
                });
                $method = join('', $elements);
            } else {
                $method = ucfirst($method);
            }
            $this->action = $method;
            $action = $method;
            if(!empty($action))
            {
                $action = (!isset(self::$system['action_prefix']) ? '' : 
                    self::$system['action_prefix']) . $action;
                if(method_exists($content, $action))
                {
                    if(empty($arguments)) {
                        $content->$action();
                    } else {
                        call_user_func(array($content, $action), $arguments);
                    }
                }
                else {
                    throw new AtPageNotFoundException('Action Not Found', 3);
                }
            } else {
                $action = (!isset(self::$system['action_prefix']) ? '' : 
                self::$system['action_prefix']) . 'Index';
                $content->$action();
            }
        } catch(ActiveRecord\DatabaseException $e) {
            if(array_key_exists('DatabaseErrorHandler', self::$system)) {
                $class = self::$system['DatabaseErrorHandler'];
                $handler = new $class();
                $handler->exceptionTrigger($this->cfgAR, $e);
            } else {
                echo "Database Error";
            }
        } catch(AtPageNotFoundException $e) {
            if(array_key_exists('PageNotFoundHandler', self::$system)) {
                $class = self::$system['PageNotFoundHandler'];
                $handler = new $class();
                $handler->exception($e, $this);
            } else {
                throw new AtRouteNotFound($e->getMessage(), $e->getCode());
            }
        } catch(AtRedirectRequestException $e) {
            $type = $e->getType();
            if (!empty($type)) {
                header($type);
            }
            header('Location: ' . $e->getLocation());
        }
    }

    public function GetConfig() {
        return $this->config;
    }

    public static function getInstance($bootstrap)
    {
        if(self::$PhpPhantInstance == null)
        {
           self::$PhpPhantInstance = new self($bootstrap);
        }
        return self::$PhpPhantInstance;
    }

    public function getRouterManager() {
        return $this->router;
    }

    public function getController() {
        return $this->controller;
    }

    public function getAction() {
        return $this->action;
    }

    public function getAutoloadManager() {
        return $this->autoloadManager;
    }
}
