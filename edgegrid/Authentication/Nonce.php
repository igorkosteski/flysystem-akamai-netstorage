<?php

/**
 * Akamai {OPEN} EdgeGrid Auth for PHP
 *
 * @author Davey Shafik <dshafik@akamai.com>
 * @copyright Copyright 2016 Akamai Technologies, Inc. All rights reserved.
 * @license Apache 2.0
 * @link https://github.com/akamai-open/AkamaiOPEN-edgegrid-php
 * @link https://developer.akamai.com
 * @link https://developer.akamai.com/introduction/Client_Auth.html
 */

namespace Akamai\Open\EdgeGrid\Authentication;

/**
 * Generates a random nonce for each request
 *
 * @package Akamai\Open\EdgeGrid\Authentication
 */
class Nonce
{
    public const FORMAT = 'Ymd\TH:i:sO';

    /**
     * @var string The current random function to use
     */
    protected $function;

    /**
     * Constructor
     *
     * Determines if PHP 7's random_bytes() can be used
     */
    public function __construct()
    {
        $this->function = 'random_bytes';
    }

    /**
     * Return the nonce when cast to string
     *
     * @return string Returns the nonce
     */
    public function __toString()
    {
        return bin2hex(($this->function)(16));
    }
}
