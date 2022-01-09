<?php

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;

/**
 * Password Reset Model
 * Inherits from the Model class of Laravel
 * @property string $token
 * @property int $user_id
 * @property mixed $expiration
 * @method static where(string $string, string $string1, string $string2) Eloquent method
 * @method static whereToken(string $token) Eloquent method
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\models
 */
class PasswordReset extends Model
{
    protected $table = 'passwords_reset';
    protected $primaryKey = 'token';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['token', 'user_id', 'expiration'];

    /**
     * Check if a list is expired
     * @return bool true if the token is expired, false otherwise
     */
    public function expired(): bool
    {
        return !empty($this->expiration) && $this->expiration <= date('Y-m-d H:i:s');
    }

}