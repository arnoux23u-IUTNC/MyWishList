<?php

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;
use \mywishlist\bd\HasCompositePrimaryKey;

class RescueCode extends Model
{
    use HasCompositePrimaryKey;
    protected $table = 'totp_rescue_codes';
    protected $primaryKey = ['user','code'];
    public $timestamps = false;
    protected $guarded = ['created_at'];

}