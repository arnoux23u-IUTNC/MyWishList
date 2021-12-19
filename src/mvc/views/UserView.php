<?php

namespace mywishlist\mvc\views;

use Slim\Container;
use OTPHP\TOTP;
use \mywishlist\mvc\models\{User, RescueCode};
use \mywishlist\exceptions\{ForbiddenException, CookieNotSetException};
use mywishlist\mvc\Renderer;

class UserView
{

    private $user;
    private Container $container;
    private $request;
    private $secret;
    

    public function __construct(Container $c, User $user = NULL, $request = null)
    {
        $this->user = $user;
        $this->container = $c;
        $this->request = $request;
    }

    private function login($authenticator = false){
        
        $popup = "\n\t".( $authenticator ? "\n\t<div class='popup info'>Entrez votre code 2FA.</div>" : match(filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? ""){
            "nouser" => "<div class='popup warning'>Aucun utilisateur trouvé</div>",
            "2fanok" => "<div class='popup warning'>Code 2FA incorrect</div>",
            "password"  => "<div class='popup warning'>Mot de passe incorrect</div>",
            "not_logged"  => "<div class='popup warning'>Vous n'êtes pas connecté.</div>",
            "pc"  => "<div class='popup'>Mot de passe changé, veuillez vous reconnecter.</div>",
            "2fa"  => "<div class='popup'>2FA activé, veuillez vous reconnecter.</div>",
            "2farec"  => "<div class='popup'>2FA désactivé.</div>",
            default => ""
        }); 
        $username = $authenticator ? filter_var($this->request->getParsedBodyParam('username'), FILTER_SANITIZE_STRING) ?? NULL : NULL;
        $password = $authenticator ? filter_var($this->request->getParsedBodyParam('password'), FILTER_SANITIZE_STRING) ?? NULL : NULL;
        $auth2FA = $authenticator ? "<label for='2fa'>Code 2FA</label>\n\t\t<input type='text' autofocus name='query-code' required maxlength='6' minlenght='6' pattern='^\d{6}$'>" : "";
        $route = $this->container->router->pathFor('accounts',["action" => 'login']);
        $routeInscription = $this->container->router->pathFor('accounts',["action" => 'register']);
        $routeRecover = $this->container->router->pathFor('2fa',["action" => 'recover'],["username" => $username]);
        return genererHeader("Login - Authentification", ["list.css"]). <<<EOD
            <h2>Connexion</h2>$popup
            <div>
                <form class='form_container' method="post" action="$route">
                    <label for="username">Utilisateur</label>
                    <input type="text" name="username" id="username" value="$username" required />
                    <label for="password">Mot de passe</label>
                    <input type="password" name="password" id="password" value="$password" required />$auth2FA
                    <button type="submit" value="OK" name="sendBtn">Connexion</button>
                    <a href="$routeInscription">Pas de compte ? S'inscrire</a>
                    <a href="$routeRecover">Code perdu ?</a>
                </form>
            <div>
        </body>
        </html>
        EOD;
    }

