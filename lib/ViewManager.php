<?php
require_once 'smarty/Smarty.class.php';
final class ViewManager {
    private static $instance;
    private $smartyInstance;

    private function __construct(Core $configSystem) {
        $this->smartyInstance = new Smarty();
        $templatePath = $configSystem->Value('template_path');
        if(empty($templatePath)) {
            throw new Exception('You must specified a template path');
        }
        $this->smartyInstance->setTemplateDir($templatePath);
        $templateC = $configSystem->Value('template_c');
        if(!empty($templateC)) {
            $this->smartyInstance->setCompileDir($templateC);
        }
        $templateConfigs = $configSystem->Value('template_configs_dir');
        if(!empty($templateConfigs)) {
            $this->smartyInstance->setConfigDir($templateConfigs);
        }
        $templateCache = $configSystem->Value('template_cache_dir');
        if(!empty($templateCache)) {
            $this->smartyInstance->setCacheDir($templateCache);
        }
        $customPlugins = $configSystem->Value('smarty_plugins');
        if (!empty($customPlugins)) {
            $this->smartyInstance->setPluginsDir(array_merge(
                $this->smartyInstance->getPluginsDir(),
                array($customPlugins)
            ));
        }

        $delimiters = $configSystem->Value('smarty_delimiters');
        if (is_array($delimiters) && !empty($delimiters)) {
            $this->smartyInstance->left_delimiter = $delimiters['left'];
            $this->smartyInstance->right_delimiter = $delimiters['right'];
        }
    }

    public static function GetInstance(Core $configSystem) {
        if(self::$instance == null) {
            self::$instance = new ViewManager($configSystem);
        }
        return self::$instance;
    }
    public function GetSmartyInstance() {
        return $this->smartyInstance;
    }
}
?>
