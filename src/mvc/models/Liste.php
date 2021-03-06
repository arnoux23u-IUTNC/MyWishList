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
 * @property mixed $is_public
 * @property mixed $items Goes to items(), eloquent relation
 * @method static where(string $string, string $string1, string $string2) Eloquent method
 * @method static whereUserId(int $user_id) Eloquent method
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\models
 */
class Liste extends Model
{
    protected $table = 'liste';
    protected $primaryKey = 'no';
    public $timestamps = false;
    protected $hidden = ['user_id', 'private_key'];
    protected $appends = ['user_name'];
    protected $fillable = ['titre', 'user_id', 'description', 'expiration', 'public_key', 'published', 'is_public'];

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
     * @return bool true if the list is expired, false otherwise
     */
    public function isExpired(): bool
    {
        return !empty($this->expiration) && $this->expiration <= date('Y-m-d');
    }

    /**
     * Check if a list is published
     * @return bool true if published, false otherwise
     */
    public function isPublished(): bool
    {
        return $this->published == 1;
    }

    /**
     * Check if a list is public
     * @return bool true if public, false otherwise
     */
    public function isPublic(): bool
    {
        return $this->is_public == 1;
    }

    /**
     * Internal method for toJSON() method of Slim
     * @return string|null user's name if exists, null otherwise
     * @noinspection PhpUnused
     */
    public function getUserNameAttribute(): ?string
    {
        $user = User::find($this->user_id);
        return !empty($user) ? $user->name() : null;
    }

    /**
     * Check if a list is claimed
     * @return bool at true if claimed, false otherwise
     */
    public function isClaimed(): bool
    {
        return $this->user_id != NULL;
    }

}