<?php

namespace mywishlist\mvc\models;

use Illuminate\Database\Eloquent\Model;

/**
 * User Model
 * Inherits from the Model class of Laravel
 * @property int $user_id
 * @property string $username
 * @property string $lastname
 * @property string $firstname
 * @property string $password
 * @property string $mail
 * @property string $avatar
 * @property mixed $updated
 * @property mixed $last_login
 * @property int $last_ip
 * @property mixed $is_admin
 * @property string $totp_key
 * @method static find(int $USER_ID) Eloquent method
 * @method static where(string $string, string $string1, string $string2) Eloquent method
 * @method static whereMail(string $email) Eloquent method
 * @method static whereUsername(string $username) Eloquent method
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\models
 */
class User extends Model
{
    protected $table = 'accounts';
    protected $primaryKey = 'user_id';
    public $timestamps = false;
    protected $guarded = ['user_id', 'created_at'];

    /**
     * Constructor
     * Force the user_id to -1
     */
    public function __construct()
    {
        $this->user_id = -1;
        parent::__construct();
    }

    /**
     * Logout the user
     */
    public static function logout()
    {
        session_destroy();
        //Double vérification pour éviter les problèmes de session
        $l = $_SESSION['lang'];
        unset($_SESSION);
        session_start();
        $_SESSION['lang'] = $l;
    }

    /**
     * Associate 2FA with the user
     * @param string $secret 2FA secret key
     */
    public function create2FA(string $secret)
    {
        $this->update(["totp_key" => $secret]);
        //Creation des codes de secours
        for ($i = 1; $i <= 6; $i++)
            RescueCode::create(["user" => $this->user_id, "code" => rand(10000000, 99999999)]);
    }

    /**
     * Disassociate 2FA of the user
     */
    public function remove2FA()
    {
        $this->update(["totp_key" => NULL]);
        RescueCode::whereUser($this->user_id)->delete();
    }

    /**
     * Check if the user is an adminisrator
     * @return bool true if is admin, false otherwise
     */
    public function isAdmin(): bool
    {
        return $this->is_admin == "1";
    }

    /**
     * Return the user's firstname and lastname
     * @return string
     */
    public function name(): string
    {
        return $this->lastname . " " . $this->firstname;
    }

    /**
     * Check if the user can interact with a list
     * @param Liste $list List to check
     * @return bool true if user can interact, false otherwise
     */
    public function canInteractWithList(Liste $list): bool
    {
        return $this->user_id != "-1" && $this->user_id === $list->user_id;
    }

    /**
     * Authenticate the user
     * Fill the $_SESSION array and update some vars from the database
     */
    public function authenticate()
    {
        session_regenerate_id();
        //Mise a jour des variables utilisateur
        $this->update(['last_ip' => ip2long($_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']), "last_login" => date("Y-m-d H:i:s")]);
        $_SESSION['LOGGED_IN'] = true;
        $_SESSION['USER_ID'] = $this->user_id;
        $_SESSION['USER_NAME'] = $this->username;
    }

}