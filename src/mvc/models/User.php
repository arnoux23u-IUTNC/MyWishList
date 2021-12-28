<?php

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;
use \mywishlist\mvc\models\Liste;

class User extends Model
{
    protected $table = 'accounts';
    protected $primaryKey = 'user_id';
    public $incrementing = true;
    public $timestamps = false;
    protected $guarded = ['user_id', 'created_at'];

    public function __construct(){
        $this->user_id = -1;
    }

    public static function logout(){
        session_destroy();
        //Double vérification pour éviter les problèmes de session
        unset($_SESSION);
        session_start();
    }

    public function create2FA($secret){
        $this->update(["totp_key" => $secret]);
        for ($i = 1; $i <= 6; $i++)
        RescueCode::create(["user" => $this->user_id, "code" => rand(10000000, 99999999)]);
    }

    public function remove2FA(){
        $this->update(["totp_key" => NULL]);
        RescueCode::whereUser($this->user_id)->delete();
    }

    public function isAdmin() : bool {
        return $this->is_admin == "1";
    }

    public function canInteractWithList(Liste $list) : bool{
        return $this->user_id != "-1" && $this->user_id === $list->user_id;
    }

}