<?php

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Liste Model
 * Inherits from the Model class of Laravel
 * @property int $no
 * @property int $user_id
 * @property string $titre
 * @property string $description
 * @property mixed $expiration
 * @property string $public_key
 * @property string $private_key
 * @property mixed $published
 * @property mixed $items Goes to items(), eloquent relation
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\models
 * @method static where(string $string, string $string1, string $string2) Eloquent method
 * @method static whereUserId(int $user_id) Eloquent method
 */
class Liste extends Model
{
    protected $table = 'liste';
    protected $primaryKey = 'no';
    public $timestamps = false;
    protected $hidden = ['user_id', 'private_key'];
    protected $appends = ['user_name'];
    protected $fillable = ['titre', 'user_id', 'description', 'expiration', 'public_key', 'published'];

    /**
     * Get items associated to the list
     * @return HasMany items has many relation
     */
    public function items(): HasMany
    {
        return $this->hasMany('\mywishlist\mvc\models\Item', 'liste_id');
    }

    /**
     * Check if a list is expired
     * @return bool
     */
    public function isExpired(): bool
    {
        return !empty($this->expiration) && $this->expiration <= date('Y-m-d');
    }

    /**
     * Check if a list is published
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->published == 1;
    }

    /**
     * Internal method for toJSON() method of Slim
     * @return string|null user's name if exists, null otherwise
     * @noinspection PhpUnused
     */
    protected function getUserNameAttribute(): ?string
    {
        $user = User::find($this->user_id);
        return !empty($user) ? $user->name() : null;
    }

}