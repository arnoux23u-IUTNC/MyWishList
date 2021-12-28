<?php

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;

class Liste extends Model
{
    protected $table = 'liste';
    protected $primaryKey = 'no';
    public $timestamps = false;
    protected $fillable = ['titre', 'user_id', 'description', 'expiration', 'public_key', 'published'];


    public function items()
    {
        //hasMany(quoi, qui a quelle cle)
        //verifie que primaryKey = liste_id
        //cle etrangere de first param
        return $this->hasMany('\mywishlist\mvc\models\Item', 'liste_id');
    }

    public function isExpired()
    {
        return !empty($this->expiration) && $this->expiration <= date('Y-m-d');
    }

    public function isPublished() : bool{
        return $this->published == 1;
    }

}