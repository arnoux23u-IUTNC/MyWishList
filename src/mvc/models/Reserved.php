<?php /** @noinspection PhpPossiblePolymorphicInvocationInspection */

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;

/**
 * Reserved Model
 * Inherits from the Model class of Laravel
 * @property int $item_id
 * @property int $user_id
 * @property string $message
 * @method static where(string $string, string $string1, string $string2) Eloquent method
 * @method static find(int $user_id) Eloquent method
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\models
 */
class Reserved extends Model
{
    protected $table = 'reserve';
    protected $primaryKey = 'item_id';
    public $incrementing = false;
    protected $guarded = [];

    /**
     * Get the user that reserved the item
     * @return string user lastname and firstname
     */
    public function user(): string
    {
        $user = $this->belongsTo(User::class, 'user_id')->first();
        return " $user->lastname $user->firstname";
    }
}