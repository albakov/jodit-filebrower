<?php

namespace Albakov\JoditFilebrowser\Controllers;

class Request
{
    /**
     * @var array
     */
    private $request;

    /**
     * Request constructor.
     */
    public function __construct()
    {
        $this->request = $_REQUEST;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        return isset($this->request[$name]) ? $this->request[$name] : null;
    }
}
