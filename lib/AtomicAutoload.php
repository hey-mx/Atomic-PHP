<?php
require_once 'PHP-Autoload-Manager/autoloadManager.php';

class AtomicAutoload extends autoloadManager {
	/**
     * Namespaces that should be exclude
     * @var array
     */
    protected $_excludedNamespaces = array();

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
     * Overriding Method used by the spl_autoload_register
     *
     * @param string $className Name of the class
     * @return void
     */
    public function loadClass($className)
    {
        $className = strtolower($className);
        if (strpos($classname,'\\') === true) {
            $namespace = explode('\\', $classname);
            if (in_array($namespace, $this->_excludedNamespaces)) {
                return;
            }
        }
        parent::loadClass($className);
    }
}