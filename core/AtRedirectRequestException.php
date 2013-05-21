<?php
class AtRedirectRequestException extends Exception {
    
    protected $location;
    protected $type;
    const PERMANENTLY = 'HTTP/1.1 301 Moved Permanently';
    const TEMPORALLY = 'HTTP/1.1 302 Moved Temporally';
    

    public function __construct($location, $type='') {
        parent::__construct('', 0, null);
        $this->location = $location;
        $this->type = $type;
    }

    public function getLocation() {
        return $this->location;
    }

    public function getType() {
        return $this->type;
    }
}
