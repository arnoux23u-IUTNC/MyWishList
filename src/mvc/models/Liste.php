<?php

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;

class Liste extends Model
{
    protected $table = 'liste';
    protected $primaryKey = 'no';
    public $timestamps = false;


    public function items()
    {
        //hasMany(quoi, qui a quelle cle)
        //verifie que primaryKey = liste_id
        //cle etrangere de first param
        return $this->hasMany('\mywishlist\mvc\models\Item', 'liste_id');
    }

    public function isExpired()
    {
        return $this->expiration <= date('Y-m-d');
    }

}