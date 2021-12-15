<?php

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'item';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['liste_id', 'nom', 'descr', 'img', 'url', 'tarif'];

    public function liste()
    {
        return $this->belongsTo('\mywishlist\mvc\models\Liste', 'liste_id');
    }

}