<?php
final class RouterManager {
    private static $instance;
    private $phpRouterInstance;
    private $configSystem;

    private function __construct(Core $configSystem) {
        $this->configSystem = $configSystem;
        $this->phpRouterInstance = new Router();
        $defaultModule = $configSystem->Value('default_module');
        if (!empty($defaultModule)) {
            $rootRoute = new Route('/');
            $rootRoute->setMapClass($defaultModule)
                ->setMapMethod('index');
            $this->phpRouterInstance->addRoute('default', $rootRoute);
        }
    }

    public static function GetInstance(Core $configSystem) {
        if(self::$instance == null) {
            self::$instance = new RouterManager($configSystem);
        }
        return self::$instance;
    }

    public function GetPhpRouterInstance() {
        return $this->phpRouterInstance;
    }
}