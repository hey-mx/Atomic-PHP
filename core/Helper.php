<?php
class Helper implements IConfigurable
{
	protected $config = null;
	public function setConfig(Core $configObject)
	{
		$this->config = $configObject;
	}
}