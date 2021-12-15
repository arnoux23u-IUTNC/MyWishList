<?php
namespace mywishlist\bd;

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;

class Eloquent
{

    public static function start($file)
    {
        $capsule = new Capsule;
        $capsule->addConnection(parse_ini_file($file));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        try {
            $capsule->getConnection()->getPdo();
        } catch (Exception) {
            header('HTTP/1.1 500 Internal Server Error');
            require_once __DIR__.'\..\..\errors\500.html';
            exit();
        }
    }
}