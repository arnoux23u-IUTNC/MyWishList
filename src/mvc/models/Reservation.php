<?php /** @noinspection PhpPossiblePolymorphicInvocationInspection */

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;

/**
 * Reservation Model
 * Inherits from the Model class of Laravel
 * @property int $item_id
 * @property string $user_email
 * @property string $message
 * @method static where(string $string, string $string1, string $string2) Eloquent method
 * @method static find(int $item_id) Eloquent method
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\models
 */
class Reservation extends Model
{
    protected $table = 'reserve';
    protected $primaryKey = 'item_id';
    public $timestamps = false;
    public $incrementing = false;
    protected $guarded = [];

    /**
     * Get the user that reserved the item
     * @return string user lastname and firstname
     */
    public function getUser(): string
    {
        $user = User::whereMail($this->user_email)->first();
        return empty($user) ? " $this->user_email" : " $user->lastname $user->firstname";
    }
}