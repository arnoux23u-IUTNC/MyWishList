<?php /** @noinspection PhpUndefinedFieldInspection */

namespace mywishlist\mvc\views;

use JetBrains\PhpStorm\Pure;
use Slim\Container;
use Slim\Http\Request;
use OTPHP\TOTP;
use mywishlist\mvc\View;
use mywishlist\mvc\Renderer;
use mywishlist\exceptions\ForbiddenException;
use mywishlist\mvc\models\{User, Liste, RescueCode};

/**
 * User View
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\views
 */
class UserView extends View
{

    /**
     * @var User|null User associated with the view
     */
    private ?User $user;
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
    #[Pure] public function __construct(Container $c, User $user = NULL, Request $request = null)
    {
        $this->user = $user;
        parent::__construct($c, $request);
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
            "deleted" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['user_deleted']}</span></div>",
            default => ""
        });
        $username = $authenticator ? filter_var($this->request->getParsedBodyParam('username'), FILTER_SANITIZE_STRING) ?? NULL : NULL;
        $auth2FAReset = $authenticator ? "<a href=\"{$this->container->router->pathFor('2fa', ['action' => 'recover'], ['username' => $username])}\" class='btn btn-sm btn-default'>{$this->container->lang['login_lost_2FA']}</a>" : "";
        $password = $authenticator ? filter_var($this->request->getParsedBodyParam('password'), FILTER_SANITIZE_STRING) ?? NULL : NULL;
        $auth2FA = $authenticator ? "<div class='row fw'>\n\t\t\t\t\t\t\t\t<div class='form-group focused fw'>\n\t\t\t\t\t\t\t\t\t<label class='form-control-label' for='2fa'>{$this->container->lang['user_2fa_code']}</label>\n\t\t\t\t\t\t\t\t\t<input type='text' class='form-control form-control-alternative' autofocus id='2fa' name='query-code' required maxlength='6' minlength='6' pattern='^\d{6}$'>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>" : "";
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
                                                <input type="text" id="username" name="username" value="$username" class="form-control form-control-alternative" required>
                                            </div>
                                        </div>
                                        <div class="row fw">
                                            <div class="form-group focused fw">
                                                <label class="form-control-label" for="password">{$this->container->lang['user_password']}</label>
                                                <div class="pfield"><input type="password" id="password" name="password" value="$password" class="form-control form-control-alternative" required/><i data-associated="password" class="pwdicon far fa-eye"></i></div>
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
        </body>
        </html>
        HTML;
        return genererHeader("{$this->container->lang['login_title']} - MyWishList", ["profile.css"]) . $html;
    }

    /**
     * Display forgot password page
     * @return string html code
     */
    private function forgotPassword(): string
    {
        $popup = $this->getHeaderInfo();
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
        </body>
        </html> 
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
        $popup = $this->getHeaderInfo();
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
                                                <div class="pfield"><input type="password" id="input-new-password" minlength="14" maxlength="40" pattern="(?=.*\d)(?=.*[a-z])(?=.*[~!@#$%^&*()\-_=+[\]{};:,<>\/?|])(?=.*[A-Z]).{14,}" name="input-new-password" class="form-control form-control-alternative"><i data-associated="input-new-password" class="pwdicon far fa-eye"></i></div>
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
                                                <div class="pfield"><input type="password" id="input-new-password-c" minlength="14" maxlength="40" pattern="(?=.*\d)(?=.*[a-z])(?=.*[~!@#$%^&*()\-_=+[\]{};:,<>\/?|])(?=.*[A-Z]).{14,}" name="input-new-password-c" class="form-control form-control-alternative"><i data-associated="input-new-password-c" class="pwdicon far fa-eye"></i></div>
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
        </body>
        </html>
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
        $popup = "\n\t\t\t\t\t" . match (filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? "") {
                "nok" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['user_2fa_incorrect_code']}</span></div>",
                default => ""
            };
        return genererHeader("{$this->container->lang['user_2fa_recover']} - MyWishList", ["profile.css"]) . <<<HTML
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
                                <h1 class="text-center text-white">{$this->container->lang['user_2fa_recover']}</h1>$popup
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 flex mt--7">
                    <div class="fw">
                        <form method="post" action="$routeRecover">
                            <div class="card bg-secondary shadow">
                                <div class="card-body">
                                    <div class="pl-lg-4">
                                        <div class="row fw">
                                            <div class="form-group focused fw">
                                                <label class="form-control-label" for="username">{$this->container->lang['user_username']}</label>
                                                <input type="text" id="username" name="username" value="$username" class="form-control form-control-alternative" required>
                                            </div>
                                        </div>
                                        <div class="row fw">
                                            <div class="form-group focused fw">
                                                <label class="form-control-label" for="rescue">{$this->container->lang['user_2fa_rescue_code']}</label>
                                                <input type="text" class="form-control form-control-alternative" name="rescue" id="rescue" required maxlength="8" minlength="8" pattern="^\d{8}$">
                                            </div>
                                        </div>
                                        <div class="row fw">
                                            <button type="submit" class="btn btn-sm btn-primary" value="OK" name="sendBtn">{$this->container->lang['login_title']}</button>
                                            <a href="{$this->container->router->pathFor('accounts', ['action' => 'forgot_password'])}" class="btn btn-sm btn-danger">{$this->container->lang['login_lost_password']} ?</a>
                                            <a href="{$this->container->router->pathFor('accounts', ['action' => 'register'])}" class="btn btn-sm btn-default">{$this->container->lang['login_to_register']}</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <script src="/assets/js/password-viewer.js"></script>
        </body>
        </html>
        HTML;
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
                                                <label class="form-control-label" for="input-new-password">{$this->container->lang['user_password']}</label>
                                                <div class="pfield"><input type="password" minlength="14" maxlength="40" pattern="(?=.*\d)(?=.*[a-z])(?=.*[~!@#$%^&*()\-_=+[\]{};:,<>\/?|])(?=.*[A-Z]).{14,}" id="input-new-password" name="password" class="form-control form-control-alternative" required/><i data-associated="input-new-password" class="pwdicon far fa-eye"></i></div>
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
                                                <div class="pfield"><input type="password" minlength="14" maxlength="40" pattern="(?=.*\d)(?=.*[a-z])(?=.*[~!@#$%^&*()\-_=+[\]{};:,<>\/?|])(?=.*[A-Z]).{14,}" id="input-new-password-c" name="password-confirm" class="form-control form-control-alternative" required/><i data-associated="input-new-password-c" class="pwdicon far fa-eye"></i></div>
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
        </body>
        </html>
        HTML;
        return genererHeader("{$this->container->lang['title_register']} - MyWishList", ["profile.css"]) . $html;
    }

    /**
     * Display the profile page
     * @return string html code
     */
    protected function show(): string
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
            "delete_account_route" => $this->container->router->pathFor('accounts', ["action" => 'delete']),
            "avatar_src" => (!empty($user->avatar) && file_exists($this->container['users_img_dir'] . DIRECTORY_SEPARATOR . "$user->avatar")) ? "/assets/img/avatars/$user->avatar" : "https://www.gravatar.com/avatar/" . md5(strtolower(trim($user->mail))) . "?size=120",
            "user_username" => $user->username,
            "user_firstname" => $user->firstname,
            "user_lastname" => $user->lastname,
            "user_email" => $user->mail,
            "user_created_at" => $user->created_at,
            "user_updated_at" => $user->updated ?? "Jamais",
            "user_api_key" => !empty($user->api_key) ? "<span class='hidden'>$user->api_key</span>" : "Aucune",
            "user_lastlogged_at" => $user->last_login,
            "user_lastlogged_ip" => $user->last_ip,
            "info_msg" => match (filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? "") {
                "noavatar" => "<div class='popup warning fit'><span style='color:black;'>Vous n'avez pas d'avatar<br>Nous utilisons automatiquement un Gravatar dans ce cas.</span></div>",
                "password" => "<div class='popup warning fit'><span style='color:black;'>Mot de passe incorrect</span></div>",
                "no-change" => "<div class='popup warning fit'><span style='color:black;'>Aucun changement apport??</span></div>",
                "equals" => "<div class='popup warning fit'><span style='color:black;'>Mot de passe identique ?? l'ancien.</span></div>",
                "2faok" => "<div class='popup fit'><span style='color:black;'>2FA Activ??.</span></div>",
                "2fanok" => "<div class='popup warning fit'><span style='color:black;'>Erreur pendant l'activation de 2FA.</span></div>",
                "2fa_disabled" => "<div class='popup fit'><span style='color:black;'>2FA desactiv??</span></div>",
                "success" => "<div class='popup fit'><span style='color:black;'>Profil mis ?? jour</span></div>",
                "ok" => "<div class='popup fit'><span style='color:black;'>{$this->container->lang['image_saved']}</div>",
                "typeerr" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_type_error']}</span></div>",
                "sizeerr" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_size_error']}</span></div>",
                "writeerr" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_write_error']}</span></div>",
                "fileexist" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_exists']}</span></div>",
                "error" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_error']}</span></div>",
                "api" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['api_warning']}</span></div>",
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
        $route_2fa = $this->container->router->pathFor('2fa', ["action" => 'enable']);
        $otp = TOTP::create($this->secret);
        $otp->setLabel('MyWishList');
        $img_src = $otp->getQrCodeUri('https://api.qrserver.com/v1/create-qr-code/?data=[DATA]&size=300x300&ecc=M', '[DATA]');
        return genererHeader("{$this->container->lang['user_2fa_manage']} - MyWishList", ["profile.css"]) . <<<HTML
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
                                <h1 class="text-center text-white">{$this->container->lang['user_2fa_manage']}</h1>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 flex mt--7">
                    <div class="fw">
                        <div class="card bg-secondary shadow">
                            <div class="card-body">
                                <div class="pl-lg-4">
                                    <div class="row fw">
                                        <div class="row fw">
                                            <button disabled class="btn btn-sm btn-danger btn-danger-fr">{$this->container->lang['user_2fa_disable']}</button>
                                            <a href="#popup" class="btn btn-sm btn-primary">{$this->container->lang['user_2fa_enable']}</a>
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
                    <h2 style="padding-bottom:2vh;">{$this->container->lang['user_2fa_enabling']}</h2>
                    <a class="close" href="#">&times;</a>
                    <form method="POST" action="$route_2fa" class="content">
                        <h3>{$this->container->lang['user_2fa_enabling_message']}</h3>
                        <div class="d-flex">
                            <img alt="qr" src="$img_src"/>
                            <div style='padding-left:2vw;width:100%;display:flex;flex-direction:column;'>
                                <label class="form-control-label" for="private_key">{$this->container->lang['user_2fa_key']}</label>
                                <input type="text" readonly id="private_key" name="private_key" value="$this->secret">
                            </div>
                        </div>
                        <input type="text" autofocus name="query-code" required maxlength="6" minlength="6" pattern="^\d{6}$">
                        <button type="submit" name="sendBtn" value="ok" class="btn btn-sm btn-primary">{$this->container->lang['phtml_enable']}</button>
                    </form>
                </div>
            </div>
        </body>
        </html> 
        HTML;
    }

    /**
     * Display the 2fa enabling page
     * @return string html code
     */
    private function deleteAccount(): string
    {
        $popup = $this->getHeaderInfo();
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
                                <h1 class="text-center text-white">{$this->container->lang["user_delete_account"]}</h1>
                                $popup
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 flex mt--7">
                    <div class="fw">
                        <form method="post" enctype="multipart/form-data" action="{$this->container->router->pathFor('accounts', ['action' => 'delete'])}">
                            <div class="card bg-secondary shadow">
                                <div class="card-body">
                                    <div class="pl-lg-4">
                                        <div class="row fw">
                                            <div class="row fw">
                                                <div class="form-group focused fw">
                                                    <label class="form-control-label">{$this->container->lang['user_delete_account_confirm']}</label>
                                                </div>
                                            </div>
                                            <div class="form-group focused fw">
                                                <label class="form-control-label" for="password">{$this->container->lang['user_password']}</label>
                                                <div class="pfield"><input type="password" name="password" id="password" class="form-control form-control-alternative" autofocus required /><i data-associated="password" class="pwdicon far fa-eye"></i></div>
                                            </div>
                                            <div class="row fw">
                                                <button type="submit" class="btn btn-sm btn-danger" value="OK" name="sendBtn">{$this->container->lang['confirm']}</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </body>
        </html>
        HTML;
        return genererHeader("{$this->container->lang["user_delete_account"]} | MyWishList", ["profile.css", "toggle.css"]) . $html;
    }

    /**
     * Display the 2fa manage page
     * @return string
     */
    private function manage2FA(): string
    {
        $route_main = $this->container->router->pathFor('home');
        return genererHeader("{$this->container->lang['user_2fa_manage']} - MyWishList", ["profile.css"]) . <<<HTML
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
                    <form method="post" action="{$this->container->router->pathFor('2fa', ['action' => 'disable'])}">
                        <div class="row">
                            <div style="width:100%;">
                                <div class="card bg-secondary shadow">
                                    <div class="card-header bg-white border-0">
                                    <h3 class="mb-0">{$this->container->lang['user_2fa_manage']}</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="pl-lg-4">
                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <div class="popup">
                                                        <span>{$this->container->lang['user_2fa_enabled']}</span>
                                                    </div>
                                                    <div>
                                                        <button type="submit" name="sendBtn" value="ok" class="btn btn-sm btn-danger text-white">{$this->container->lang['user_2fa_disable']}</button>
                                                        <button disabled class="btn btn-sm btn-default">{$this->container->lang['user_2fa_enable']}</button>
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
        </body>
        </html>
        HTML;
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
        return genererHeader("{$this->container->lang['phtml_rescue_code']} - MyWishList", ["profile.css"]) . <<<HTML
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
                            <h3 class="mb-0">{$this->container->lang['user_2fa_rescue_codes']}</h3>
                        </div>
                        <div class="card-body">
                            <div class="pl-lg-4">
                            <div class="row">
                                <div class="col-lg-4">
                                <div class="code">
                                    $codes
                                </div>
                                <div>
                                    <a href="$route_login" class="btn btn-sm btn-default">{$this->container->lang['phtml_noted']}</a>
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
        </body>
        </html>
        HTML;
    }

    /**
     * Display the home page
     * @return string html code
     */
    private function showHome(): string
    {
        $routeCreate = $this->container->router->pathFor('lists_create');
        $html = genererHeader("{$this->container->lang['home_title']} - MyWishList", ["style.css", "lang.css"]) . $this->sidebar();
        $popup = match (filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? "") {
            "deleted" => "\n\t\t<div class='popup warning fit'><span style='color:black; font-size:.7rem;'>{$this->container->lang['phtml_list_deleted']}</span></div>",
            default => ""
        };
        $html .= <<<HTML
        <div class="main_container">
                <h3>{$this->container->lang["home_welcome"]}</h3>$popup
                <span><a id="createBtn" content="{$this->container->lang['phtml_lists_create']}" href="$routeCreate"></a></span>
                <span><a content="{$this->container->lang['html_btn_list']}" id="lookBtn" href="{$this->container->router->pathFor('lists_home')}"></a></span>
            </div>
        </body>
        </html>
        HTML;
        return $html;
    }

    /**
     * Display the creators page
     * @return string html code
     * @throws ForbiddenException error while render
     */
    private function showCreators(): string
    {
        $users = "";
        $html = genererHeader("{$this->container->lang['phtml_creators']} - MyWishList", ["profile.css", "style.css", "lang.css"]) . $this->sidebar();
        foreach (User::all() as $user) {
            if (Liste::where('user_id', 'LIKE', $user->user_id)->where('is_public', 'LIKE', 1)->count() > 0)
                $users .= (new UserView($this->container, $user, $this->request))->render(Renderer::SHOW_FOR_MENU);
        }
        $html .= <<<HTML
            <div class="main_container">
                <h3 class="text-white">{$this->container->lang["phtml_creators"]}</h3>
                <div class='lists'>$users
                </div>
            </div>
        </body>
        </html>
        HTML;
        return $html;
    }

    /**
     * Display the public lists page
     * @return string html code
     * @throws ForbiddenException error while render
     */
    private function showLists(): string
    {
        $html = genererHeader("{$this->container->lang['phtml_my_lists']} - MyWishList", ["profile.css", "style.css", "lang.css", "search.css"]) . $this->sidebar();
        $public_lists = "";
        $my_lists = "<h4>Aucune liste</h4>";
        if(!empty($_SESSION["LOGGED_IN"])){
            $my_lists = "";
            foreach (Liste::where('user_id', 'LIKE', $_SESSION['USER_ID'])->get() as $liste) {
                $my_lists .= (new ListView($this->container, $liste, $this->request))->render(Renderer::SHOW_FOR_MENU);
            }
        }
        if ($this->request->getQueryParam('search', '') != '' || $this->request->getQueryParam('exp', '') != '') {
            foreach (Liste::where('is_public', 'LIKE', '1')->orderBy('expiration')->get() as $list) {
                if (!$list->isExpired() && $list->isPublished() && (($list->expiration ?? date('9999-99-99')) > filter_var($this->request->getQueryParam('exp'))) && str_contains(strtolower($list->getUserNameAttribute()) ?? "", strtolower(filter_var($this->request->getQueryParam('search', ''), FILTER_SANITIZE_STRING))))
                    $public_lists .= (new ListView($this->container, $list, $this->request))->render(Renderer::SHOW_FOR_MENU);
            }
        } else {
            foreach (Liste::where('is_public', 'LIKE', '1')->orderBy('expiration')->get() as $list) {
                if (!$list->isExpired() && $list->isPublished())
                    $public_lists .= (new ListView($this->container, $list, $this->request))->render(Renderer::SHOW_FOR_MENU);
            }
        }
        $search = filter_var($this->request->getQueryParam('search'), FILTER_SANITIZE_STRING);
        $exp = filter_var($this->request->getQueryParam('exp'), FILTER_SANITIZE_STRING);
        $html .= <<<HTML
            <div class="main_container">
                <h3 class="text-white">{$this->container->lang["phtml_public_lists"]}</h3>
                <div class="fw flex">
                    <div id="enable" class="row flex flex-row col-4">
                        <div class="search-box">
                            <input type="text" value="$search" placeholder="{$this->container->lang["phtml_search_by_author"]}">
                            <div class="search-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="cancel-icon">
                                <i class="fas fa-times"></i>
                            </div>
                        </div>
                        <label class="form-control-label-2" for="expiration">{$this->container->lang['date']}</label>
                        <input type="date" id="expiration" value="$exp" class="form-control form-control-alternative search-date ml-2"/>
                    </div>
                </div>
                <div class='lists'>$public_lists
                </div>
                <h3 class="mt-4 text-white">{$this->container->lang["phtml_my_lists"]}</h3>
                <div class="fw flex">
                </div>
                <div class='lists'>$my_lists
                </div>
            </div>
            <script src="/assets/js/search.js"></script>
        </body>
        </html>
        HTML;
        return $html;
    }

    /**
     * Display the sidebar
     * @return string html code
     */
    private function sidebar(): string
    {
        $html = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'sidebar.phtml');
        $phtmlVars = array(
            'iconclass' => empty($_SESSION["LOGGED_IN"]) ? "bx bx-lock-open-alt" : "bx bx-log-out",
            'user_name' => $_SESSION["USER_NAME"] ?? "{$this->container->lang['login_title']}",
            'main_route' => $this->container->router->pathFor('home'),
            'my_lists_route' => $this->container->router->pathFor('lists_home'),
            'createurs_route' => $this->container->router->pathFor('createurs'),
            'create_list_route' => $this->container->router->pathFor('lists_create'),
            'flag_img' => "<img class='selected' alt='" . strtolower($_SESSION["lang"]) . "-flag' src='/assets/img/flags/flag-" . strtolower($_SESSION["lang"]) . ".png'>",
            'href' => empty($_SESSION["LOGGED_IN"]) ? $this->container->router->pathFor('accounts', ["action" => "login"]) : $this->container->router->pathFor('accounts', ["action" => "logout"]),
            'userprofile' => empty($_SESSION["LOGGED_IN"]) ? "" : <<<HTML

                        <li>
                            <a href="{$this->container->router->pathFor('accounts', ['action' => 'profile'])}">
                                <i class='bx bxs-user'></i>
                                <span class="links_name">{$this->container->lang['home_my_profile']}</span>
                            </a>
                            <span class="tooltip">{$this->container->lang['home_my_profile']}</span>
                        </li>
            HTML
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
     * Display the profile of user for include in a list
     * @return string html code
     */
    private function showInList(): string
    {
        $avatar = (!empty($this->user->avatar) && file_exists($this->container['users_img_dir'] . DIRECTORY_SEPARATOR . $this->user->avatar)) ? "/assets/img/avatars/{$this->user->avatar}" : "https://www.gravatar.com/avatar/" . md5(strtolower(trim($this->user->mail))) . "?size=120";
        return "\n\t\t\t<div class='mb-2'>\n\t\t\t\t<div class='list form-control mw form-control-alternative flex flex-row'>\n\t\t\t\t\t<span class='mw text-white form-control-label'>{$this->user->name()}</span>\n\t\t\t\t\t<img alt='profilepicture' class='rounded-circle minimified' src='$avatar'/>\n\t\t\t\t</div>\n\t\t\t</div>";
    }

    /**
     * Render the page
     * @param int $method method to render
     * @return string html code
     * @throws ForbiddenException
     */
    public function render(int $method, int $access_level = Renderer::OTHER_MODE): string
    {
        return match ($method) {
            Renderer::HOME_HOME => $this->showHome(),
            Renderer::HOME_LISTS => $this->showLists(),
            Renderer::HOME_CREATORS => $this->showCreators(),
            Renderer::LOGIN => $this->login(),
            Renderer::LOGIN_2FA => $this->login(true),
            Renderer::REGISTER => $this->register(),
            Renderer::ENABLE_2FA => $this->enable2FA(),
            Renderer::MANAGE_2FA => $this->manage2FA(),
            Renderer::SHOW_2FA_CODES => $this->show2FACodes(),
            Renderer::SHOW_FOR_MENU => $this->showInList(),
            Renderer::RECOVER_2FA => $this->recover2FA(),
            Renderer::LOST_PASSWORD => $this->forgotPassword(),
            Renderer::RESET_PASSWORD => $this->resetPassword(),
            Renderer::DELETE_ACCOUNT_CONFIRM => $this->deleteAccount(),
            default => parent::render($method, $access_level),
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

    /**
     * Get the info parameter in the request
     * @return string info parameter
     */
    private function getHeaderInfo(): string
    {
        return match (filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? "") {
            "nouser" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['user_user_notfound']}</span></div>",
            "sent" => "<div class='popup fit'><span style='color:black;'>{$this->container->lang['email_sent']}</span></div>",
            "not_sent" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['email_not_sent']}</span></div>",
            "already" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['reset_already_asked']}</span></div>",
            "invalid" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['token_invalid']}</span></div>",
            'password' => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['user_password_incorrect']}</span></div>",
            "emailnovalid" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['email_not_valid']}</span></div>",
            default => ""
        };
    }

    /**
     * {@inheritDoc}
     */
    public function encode(int $access_level): string
    {
        return "";
    }

    /**
     * [NEXISTS] Edit user function
     * @return void
     * @throws ForbiddenException because not exists (cannot modify a user separately)
     */
    protected function edit()
    {
        throw new ForbiddenException(message: $this->container->lang['error_forbidden']);
    }
}
