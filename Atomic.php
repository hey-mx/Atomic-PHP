<?php
class Atomic {
    private static $PhpPhantInstance;
    private $router;
    private static $system;

    public static function autoLoad($className)
    {
        $paths = array(CORE_PATH, LIB_PATH, MODULE_PATH, MODEL_PATH, HELPERS_PATH, HELPER_CORE_PATH);
        foreach($paths as $path)
        {
            if( is_dir( $path ) ) {
                $dir = opendir( $path );
                while( false !== $item = readdir( $dir ) ) {
                    $current = $path . "/$item";
                    if( is_dir( $current ) ) {
                        $file = self::checkFileExists( $current, $className );
                        if( $file ) {
                            include_once $file;
                            return;
                        }
                    }
                    else
                    {
                        if($item == $className)
                        {
                            include_once $current;
                            return;
                        }
                    }
                }
            }
        }
    }

    private static function checkFileExists( $dir_name, $filename )
    {
        $file = $dir_name . "/$filename.php";
        return file_exists( $file ) ? $file : false;
    }

    public static function activerecord_lib_autoload($class_name)
    {
        $lib_path = LIB_PATH . DIRECTORY_SEPARATOR . 'activerecord/';
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
        define('CORE_PATH', $system['core_path']);
        define('LIB_PATH', $system['lib_path']);
        define('HELPERS_PATH', $system['helpers']);
        define('HELPER_CORE_PATH', CORE_PATH . DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . 'helpers');
        self::$system = $system;
        spl_autoload_register("Atomic::autoLoad");
        spl_autoload_register("Atomic::activerecord_lib_autoload");
        if(isset($db) && defined('MODEL_PATH')) {
            $lib_path = LIB_PATH . DIRECTORY_SEPARATOR . 'activerecord/';
            require_once $lib_path . 'Utils.php';
            require_once $lib_path . 'Exceptions.php';
            $cfgAR = ActiveRecord\Config::instance();
            try {
            $cfgAR->set_connections($db);
            } catch(ActiveRecord\DatabaseException $e) {
                echo "Database Error: " . $e->getMessage();
            }
            $cfgAR->set_model_directory(MODEL_PATH);
        }
        set_include_path(get_include_path() . PATH_SEPARATOR . LIB_PATH);
    }

    private function loadRoutes() {
        $this->router = new Router;
        $rootRoute = new Route('/');
        $rootRoute->setMapClass(self::$system['default_module'])
            ->setMapMethod('index');
        $this->router->addRoute('default', $rootRoute);
        if(array_key_exists('routes', self::$system)) {
            $count = 0;
            foreach(self::$system['routes'] as $route => $info) {
                $customRoute = new Route($route);
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
    }

    public function run()
    {
        //Loading routes
        $this->loadRoutes();
        //Get the route destination
        $url = urldecode($_SERVER['REQUEST_URI']);
        try{
            try {
                $foundRoute = $this->router->findRoute($url);
            }catch (RouteNotFoundException $e) {
                throw new AtPageNotFoundException("Route Not Found", 0);
            }
            $class = $foundRoute->getMapClass();
            $method = $foundRoute->getMapMethod();
            $arguments = $foundRoute->getMapArguments();
            if(!class_exists($class)) {
                throw new AtPageNotFoundException("Class Not Found", 1);
            }
            $content = new $class;
            if(!$content instanceof AtController) {
                  throw new AtPageNotFoundException('The request is no a valid Content. Nothing to do.', 2);
            }
            else
            {
                $config = Core::getInstance(self::$system);
                $content->setConfigInstance($config);
            }
            $action = $method;
            if(!empty($action))
            {
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
                $content->index();
            }
        } catch(ActiveRecord\DatabaseException $e) {
            echo "Database Error: ";
        } catch(AtPageNotFoundException $e) {
            if(array_key_exists('PageNotFoundHandler', self::$system)) {
                $class = self::$system['PageNotFoundHandler'];
                $handler = new $class();
                $handler->exception($e);
            } else {
                throw new Exception($e->getMessage(), $e->getCode());
            }
        }
    }

    public static function getInstance($bootstrap)
    {
        if(self::$PhpPhantInstance == null)
        {
           self::$PhpPhantInstance = new self($bootstrap);
        }
        return self::$PhpPhantInstance;
    }
}
