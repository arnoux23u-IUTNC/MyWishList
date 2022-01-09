<?php

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item Model
 * Inherits from the Model class of Laravel
 * @property int $id
 * @property int $liste_id
 * @property string $nom
 * @property string $descr
 * @property string $img
 * @property string $url
 * @property float $tarif
 * @property mixed $liste Goes to liste() method, eloquent relation
 * @method static where(string $string, string $string1, string $string2) Eloquent method
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\models
 */
class Item extends Model
{
    protected $table = 'item';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['liste_id', 'nom', 'descr', 'img', 'url', 'tarif'];

    /**
     * Get the associated list of an item
     * @return BelongsTo liste belongsTo relation
     */
    public function liste(): BelongsTo
    {
        return $this->belongsTo('\mywishlist\mvc\models\Liste', 'liste_id');
    }

}