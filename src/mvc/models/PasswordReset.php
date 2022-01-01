<?php

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;

/**
 * Password Reset Model
 * Inherits from the Model class of Laravel
 * @property string $token
 * @property int $user_id
 * @property mixed $expiration
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\models
 * @method static where(string $string, string $string1, string $string2) Eloquent method
 * @method static whereToken(string $token) Eloquent method
 */
class PasswordReset extends Model
{
    protected $table = 'passwords_reset';
    protected $primaryKey = 'token';
    public $timestamps = false;
    protected $fillable = ['token', 'user_id', 'expiration'];

    /**
     * Check if a list is expired
     * @return bool
     */
    public function isExpired(): bool
    {
        return !empty($this->expiration) && $this->expiration <= date('Y-m-d H:i:s');
    }

}