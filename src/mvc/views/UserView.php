<?php /** @noinspection PhpUndefinedVariableInspection */

/** @noinspection PhpUndefinedFieldInspection */

namespace mywishlist\mvc\views;

use Slim\Container;
use Slim\Http\Request;
use OTPHP\TOTP;
use mywishlist\mvc\Renderer;
use mywishlist\exceptions\ForbiddenException;
use mywishlist\mvc\models\{User, Liste, RescueCode};

/**
 * User View
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\views
 */
class UserView
{

    /**
     * @var User|null User associated with the view
     */
    private ?User $user;
    private Container $container;
    private Request $request;
    /**
     * @var string 2fa secret key
     */
    private string $secret;

    /**
     * UserView constructor
     * @param Container $c
     * @param User|null $user
     * @param Request|null $request
     */
    public function __construct(Container $c, User $user = NULL, Request $request = null)
    {
        $this->user = $user;
        $this->container = $c;
        $this->request = $request;
    }

    /**
     * Display login page
     * @param false $authenticator at true if user uses 2fa
     * @return string html code
     */
    private function login(bool $authenticator = false): string
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
        $auth2FAReset = $authenticator ? "<a href=\"{$this->container->router->pathFor('2fa', ['action' => 'recover'], ['username' => $username])}\" class='btn btn-sm btn-default'>{$this->container->lang['login_lost_2FA']}</a>" : "";
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
                                            <div class="pfield"><input type="password" id="password" name="password" value="$password" class="form-control form-control-alternative" required/><i onclick="pwd('password', event)" class="pwdicon far fa-eye"></i></div>
                                        </div>
                                    </div>
                                    $auth2FA
                                    <div class="row fw">
                                        <button type="submit" class="btn btn-sm btn-primary" value="OK" name="sendBtn">{$this->container->lang['login_title']}</button>
                                        <a href="{$this->container->router->pathFor('accounts', ['action' => 'forgot_password'])}" class="btn btn-sm btn-danger">{$this->container->lang['login_lost_password']} ?</a>
                                        <a href="{$this->container->router->pathFor('accounts', ['action' => 'register'])}" class="btn btn-sm btn-default">{$this->container->lang['login_to_register']}</a>
                                        $auth2FAReset
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script src="/assets/js/password-viewer.js"></script>
        HTML;
        return genererHeader("{$this->container->lang['login_title']} - MyWishList", ["profile.css"]) . $html;
    }

    /**
     * Display forgot password page
     * @return string html code
     */
    private function forgotPassword(): string
    {
        $popup = match (filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? "") {
            "nouser" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['user_user_notfound']}</span></div>",
            "sent" => "<div class='popup fit'><span style='color:black;'>{$this->container->lang['email_sent']}</span></div>",
            "not_sent" => "<div class='popup fit'><span style='color:black;'>{$this->container->lang['email_not_sent']}</span></div>",
            "already" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['reset_already_asked']}</span></div>",
            "invalid" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['token_invalid']}</span></div>",
            "emailnovalid" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['email_not_valid']}</span></div>",
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
                            <h1 class="text-center text-white">{$this->container->lang['login_lost_password']}</h1>
                            $popup
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 flex mt--7">
                <div class="fw">
                    <form method="post" action="{$this->container->router->pathFor('accounts', ['action' => 'forgot_password'])}">
                        <div class="card bg-secondary shadow">
                            <div class="card-body">
                                <div class="pl-lg-4">
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="email">{$this->container->lang['user_email']}</label>
                                            <input type="email" id="email" name="email" class="form-control form-control-alternative mb-4" required autofocus>
                                            <span class="form-text mt-4" style="color:var(--red);">{$this->container->lang['email_warn_microsoft']}</span>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <button type="submit" class="btn btn-sm btn-primary" value="OK" name="sendBtn">{$this->container->lang['validate']}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        HTML;
        return genererHeader("{$this->container->lang['login_lost_password']} - MyWishList", ["profile.css"]) . $html;
    }

    /**
     * Display reset password page
     * @return string html code
     */
    private function resetPassword(): string
    {
        $mail = filter_var($this->request->getQueryParam('mail'), FILTER_VALIDATE_EMAIL) ? filter_var($this->request->getQueryParam('mail'), FILTER_SANITIZE_EMAIL) ?? "" : "";
        $token = filter_var($this->request->getQueryParam('token'), FILTER_SANITIZE_STRING) ?? "";
        $popup = match (filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? "") {
            "nouser" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['user_user_notfound']}</span></div>",
            "sent" => "<div class='popup fit'><span style='color:black;'>{$this->container->lang['email_sent']}</span></div>",
            "not_sent" => "<div class='popup fit'><span style='color:black;'>{$this->container->lang['email_not_sent']}</span></div>",
            "already" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['reset_already_asked']}</span></div>",
            "invalid" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['token_invalid']}</span></div>",
            "emailnovalid" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['email_not_valid']}</span></div>",
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
                            <h1 class="text-center text-white">{$this->container->lang['forgot_password_title']}</h1>
                            $popup
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 flex mt--7">
                <div class="fw">
                    <form method="post" onsubmit="return matchPwd()" action="{$this->container->router->pathFor('accounts', ['action' => 'reset_password'])}">
                        <div class="card bg-secondary shadow">
                            <div class="card-body">
                                <div class="pl-lg-4">
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="input-new-password">{$this->container->lang['user_new_password']}</label>
                                            <div class="pfield"><input type="password" id="input-new-password" minlength="14" maxlength="40" pattern="(?=.*\d)(?=.*[a-z])(?=.*[~!@#$%^&*()\-_=+[\]{};:,<>\/?|])(?=.*[A-Z]).{14,}" name="input-new-password" class="form-control form-control-alternative"><i onclick="pwd('input-new-password', event)" class="pwdicon far fa-eye"></i></div>
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
                                            <div class="pfield"><input type="password" id="input-new-password-c" minlength="14" maxlength="40" pattern="(?=.*\d)(?=.*[a-z])(?=.*[~!@#$%^&*()\-_=+[\]{};:,<>\/?|])(?=.*[A-Z]).{14,}" name="input-new-password-c" class="form-control form-control-alternative"><i onclick="pwd('input-new-password-c', event)" class="pwdicon far fa-eye"></i></div>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <button type="submit" class="btn btn-sm btn-primary" value="OK" name="sendBtn">{$this->container->lang['validate']}</button>
                                    </div>
                                    <div class="row fw">
                                        <input type="hidden" id="mail" name="mail" value="$mail" class="form-control form-control-alternative"/>
                                        <input type="hidden" id="token" name="token" value="$token" class="form-control form-control-alternative"/>
                                        <input type="hidden" id="auth" name="auth" value="1" class="form-control form-control-alternative"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script src="/assets/js/password-validator.js"></script>
        <script src="/assets/js/password-viewer.js"></script>
        HTML;
        return genererHeader("{$this->container->lang['forgot_password_title']} - MyWishList", ["profile.css"]) . $html;
    }

    /**
     * Display the 2fa recover page
     * @return string html code
     */
    private function recover2FA(): string
    {
        $username = filter_var($this->request->getQueryParam('username'), FILTER_SANITIZE_STRING) ?? "";
        $routeRecover = $this->container->router->pathFor('2fa', ["action" => 'recover']);
        $popup = "\n\t" . match (filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? "") {
                "nok" => "<div class='popup warning'>{$this->container->lang['user_2fa_incorrect_code']}</div>",
                default => ""
            };
        return genererHeader("Auth - MyWishList", ["list.css"]) . <<<EOD
          <h2>{$this->container->lang['user_2fa_recover']}</h2>
          <div>
              <form class='form_container' method="post" action="$routeRecover">$popup
                  <label for="username">{$this->container->lang['user_username']}</label>
                  <input type="text" autofocus name="username" value="$username" required>
                  <label for="rescue">{$this->container->lang['user_2fa_rescue_code']}</label>
                  <input type="text" name="rescue" required maxlength="8" minlength="8" pattern="^\d{8}$">
                  <button type="submit" value="OK" name="sendBtn">{$this->container->lang['user_2fa_delete']}</button>
              </form>
          <div>
      </body>
      </html>
      EOD;
    }

    /**
     * Display the register page
     * @return string html code
     */
    private function register(): string
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
                                            <label class="form-control-label" for="password">{$this->container->lang['user_password']}</label>
                                            <div class="pfield"><input type="password" minlength="14" maxlength="40" pattern="(?=.*\d)(?=.*[a-z])(?=.*[~!@#$%^&*()\-_=+[\]{};:,<>\/?|])(?=.*[A-Z]).{14,}" id="input-new-password" name="password" class="form-control form-control-alternative" required/><i onclick="pwd('input-new-password', event)" class="pwdicon far fa-eye"></i></div>
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
                                            <label class="form-control-label" for="password-confirm">{$this->container->lang['user_password_confirm']}</label>
                                            <div class="pfield"><input type="password" minlength="14" maxlength="40" pattern="(?=.*\d)(?=.*[a-z])(?=.*[~!@#$%^&*()\-_=+[\]{};:,<>\/?|])(?=.*[A-Z]).{14,}" id="input-new-password-c" name="password-confirm" class="form-control form-control-alternative" required/><i onclick="pwd('input-new-password-c', event)" class="pwdicon far fa-eye"></i></div>
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
        <script src="/assets/js/password-viewer.js"></script>
        <script src="/assets/js/avatar-register.js"></script>
        HTML;
        return genererHeader("{$this->container->lang['title_register']} - MyWishList", ["profile.css"]) . $html;
    }

    /**
     * Display the profile page
     * @return string html code
     */
    private function showProfile(): string
    {
        $html = genererHeader("{$this->container->lang['profile_title']} - MyWishList", ["profile.css"]) . file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'profile.phtml');
        $user = $this->user;
        $lists = Liste::whereUserId($user->user_id)->get();
        $htmlLists = "";
        foreach ($lists as $k => $list) {
            $k++;
            $route = $this->container->router->pathFor('lists_show_id', ["id" => $list->no]);
            $htmlLists .= "<a href='$route'><div class='list form-control form-control-alternative'><span class='form-control-label'>$k | $list->titre</span></div></a>";
        }
        $phtmlVars = array(
            "main_route" => $this->container->router->pathFor('home'),
            "api_key_route" => $this->container->router->pathFor('accounts', ['action' => 'api_key']),
            "mylists" => $htmlLists,
            "profile_route" => $this->container->router->pathFor('accounts', ["action" => 'profile']),
            "logout_route" => $this->container->router->pathFor('accounts', ["action" => 'logout']),
            "2fa_route" => $this->container->router->pathFor('2fa', ["action" => 'manage']),
            "avatar_src" => (!empty($user->avatar) && file_exists($this->container['users_upload_dir'] . DIRECTORY_SEPARATOR . "$user->avatar")) ? "/assets/img/avatars/$user->avatar" : "https://www.gravatar.com/avatar/" . md5(strtolower(trim($user->mail))) . "?size=120",
            "user_username" => $user->username,
            "user_firstname" => $user->firstname,
            "user_lastname" => $user->lastname,
            "user_email" => $user->mail,
            "user_created_at" => $user->created_at,
            "user_updated_at" => $user->updated ?? "Jamais",
            "user_api_key" => !empty($user->api_key) ? "<span class='hidden'>$user->api_key</span>" : "Aucune",
            "user_lastlogged_at" => $user->last_login,
            "user_lastlogged_ip" => long2ip($user->last_ip),
            "info_msg" => match (filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? "") {
                "noavatar" => "<div class='popup warning fit'><span style='color:black;'>Vous n'avez pas d'avatar<br>Nous utilisons automatiquement un Gravatar dans ce cas.</span></div>",
                "password" => "<div class='popup warning fit'><span style='color:black;'>Mot de passe incorrect</span></div>",
                "no-change" => "<div class='popup warning fit'><span style='color:black;'>Aucun changement apporté</span></div>",
                "equals" => "<div class='popup warning fit'><span style='color:black;'>Mot de passe identique à l'ancien.</span></div>",
                "2faok" => "<div class='popup fit'><span style='color:black;'>2FA Activé.</span></div>",
                "2fanok" => "<div class='popup warning fit'><span style='color:black;'>Erreur pendant l'activation de 2FA.</span></div>",
                "2fa_disabled" => "<div class='popup fit'><span style='color:black;'>2FA desactivé</span></div>",
                "success" => "<div class='popup fit'><span style='color:black;'>Profil mis à jour</span></div>",
                "ok" => "<div class='popup fit'><span style='color:black;'>{$this->container->lang['image_saved']}</div>",
                "typeerr" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_type_error']}</span></div>",
                "sizeerr" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_size_error']}</span></div>",
                "writeerr" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_write_error']}</span></div>",
                "fileexist" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_exists']}</span></div>",
                "error" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_error']}</span></div>",
                default => ""
            },
        );
        foreach ($phtmlVars as $key => $value) {
            $html = str_replace("%" . $key . "%", $value, $html);
        }
        preg_match_all("/{#(\w|_)+#}/", $html, $matches);
        foreach ($matches[0] as $match) {
            $html = str_replace($match, $this->container->lang[str_replace(["{", "#", "}"], "", $match)], $html);
        }
        return $html;
    }

    /**
     * Display the 2fa enabling page
     * @return string html code
     */
    private function enable2FA(): string
    {
        $route_main = $this->container->router->pathFor('home');
        $route_2fa = $this->container->router->pathFor('2fa', ["action" => 'enable']);
        $otp = TOTP::create($this->secret);
        $otp->setLabel('MyWishList');
        $img_src = $otp->getQrCodeUri('https://api.qrserver.com/v1/create-qr-code/?data=[DATA]&size=300x300&ecc=M', '[DATA]');
        return genererHeader("Activation de 2FA - MyWishList", ["profile.css"]) . <<<EOD
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
        EOD;
    }

    /**
     * Display the 2fa manage page
     * @return string
     */
    private function manage2FA(): string
    {
        $route_main = $this->container->router->pathFor('home');
        $route_2fa = $this->container->router->pathFor('2fa', ["action" => "disable"]);
        return genererHeader("Gestion 2FA - MyWishList", ["profile.css"]) . <<<EOD
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

    /**
     * Display the 2fa rescue codes page
     * @return string html code
     */
    private function show2FACodes(): string
    {
        $route_main = $this->container->router->pathFor('home');
        $route_login = $this->container->router->pathFor('accounts', ["action" => 'login'], ["info" => "2fa"]);
        $codes = "";
        foreach (RescueCode::whereUser($this->user->user_id)->get() as $code) {
            $codes .= "<p>$code->code</p>";
        }
        return genererHeader("Gestion 2FA - MyWishList", ["profile.css"]) . <<<EOD
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

    /**
     * Render the page
     * @param int $method method to render
     * @return string html code
     * @throws ForbiddenException
     */
    public function render(int $method): string
    {
        return match ($method) {
            Renderer::LOGIN => $this->login(),
            Renderer::LOGIN_2FA => $this->login(true),
            Renderer::REGISTER => $this->register(),
            Renderer::PROFILE => $this->showProfile(),
            Renderer::ENABLE_2FA => $this->enable2FA(),
            Renderer::MANAGE_2FA => $this->manage2FA(),
            Renderer::SHOW_2FA_CODES => $this->show2FACodes(),
            Renderer::RECOVER_2FA => $this->recover2FA(),
            Renderer::LOST_PASSWORD => $this->forgotPassword(),
            Renderer::RESET_PASSWORD => $this->resetPassword(),
            default => throw new ForbiddenException(message: $this->container->lang['exception_page_not_allowed']),
        };
    }

    /**
     * Add 2fa key to view
     * @param string $secret 2fa key
     * @return $this self
     */
    public function with2FA(string $secret): static
    {
        $this->secret = $secret;
        return $this;
    }

}
