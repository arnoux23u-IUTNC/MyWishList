<?php

namespace mywishlist\mvc\models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use mywishlist\bd\HasCompositePrimaryKey;

/**
 * Participation Model
 * Inherits from the Model class of Laravel
 * @property int $cagnotte_itemid
 * @property string $user_email
 * @property float $montant
 * @method static whereCagnotteItemidAndUserEmail($item_id, string $user_email) Eloquent method
 * @method static whereCagnotteItemid(int $cagnotte_itemid) Eloquent method
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\models
 */
class Participation extends Model
{
    use HasCompositePrimaryKey;

    protected $table = 'participe';
    protected $primaryKey = ['cagnotte_itemid', 'user_email'];
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $guarded = [];

}