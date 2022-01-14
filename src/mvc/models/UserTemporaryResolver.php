<?php

namespace mywishlist\mvc\models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use mywishlist\bd\HasCompositePrimaryKey;

/**
 * UserTemp Model
 * Inherits from the Model class of Laravel
 * @property int $list_id
 * @property string $email
 * @method static where(string $string, string $string1, string $string2) Eloquent method
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\models
 */
class UserTemporaryResolver extends Model
{

    protected $table = 'temporary_waiting_users';
    protected $primaryKey = 'list_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

}