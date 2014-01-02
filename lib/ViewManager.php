<?php
require_once 'smarty/Smarty.class.php';
final class ViewManager {
    private static $instance;
    private $smartyInstance = array();
    private $configSystem;

    private function __construct(Core $configSystem) {
        $this->configSystem = $configSystem;
    }

    private function CreateSmartyInstance($usingCache, $templateCache='') {
        if ($usingCache && isset(self::$smartyInstance['cache'])) {
            $smartyInstance = self::$smartyInstance['cache'];
        } elseif (!$usingCache && isset(self::$smartyInstance['default'])) {
            $smartyInstance = self::$smartyInstance['default'];
        } else {
            $isUsingCache = !empty($templateCache) && $usingCache;
            $smartyInstance = new Smarty();
            $templatePath = $this->configSystem->Value('template_path');
            if(empty($templatePath)) {
                throw new Exception('You must specified a template path');
            }
            $smartyInstance->setTemplateDir($templatePath);
            $templateC = $this->configSystem->Value('template_c');
            if(!empty($templateC)) {
                $smartyInstance->setCompileDir($templateC);
            }
            $templateConfigs = $this->configSystem->Value('template_configs_dir');
            if(!empty($templateConfigs)) {
                $smartyInstance->setConfigDir($templateConfigs);
            }
            if($isUsingCache) {
                $smartyInstance->setCacheDir($templateCache);
                $smartyInstance->setCaching(Smarty::CACHING_LIFETIME_SAVED);
            }
            $customPlugins = $this->configSystem->Value('smarty_plugins');
            if (!empty($customPlugins)) {
                $smartyInstance->setPluginsDir(array_merge(
                    $smartyInstance->getPluginsDir(),
                    array($customPlugins)
                ));
            }
            $delimiters = $this->configSystem->Value('smarty_delimiters');
            if (is_array($delimiters) && !empty($delimiters)) {
                $smartyInstance->left_delimiter = $delimiters['left'];
                $smartyInstance->right_delimiter = $delimiters['right'];
            }
            if ($isUsingCache) {
                self::$smartyInstance['cache'] = $smartyInstance;
            } else {
                self::$smartyInstance['default'] = $smartyInstance;
            }
        }
        return $smartyInstance;
    }

    public static function GetInstance(Core $configSystem) {
        if(self::$instance == null) {
            self::$instance = new ViewManager($configSystem);
        }
        return self::$instance;
    }

    public function GetSmartyInstance($usingCache=false) {
        $templateCache = $this->configSystem->Value('template_cache_dir');
        $smarty = null;
        if ($usingCache && !empty($templateCache)) {
            $smarty = $this->CreateSmartyInstance($usingCache, $templateCache);
        } else {
            $smarty = $this->CreateSmartyInstance(false);
        }
        return $smarty;
    }
}
?>
