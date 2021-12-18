<?php

namespace mywishlist\mvc\views;

use Slim\Container;
use \mywishlist\mvc\models\{User};
use \mywishlist\exceptions\{ForbiddenException, CookieNotSetException};
use mywishlist\mvc\Renderer;

class UserView
{

    private $user;
    private Container $container;
    private $request;
    

    public function __construct(Container $c, User $user = NULL, $request = null)
    {
        $this->user = $user;
        $this->container = $c;
        $this->request = $request;
    }

    private function login(){
        $popup = "\n\t".match(filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? ""){
            "nouser" => "<div class='popup warning'>Aucun utilisateur trouvé</div>",
            "password"  => "<div class='popup warning'>Mot de passe incorrect</div>",
            "pc"  => "<div class='popup'>Mot de passe changé, veuillez vous reconnecter.</div>",
            default => ""
        };    
        $route = $this->container->router->pathFor('accounts',["action" => 'login']);
        $routeInscription = $this->container->router->pathFor('accounts',["action" => 'register']);
        return genererHeader("Login - Authentification", ["list.css"]). <<<EOD
            <h2>Connexion</h2>$popup
            <div>
                <form class='form_container' method="post" action="$route">
                    <label for="username">Utilisateur</label>
                    <input type="text" name="username" id="username" required />
                    <label for="password">Mot de passe</label>
                    <input type="password" name="password" id="password" required />
                    <button type="submit" value="OK" name="sendBtn">Connexion</button>
                    <a href="$routeInscription">Pas de compte ? S'inscrire</a>
                </form>
            <div>
        </body>
        </html>
        EOD;
    }

    private function register(){
        $popup = "\n\t".match(filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? ""){
            "invalid" => "<div class='popup warning'>Un ou plusieurs champs sont incorrects</div>",
            "password" => "<div class='popup warning'>Mot de passe invalide</div>",
            default => ""
        };    
        $route = $this->container->router->pathFor('accounts',["action" => 'register']);
        $routeConnexion = $this->container->router->pathFor('accounts',["action" => 'login']);
        return genererHeader("Inscription - Authentification", ["list.css"]). <<<EOD
            <h2>Inscription</h2>$popup
            <div>
                <form class='form_container' enctype="multipart/form-data" method="post" action="$route">
                    <label for="username">Utilisateur</label>
                    <input type="text" name="username" id="username" required />
                    <label for="lastname">Nom</label>
                    <input type="text" name="lastname" id="lastname" required />
                    <label for="firstname">Prénom</label>
                    <input type="text" name="firstname" id="firstname" required />
                    <label for="email">E-Mail</label>
                    <input type="email" name="email" id="email" required />
                    <label for="file_img">Avatar</label>
                    <input type="file" name="file_img" id="file_img"/>
                    <label for="password">Mot de passe</label>
                    <input required type="password" id="input-new-password" minlength="14" maxlength="40" name="password" pattern="(?=.*\d)(?=.*[a-z])(?=.*[~!@#$%^&*()\-_=+[\]{};:,<>\/?|])(?=.*[A-Z]).{14,}" >
                    <div id="message">
                        <h6 class="heading-small text-muted mb-4">Mot de passe valide</h6>
                        <p id="small" class="invalid">Une lettre <b>minuscule</b></p>
                        <p id="capital" class="invalid">Une lettre <b>majuscule</b></p>
                        <p id="number" class="invalid">Une <b>chiffre</b></p>
                        <p id="special" class="invalid">Un <b>caractère spécial</b></p>
                        <p id="length" class="invalid">14 <b>caractères minimum</b></p>
                    </div>
                    <button type="submit" value="OK" name="sendBtn">S'inscrire</button>
                    <a href="$routeConnexion">Deja un compte ? Se connecter</a>
                </form>
            <div>
            <script src="/assets/js/password-validator.js"></script>
        </body>
        </html>
        EOD;
    }

    private function showProfile(){
        $html = genererHeader("Votre Profil - MyWishList",["profile.css"]).file_get_contents(__DIR__.'\..\..\content\profile.phtml');
        $user = $this->user;
        $phtmlVars = array(
            "main_route"=> $this->container->router->pathFor('home'),
            "profile_route"=> $this->container->router->pathFor('accounts',["action" => 'profile']),
            "logout_route"=> $this->container->router->pathFor('accounts',["action" => 'logout']),
            "avatar_src"=> (!empty($user->avatar) && file_exists($this->container['users_upload_dir']."\\$user->avatar")) ? $this->container['users_upload_dir']."\\$user->avatar" : "https://www.gravatar.com/avatar/".md5(strtolower(trim($user->mail))),
            "user_username"=>$this->user->username,
            "user_firstname"=>$this->user->firstname,
            "user_lastname"=>$this->user->lastname,
            "user_email"=>$this->user->mail,
            "user_created_at"=>$this->user->created_at,
            "user_updated_at"=>$this->user->updated ?? "Jamais",
            "user_lastlogged_at"=>$this->user->last_login,
            "user_lastlogged_ip"=>long2ip($this->user->last_ip),
            "info_msg"=> match(filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? ""){
                "noavatar" => "<div class='popup warning fit'><span style='color:black;'>Vous n'avez pas d'avatar<br>Nous utilisons automatiquement un Gravatar dans ce cas.</span></div>",
                "password" => "<div class='popup warning fit'><span style='color:black;'>Mot de passe incorrect</span></div>",
                "no-change" => "<div class='popup warning fit'><span style='color:black;'>Aucun changement apporté</span></div>",
                "equals" => "<div class='popup warning fit'><span style='color:black;'>Mot de passe identique à l'ancien.</span></div>",
                "success" => "<div class='popup fit'><span style='color:black;'>Profil mis à jour</span></div>",
                default => ""
            },
        );
        foreach ($phtmlVars as $key => $value) {
            $html = str_replace("%".$key."%", $value, $html);
        };  
        return $html;
    }



    public function render($method){
        switch ($method) {
            case Renderer::LOGIN:
                return $this->login();
            case Renderer::REGISTER:
                return $this->register();
            case Renderer::PROFILE:
                return $this->showProfile();
            default:
                throw new ForbiddenException("Vous n'avez pas accès à cette page");
        }
    }

}
