<?php

namespace mywishlist\bd;

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Eloquent Database Manager Class
 * @author Guillaume ARNOUX
 * @package mywishlist\bd
 */
class Eloquent
{

    /**
     * Static method to connect to the database
     * @param $file string config file
     */
    public static function start(string $file)
    {
        $capsule = new Capsule;
        $capsule->addConnection(parse_ini_file($file));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        try {
            $capsule->getConnection()->getPdo();
        } catch (Exception) {
            header('HTTP/1.1 500 Internal Server Error');
            require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . '500.html';
            exit();
        }
    }
}