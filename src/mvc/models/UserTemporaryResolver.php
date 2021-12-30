<?php

namespace mywishlist\mvc\models;

use Exception;
use Illuminate\Database\Eloquent\Model;

/**
 * UserTemp Model
 * Inherits from the Model class of Laravel
 * @property int $data_id
 * @property int $type
 * @property string $email
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\models
 */
class UserTemporaryResolver extends Model
{
    protected $table = 'temporary_waiting_users';
    protected $primaryKey = 'data_id';
    public $timestamps = false;
    protected $guarded = [];

    /**
     * Constructor
     * @param Model $object Liste or Item
     * @param string $email Email of the user
     * @throws Exception
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    public function __construct(Model $object, string $email)
    {
        switch (get_class($object)) {
            case Liste::class:
                $this->data_id = $object->no;
                $this->type = 0;
                parent::__construct();
                break;
            case Item::class:
                $this->data_id = $object->id;
                $this->type = 1;
                parent::__construct();
                break;
            default:
                throw new Exception("Unknown class");
        }
        $this->email = $email;
    }

}