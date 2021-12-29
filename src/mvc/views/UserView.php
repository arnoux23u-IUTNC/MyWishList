<?php

namespace mywishlist\mvc\views;

use Slim\Container;
use OTPHP\TOTP;
use \mywishlist\mvc\models\{User, Liste, RescueCode};
use \mywishlist\exceptions\ForbiddenException;
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

    private function login($authenticator = false)
    {
        $popup = ($authenticator ? "<div class='popup info fit'><span style='color:black;'>{$this->container->lang['user_2fa_request_code']}</span></div>" : match (filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? "") {
            "nouser" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['user_user_notfound']}</span></div>",
            "2fanok" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['user_2fa_incorrect_code']}</span></div>",
            "password" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['user_password_incorrect']}</span></div>",
            "not_logged" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['user_not_logged']}</span></div>",
            "pc" => "<div class='popup fit'><span style='color:black;'>{$this->container->lang['user_password_changed']}</span></div>",
            "2fa" => "<div class='popup fit'><span style='color:black;'>{$this->container->lang['user_2fa_enabled_log']}</span></div>",
            "2farec" => "<div class='popup fit'><span style='color:black;'>{$this->container->lang['user_2fa_disabled']}</span></div>",
            default => ""
        });
        $username = $authenticator ? filter_var($this->request->getParsedBodyParam('username'), FILTER_SANITIZE_STRING) ?? NULL : NULL;
        $password = $authenticator ? filter_var($this->request->getParsedBodyParam('password'), FILTER_SANITIZE_STRING) ?? NULL : NULL;
        $auth2FA = $authenticator ? "<div class='row fw'>\n\t\t\t\t\t\t\t\t<div class='form-group focused fw'>\n\t\t\t\t\t\t\t\t\t<label class='form-control-label' for='2fa'>{$this->container->lang['user_2fa_code']}</label>\n\t\t\t\t\t\t\t\t\t<input type='text' class='form-control form-control-alternative' required autofocus name='query-code' required maxlength='6' minlength='6' pattern='^\d{6}$'>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>" : "";
        $html = <<<HTML
        <div class="main-content">
            <nav class="navbar navbar-top navbar-expand-md navbar-dark" id="navbar-main">
                <div class="container-fluid">
                    <a class="h4 mb-0 text-white text-uppercase d-none d-lg-inline-block" href="{$this->container->router->pathFor('home')}"><img alt="logo" class="icon" src="/assets/img/logos/6.png"/>MyWishList</a>
                </div>
            </nav>
            <div class="header pb-8 pt-5 pt-lg-8 d-flex align-items-center" style="min-height: 300px;  background-size: cover; background-position: center top;">
                <span class="mask bg-gradient-default opacity-8"></span>
                <div class="container-fluid align-items-center">
                    <div class="row">
                        <div class="fw" style="position:relative;">
                            <h1 class="text-center text-white">{$this->container->lang['login_title']}</h1>
                            $popup
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 flex mt--7">
                <div class="fw">
                    <form method="post" action="{$this->container->router->pathFor('accounts', ['action' => 'login'])}">
                        <div class="card bg-secondary shadow">
                            <div class="card-body">
                                <div class="pl-lg-4">
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="username">{$this->container->lang['user_username']}</label>
                                            <input type="text" id="username" name="username" class="form-control form-control-alternative" required autofocus>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="password">{$this->container->lang['user_password']}</label>
                                            <input type="password" id="password" name="password" value="$password" class="form-control form-control-alternative" required/>
                                        </div>
                                    </div>
                                    $auth2FA
                                    <div class="row fw">
                                        <button type="submit" class="btn btn-sm btn-primary" value="OK" name="sendBtn">{$this->container->lang['login_title']}</button>
                                        <a href="{$this->container->router->pathFor('accounts', ['action' => 'register'])}" class="btn btn-sm btn-default">{$this->container->lang['login_to_register']}</a>
                                        <a href="{$this->container->router->pathFor('2fa', ['action' => 'recover'], ['username' => $username])}" class="btn btn-sm btn-default">{$this->container->lang['login_lost_2FA']}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        HTML;
        return genererHeader("{$this->container->lang['login_title']} - MyWishList", ["profile.css"]).$html;
    }

    private function recover2FA()
    {
        $username = filter_var($this->request->getQueryParam('username'), FILTER_SANITIZE_STRING) ?? "";
        $popup = "\n\t" . match (filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? "") {
                "nok" => "<div class='popup warning'>{$this->container->lang['user_2fa_incorrect_code']}</div>",
                default => ""
            };
        $html = <<<HTML
        <div class="main-content">
            <nav class="navbar navbar-top navbar-expand-md navbar-dark" id="navbar-main">
                <div class="container-fluid">
                    <a class="h4 mb-0 text-white text-uppercase d-none d-lg-inline-block" href="{$this->container->router->pathFor('home')}"><img alt="logo" class="icon" src="/assets/img/logos/6.png"/>MyWishList</a>
                </div>
            </nav>
            <div class="header pb-8 pt-5 pt-lg-8 d-flex align-items-center" style="min-height: 300px;  background-size: cover; background-position: center top;">
                <span class="mask bg-gradient-default opacity-8"></span>
                <div class="container-fluid align-items-center">
                    <div class="row">
                        <div class="fw" style="position:relative;">
                            <h1 class="text-center text-white">{$this->container->lang['user_2fa_recover']}</h1>
                            $popup
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 flex mt--7">
                <div class="fw">
                    <form method="post" action="{$this->container->router->pathFor('2fa', ['action' => 'recover'])}">
                        <div class="card bg-secondary shadow">
                            <div class="card-body">
                                <div class="pl-lg-4">
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="username">{$this->container->lang['user_username']}</label>
                                            <input type="text" id="username" name="username" class="form-control form-control-alternative" value="$username" required autofocus>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="rescue">{$this->container->lang['user_2fa_rescue_code']}</label>
                                            <input type="text" id="rescue" name="rescue" class="form-control form-control-alternative" maxlength="8" minlength="8" pattern="^\d{8}$" required/>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <button type="submit" class="btn btn-sm btn-primary" value="OK" name="sendBtn">{$this->container->lang['user_2fa_delete']}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        HTML;
        return genererHeader("{$this->container->lang['lost_2fa']} - MyWishList", ["profile.css"]).$html;
    }

    private function register()
    {
      $popup = match (filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? "") {
        "invalid" => "<div class='popup warning'>{$this->container->lang['phtml_error_fields']}</div>",
        "password" => "<div class='popup warning'>{$this->container->lang['user_password_incorrect']}</div>",
        default => ""
      };
        $html = <<<HTML
        <div class="main-content">
            <nav class="navbar navbar-top navbar-expand-md navbar-dark" id="navbar-main">
                <div class="container-fluid">
                    <a class="h4 mb-0 text-white text-uppercase d-none d-lg-inline-block" href="{$this->container->router->pathFor('home')}"><img alt="logo" class="icon" src="/assets/img/logos/6.png"/>MyWishList</a>
                </div>
            </nav>
            <div class="header pb-8 pt-5 pt-lg-8 d-flex align-items-center" style="min-height: 300px;  background-size: cover; background-position: center top;">
                <span class="mask bg-gradient-default opacity-8"></span>
                <div class="container-fluid align-items-center">
                    <div class="row">
                        <div class="fw" style="position:relative;">
                            <h1 class="text-center text-white">{$this->container->lang['title_register']}</h1>
                            $popup
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 flex mt--7">
                <div class="fw">
                    <form method="post" onsubmit="return (assertFile() && matchPwd())" enctype="multipart/form-data" action="{$this->container->router->pathFor('accounts', ['action' => 'register'])}">
                        <div class="card bg-secondary shadow">
                            <div class="card-body">
                                <div class="pl-lg-4">
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="username">{$this->container->lang['user_username']}</label>
                                            <input type="text" id="username" name="username" class="form-control form-control-alternative" required autofocus>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="lastname">{$this->container->lang['user_lastname']}</label>
                                            <input type="text" id="lastname" name="lastname" class="form-control form-control-alternative" required>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="firstname">{$this->container->lang['user_firstname']}</label>
                                            <input type="text" id="firstname" name="firstname" class="form-control form-control-alternative" required>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="email">{$this->container->lang['user_email']}</label>
                                            <input type="email" id="email" name="email" class="form-control form-control-alternative" required>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="file_img">{$this->container->lang['user_avatar']}</label>
                                            <input accept="image/*" type="file" id="file_img" name="file_img" class="form-control form-control-alternative">
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="input-new-password">{$this->container->lang['user_password']}</label>
                                            <input type="password" minlength="14" maxlength="40" pattern="(?=.*\d)(?=.*[a-z])(?=.*[~!@#$%^&*()\-_=+[\]{};:,<>\/?|])(?=.*[A-Z]).{14,}" id="input-new-password" name="password" class="form-control form-control-alternative" required/>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div id="message">
                                            <h6 class="heading-small text-muted mb-4">{$this->container->lang['password_form_valid']}</h6>
                                            <p id="small" class="invalid">{$this->container->lang['password_form_valid_small']}</p>
                                            <p id="capital" class="invalid">{$this->container->lang['password_form_valid_capital']}</p>
                                            <p id="number" class="invalid">{$this->container->lang['password_form_valid_number']}</p>
                                            <p id="special" class="invalid">{$this->container->lang['password_form_valid_special']}</p>
                                            <p id="length" class="invalid">{$this->container->lang['password_form_valid_length']}</p>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="input-new-password-c">{$this->container->lang['user_password_confirm']}</label>
                                            <input type="password" minlength="14" maxlength="40" pattern="(?=.*\d)(?=.*[a-z])(?=.*[~!@#$%^&*()\-_=+[\]{};:,<>\/?|])(?=.*[A-Z]).{14,}" id="input-new-password-c" name="password-confirm" class="form-control form-control-alternative" required/>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <button type="submit" class="btn btn-sm btn-primary" value="OK" name="sendBtn">{$this->container->lang['title_register']}</button>
                                        <a href="{$this->container->router->pathFor('accounts', ['action' => 'login'])}" class="btn btn-sm btn-default">{$this->container->lang['register_to_login']}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script src="/assets/js/password-validator.js"></script>
        <script src="/assets/js/avatar-register.js"></script>
        HTML;
        return genererHeader("{$this->container->lang['title_register']} - MyWishList", ["profile.css"]).$html;
    }

    private function showProfile()
    {
        $html = genererHeader("{$this->container->lang['profile_title']} - MyWishList", ["profile.css"]) . file_get_contents(__DIR__ . '\..\..\content\profile.phtml');
        $user = $this->user;
        $lists = Liste::whereUserId($user->user_id)->get();
        $htmlLists = "";
        foreach ($lists as $k=>$list) {
          $k++;
          $route = $this->container->router->pathFor('lists_show_id', ["id" => $list->no]);
          $htmlLists .= "<a href='$route'><div class='list form-control form-control-alternative'><span class='form-control-label'>$k | $list->titre</span></div></a>";
        }
        $phtmlVars = array(
            "main_route"=> $this->container->router->pathFor('home'),
            "mylists"=> $htmlLists,
            "profile_route"=> $this->container->router->pathFor('accounts',["action" => 'profile']),
            "logout_route"=> $this->container->router->pathFor('accounts',["action" => 'logout']),
            "2fa_route"=> $this->container->router->pathFor('2fa', ["action" => 'manage']),
            "avatar_src"=> (!empty($user->avatar) && file_exists($this->container['users_upload_dir'].DIRECTORY_SEPARATOR."$user->avatar")) ? "/assets/img/avatars/$user->avatar" : "https://www.gravatar.com/avatar/".md5(strtolower(trim($user->mail)))."?size=120",
            "user_username"=>$user->username,
            "user_firstname"=>$user->firstname,
            "user_lastname"=>$user->lastname,
            "user_email"=>$user->mail,
            "user_created_at"=>$user->created_at,
            "user_updated_at"=>$user->updated ?? "Jamais",
            "user_lastlogged_at"=>$user->last_login,
            "user_lastlogged_ip"=>long2ip($user->last_ip),
            "info_msg"=> match(filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? ""){
                "noavatar" => "<div class='popup warning fit'><span style='color:black;'>Vous n'avez pas d'avatar<br>Nous utilisons automatiquement un Gravatar dans ce cas.</span></div>",
                "password" => "<div class='popup warning fit'><span style='color:black;'>Mot de passe incorrect</span></div>",
                "no-change" => "<div class='popup warning fit'><span style='color:black;'>Aucun changement apporté</span></div>",
                "equals" => "<div class='popup warning fit'><span style='color:black;'>Mot de passe identique à l'ancien.</span></div>",
                "2faok" => "<div class='popup fit'><span style='color:black;'>2FA Activé.</span></div>",
                "2fanok" => "<div class='popup warning fit'><span style='color:black;'>Erreur pendant l'activation de 2FA.</span></div>",
                "2fa_disabled" => "<div class='popup fit'><span style='color:black;'>2FA desactivé</span></div>",
                "success" => "<div class='popup fit'><span style='color:black;'>Profil mis à jour</span></div>",
                "ok" => "<div class='popup fit'><span style='color:black;'>{$this->container->lang['image_saved']}</div>",
                "typeerr"  => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_type_error']}</span></div>",
                "sizeerr"  => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_size_error']}</span></div>",
                "writeerr"  => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_write_error']}</span></div>",
                "fileexist"  => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_exists']}</span></div>",
                "error"  => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_error']}</span></div>",
                default => ""
            },
        );
        foreach ($phtmlVars as $key => $value) {
            $html = str_replace("%" . $key . "%", $value, $html);
        };
        preg_match_all("/{#(\w|_)+#}/", $html, $matches);
        foreach ($matches[0] as $match) {
            $html = str_replace($match, $this->container->lang[str_replace(["{", "#", "}"], "", $match)], $html);
        }
        return $html;
    }

    private function enable2FA()
    {
        $route_main = $this->container->router->pathFor('home');
        $route_2fa = $this->container->router->pathFor('2fa', ["action" => 'enable']);
        $otp = TOTP::create($this->secret);
        $otp->setLabel('MyWishList');
        $img_src = $otp->getQrCodeUri('https://api.qrserver.com/v1/create-qr-code/?data=[DATA]&size=300x300&ecc=M', '[DATA]');
        return genererHeader("Activation de 2FA - MyWishList", ["profile.css"]) . <<<HTML
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
                    <input type="text" autofocus name="query-code" required maxlength="6" minlength="6" pattern="^\d{6}$">
                    <button type="submit" name="sendBtn" value="ok" class="btn btn-sm btn-primary">Activer</button>
                </form>
            </div>
        </div>
        HTML;
    }

    private function manage2FA()
    {
        $route_main = $this->container->router->pathFor('home');
        $route_2fa = $this->container->router->pathFor('2fa', ["action" => "disable"]);
        return genererHeader("Gestion 2FA - MyWishList", ["profile.css"]) . <<<HTML
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
      HTML;
    }

    private function show2FACodes()
    {
        $route_main = $this->container->router->pathFor('home');
        $route_login = $this->container->router->pathFor('accounts', ["action" => 'login'], ["info" => "2fa"]);
        $codes = "";
        foreach (RescueCode::whereUser($this->user->user_id)->get() as $code) {
            $codes .= "<p>$code->code</p>";
        }
        return genererHeader("Gestion 2FA - MyWishList", ["profile.css"]) . <<<HTML
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
        HTML;
    }

    public function render($method)
    {
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
                throw new ForbiddenException(message: $this->container->lang['exception_page_not_allowed']);
        }
    }

    public function with2FA($secret)
    {
        $this->secret = $secret;
        return $this;
    }

}