    private function recover2FA(){
      $username = filter_var($this->request->getQueryParam('username'), FILTER_SANITIZE_STRING) ?? "";
      $routeRecover = $this->container->router->pathFor('2fa',["action" => 'recover']);
      $popup = "\n\t".match(filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? "" ){
        "nok" => "<div class='popup warning'>Code 2FA incorrect</div>",
        default => ""
      };
      return genererHeader("Recover - Authentification", ["list.css"]). <<<EOD
          <h2>Récupération 2FA</h2>
          <div>
              <form class='form_container' method="post" action="$routeRecover">$popup
                  <label for="username">Nom d'utilisateur</label>
                  <input type="text" autofocus name="username" value="$username" required>
                  <label for="rescue">Code de secours</label>
                  <input type="text" name="rescue" required maxlength="8" minlenght="8" pattern="^\d{8}$">
                  <button type="submit" value="OK" name="sendBtn">Supprimer 2FA</button>
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
        $html = genererHeader("Votre Profil - MyWishList",["profile.css"]).file_get_contents(__DIR__.'\..\..\phtml\profile.phtml');
        $user = $this->user;
        $phtmlVars = array(
            "main_route"=> $this->container->router->pathFor('home'),
            "profile_route"=> $this->container->router->pathFor('accounts',["action" => 'profile']),
            "logout_route"=> $this->container->router->pathFor('accounts',["action" => 'logout']),
            "2fa_route"=> $this->container->router->pathFor('2fa', ["action" => 'manage']),
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
                "2faok" => "<div class='popup fit'><span style='color:black;'>2FA Activé.</span></div>",
                "2fanok" => "<div class='popup warning fit'><span style='color:black;'>Erreur pendant l'activation de 2FA.</span></div>",
                "2fa_disabled" => "<div class='popup fit'><span style='color:black;'>2FA desactivé</span></div>",
                "success" => "<div class='popup fit'><span style='color:black;'>Profil mis à jour</span></div>",
                default => ""
            },
        );
        foreach ($phtmlVars as $key => $value) {
            $html = str_replace("%".$key."%", $value, $html);
        };  
        return $html;
    }

    private function enable2FA(){
        $route_main = $this->container->router->pathFor('home');
        $route_2fa = $this->container->router->pathFor('2fa', ["action" => 'enable']);
        $otp = TOTP::create($this->secret);
        $otp->setLabel('MyWishList');
        $img_src = $otp->getQrCodeUri('https://api.qrserver.com/v1/create-qr-code/?data=[DATA]&size=300x300&ecc=M','[DATA]');
        return genererHeader("Activation de 2FA - MyWishList",["profile.css"]).<<<EOD
        <div class="main-content">
        <nav class="navbar navbar-top navbar-expand-md navbar-dark" id="navbar-main">
          <div class="container-fluid">
            <a class="h4 mb-0 text-white text-uppercase d-none d-lg-inline-block" href="$route_main"><img alt="logo" class="icon" src="/assets/img/logos/6.png" />MyWishList</a>
          </div>
        </nav>
        <div class="header pb-8 pt-5 pt-lg-8 d-flex align-items-center" style="min-height: 300px;  background-size: cover; background-position: center top;">
          <span class="mask bg-gradient-default opacity-8"></span>
        </div>
        <div class="container-fluid mt--7">
          <div class="row">
            <div style="width:100%;">
                <div class="card bg-secondary shadow">
                  <div class="card-header bg-white border-0">
                    <h3 class="mb-0">Mon authentification 2FA</h3>
                  </div>
                  <div class="card-body">
                    <div class="pl-lg-4">
                      <div class="row">
                        <div class="col-lg-4">
                          <div>
                            <button disabled class="btn btn-sm btn-danger btn-danger-fr">Supprimer 2FA</button>
                            <a href="#popup" class="btn btn-sm btn-primary">Activer 2FA</a>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
            </div>
          </div>
        </div>
        </div>
        <div id="popup" class="overlay">
            <div class="popup1">
                <h2 style="padding-bottom:2vh;">Activation 2FA</h2>
                <a class="close" href="#">&times;</a>
                <form method="POST" action="$route_2fa" class="content">
                    <h3>Merci d'entrer le code fourni par Google Authenticator dans la zone de texte ci-dessous.</h3>
                    <div class="d-flex">
                        <img alt="qr" src="$img_src"/>
                        <div style='padding-left:2vw;width:100%;display:flex;flex-direction:column;'>
                            <label class="form-control-label" for="private_key">Votre clé : </label>
                            <input type="text" readonly id="private_key" name="private_key" value="$this->secret">
                        </div>
                    </div>
                    <input type="text" autofocus name="query-code" required maxlength="6" minlenght="6" pattern="^\d{6}$">
                    <button type="submit" name="sendBtn" value="ok" class="btn btn-sm btn-primary">Activer</button>
                </form>
            </div>
        </div>
        EOD;
    }

    private function manage2FA(){
      $route_main = $this->container->router->pathFor('home');
      $route_2fa = $this->container->router->pathFor('2fa', ["action" => "disable"]);
      return genererHeader("Gestion 2FA - MyWishList",["profile.css"]).<<<EOD
      <div class="main-content">
      <nav class="navbar navbar-top navbar-expand-md navbar-dark" id="navbar-main">
        <div class="container-fluid">
          <a class="h4 mb-0 text-white text-uppercase d-none d-lg-inline-block" href="$route_main"><img alt="logo" class="icon" src="/assets/img/logos/6.png" />MyWishList</a>
        </div>
      </nav>
      <div class="header pb-8 pt-5 pt-lg-8 d-flex align-items-center" style="min-height: 300px;  background-size: cover; background-position: center top;">
        <span class="mask bg-gradient-default opacity-8"></span>
      </div>
      <div class="container-fluid mt--7">
        <form method="post" action="$route_2fa">
          <div class="row">
            <div style="width:100%;">
                <div class="card bg-secondary shadow">
                  <div class="card-header bg-white border-0">
                    <h3 class="mb-0">Mon authentification 2FA</h3>
                  </div>
                  <div class="card-body">
                    <div class="pl-lg-4">
                      <div class="row">
                        <div class="col-lg-4">
                          <div class="popup">
                            <span>2FA est activé.</span>
                          </div>
                          <div>
                            <button type="submit" name="sendBtn" value="ok" class="btn btn-sm btn-danger text-white">Supprimer 2FA</a>
                            <button disabled class="btn btn-sm btn-default">Activer 2FA</button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
            </div>
          </div>
        </form>
      </div>
      </div>
      EOD;
  }

    private function show2FACodes(){
        $route_main = $this->container->router->pathFor('home');
        $route_login = $this->container->router->pathFor('accounts', ["action" => 'login'],["info" => "2fa"]);
        $codes = "";
        foreach (RescueCode::whereUser($this->user->user_id)->get() as $code){
            $codes.="<p>$code->code</p>";
        }
        return genererHeader("Gestion 2FA - MyWishList",["profile.css"]).<<<EOD
        <div class="main-content">
        <nav class="navbar navbar-top navbar-expand-md navbar-dark" id="navbar-main">
          <div class="container-fluid">
            <a class="h4 mb-0 text-white text-uppercase d-none d-lg-inline-block" href="$route_main"><img alt="logo" class="icon" src="/assets/img/logos/6.png" />MyWishList</a>
          </div>
        </nav>
        <div class="header pb-8 pt-5 pt-lg-8 d-flex align-items-center" style="min-height: 300px;  background-size: cover; background-position: center top;">
          <span class="mask bg-gradient-default opacity-8"></span>
        </div>
        <div class="container-fluid mt--7">
          <div class="row">
            <div style="width:100%;">
                <div class="card bg-secondary shadow">
                  <div class="card-header bg-white border-0">
                    <h3 class="mb-0">Mes codes de récupération</h3>
                  </div>
                  <div class="card-body">
                    <div class="pl-lg-4">
                      <div class="row">
                        <div class="col-lg-4">
                          <div class="code">
                            $codes
                          </div>
                          <div>
                            <a href="$route_login" class="btn btn-sm btn-default">C'est noté !</a>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
        </div>
        EOD;
    }

    public function render($method){
        switch ($method) {
            case Renderer::LOGIN:
              return $this->login();
            case Renderer::LOGIN_2FA:
              return $this->login(true);
            case Renderer::REGISTER:
                return $this->register();
            case Renderer::PROFILE:
                return $this->showProfile();
            case Renderer::ENABLE_2FA:
                return $this->enable2FA();
            case Renderer::MANAGE_2FA:
                return $this->manage2FA();
            case Renderer::SHOW_2FA_CODES:
                return $this->show2FACodes();
            case Renderer::RECOVER_2FA:
              return $this->recover2FA();
            default:
                throw new ForbiddenException("Vous n'avez pas accès à cette page");
        }
    }

    public function with2FA($secret){
        $this->secret = $secret;
        return $this;
    }

}
