<?php
require_once 'Zend/Translate.php';

class Translate extends Helper {
    protected $translateInstance;

    public function SetupLang($lang, $domain) {
        $path = $this->config->Value('lang_path');
        if(empty($path)) {
            throw new Exception('You must define the lang path');
        }
        $this->translateInstance = new Zend_Translate(
            array(
                'adapter' => 'gettext',
                'content' => $path . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . 'LC_MESSAGES' . 
                    DIRECTORY_SEPARATOR . $domain . '.mo',
                'locale'  => $lang,
                'disableNotices'=>true
            )
        );
    }

    public function GetTranslateInstance() {
        return $this->translateInstance;
    }

    public function _($string) {
        return $this->translateInstance->_($string);;
    }
}
?>
