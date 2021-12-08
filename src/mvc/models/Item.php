<?php

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'item';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function liste()
    {
        //belongsTo(quoi, cle etrangere)
        //retourne la liste ou foreign key de list = param 2
        return $this->belongsTo('\mywishlist\mvc\models\List', 'liste_id');
    }

}