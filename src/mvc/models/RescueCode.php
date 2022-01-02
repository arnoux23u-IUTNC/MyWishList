<?php

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;
use mywishlist\bd\HasCompositePrimaryKey;

/**
 * RescueCode Model
 * Inherits from the Model class of Laravel
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\models
 * @property int $user
 * @property int $code
 * @method static whereUserAndCode($user_id, int $rescue) Eloquent method
 * @method static whereUser(int $user_id) Eloquent method
 * @method static create(array $array) Eloquent method
 */
class RescueCode extends Model
{
    use HasCompositePrimaryKey;

    protected $table = 'totp_rescue_codes';
    protected $primaryKey = ['user', 'code'];
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = ['created_at'];
}