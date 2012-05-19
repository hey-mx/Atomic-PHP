<?php
require_once 'Net/GeoIP.php';

//Get the data file from  http://www.maxmind.com/app/geoip_country
//Checkout http://pear.php.net/package/Net_GeoIP/docs

class GeoIp extends Helper {
    protected $netGeoIpInstance;

    public function SetupGeoIp($filePath) {
       $this->netGeoIpInstance = Net_GeoIP::getInstance($filePath);
    }

    public function GetGeoIpInstance() {
        return $this->netGeoIpInstance;
    }
}
?>
