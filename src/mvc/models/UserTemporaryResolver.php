<?php

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserTemp Model
 * Inherits from the Model class of Laravel
 * @property int $list_id
 * @property string $email
 * @method static whereEmail(string $email) Eloquent method
 * @method static where(string $string, string $string1, string $string2) Eloquent method
 * @method static find(int $list_id) Eloquent method
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

    /**
     * Get the associated list of a tmp resolver
     * @return BelongsTo liste belongsTo relation
     */
    public function liste(): BelongsTo
    {
        return $this->belongsTo('\mywishlist\mvc\models\Liste', 'list_id');
    }

}