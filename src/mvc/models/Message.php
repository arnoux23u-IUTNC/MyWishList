<?php

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;
use mywishlist\bd\HasCompositePrimaryKey;

/**
 * Message Model
 * Inherits from the Model class of Laravel
 * @property int $list_id
 * @property string $user_email
 * @property string $message
 * @property mixed $date
 * @method static where(string $string, string $string1, string $string2) Eloquent method
 * @method static find(int $list_id) Eloquent method
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\models
 */
class Message extends Model
{
    use HasCompositePrimaryKey; 

    protected $table = 'messages';
    protected $primaryKey = ['list_id', 'user_email', 'message', 'date'];
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