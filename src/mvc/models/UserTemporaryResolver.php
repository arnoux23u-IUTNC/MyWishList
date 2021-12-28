<?php

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;
use \mywishlist\mvc\models\Liste;

class UserTemporaryResolver extends Model
{
    protected $table = 'temporary_waiting_users';
    protected $primaryKey = 'data_id';
    public $timestamps = false;
    protected $guarded = [];

    public function __construct(Model $object, string $email){
        switch (get_class($object)){
            case Liste::class:
                $this->data_id = $object->no;
                $this->type = 0;
                break;
            case Item::class:
                $this->data_id = $object->id;
                $this->type = 1;
                break;
            default:
                throw new \Exception("Unknown class");
        }
        $this->email = $email;
    }

}