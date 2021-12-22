<?php

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;

class Reserved extends Model
{
    protected $table = 'reserve';
    protected $primaryKey = 'item_id';
    protected $guarded = [];

}