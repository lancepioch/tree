<?php

namespace App;

use GuzzleHttp\Client as HttpClient;

class Forge extends \Themsaid\Forge\Forge {

    /**
     * Forge constructor.
     * @param string $apiKey
     * @param HttpClient $guzzle
     */
    public function __construct($apiKey = null, HttpClient $guzzle = null)
    {
        if (!is_null($apiKey)) {
            parent::__construct($apiKey, $guzzle);
        }
    }

    /**
     * @param string $apiKey
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        parent::__construct($apiKey);

        return $this;
    }

}
