<?php

namespace mywishlist\exceptions;

class CookieNotSetException extends \Exception
{

    private static string $title;

    public function __construct($title = "Cookie not set", $message = "Cookie not set")
    {
        self::$title = $title;
        parent::__construct($message);
    }

    public function getTitle()
    {
        return self::$title;
    }
}