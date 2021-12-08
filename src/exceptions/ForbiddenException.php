<?php

namespace mywishlist\exceptions;

class ForbiddenException extends \Exception
{

    private static string $title;

    public function __construct($title = "Forbidden", $message = "Forbidden")
    {
        self::$title = $title;
        parent::__construct($message);
    }

    public function getTitle()
    {
        return self::$title;
    }
}