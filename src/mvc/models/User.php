<?php

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'accounts';
    protected $primaryKey = 'user_id';
    public $incrementing = true;
    public $timestamps = false;
    protected $guarded = ['user_id', 'created_at'];

}