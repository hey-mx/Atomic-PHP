<?php
require_once 'PHP-Autoload-Manager/autoloadManager.php';

class AtomicAutoload extends autoloadManager {
	/**
     * Namespaces that should be exclude
     * @var array
     */
    protected $_excludedNamespaces = array();
    /**
     * Class Names that should be exclude
     * @var array
     */
    protected $_excludedClasses =  array();
    /**
     * Exclude a namespace from the parsing
     *
     * @param string $namespace Namespace to exclude
     */
    public function excludeNamspace($namespace)
    {
        if (!in_array($namespace, $this->_excludedNamespaces) &&
                !empty($namespace)) {
            $this->_excludedNamespaces[] = strtolower($namespace);
        }
    }

    /**
     * 
     */
    public function excludeClass($className) {
        if (!empty($className) && !in_array($className, $this->_excludedClasses)) {
            $this->_excludedClasses[] = strtolower($className);
        }
    }
    /**
     * Overriding Method used by the spl_autoload_register
     *
     * @param string $className Name of the class
     * @return void
     */
    public function loadClass($className)
    {
        $className = strtolower($className);
        if (in_array($className, $this->_excludedClasses)) {
            return;
        } else if (strpos($className,'\\') !== false) {
            $namespace = explode('\\', $className);
            if (in_array($namespace[0], $this->_excludedNamespaces)) {
                return;
            }
        }
        parent::loadClass($className);
    }
}