<?php

namespace mywishlist\exceptions;

use Exception;

/**
 * Class ForbiddenException
 * Inherits from Exception
 * Generated when a forbidden action is attempted
 * @author Guillaume ARNOUX
 * @package mywishlist\exceptions
 */
class ForbiddenException extends Exception
{

    /**
     * @var string Title of the exception
     */
    private static string $title;

    /**
     * ForbiddenException constructor
     * @param string $title Title of the exception
     * @param string $message Message of the exception
     */
    public function __construct(string $title = "Forbidden", string $message = "Forbidden")
    {
        self::$title = $title;
        parent::__construct($message);
    }

    /**
     * Get the title of the exception
     * @return string Title of the exception
     */
    public function getTitle(): string
    {
        return self::$title;
    }
}