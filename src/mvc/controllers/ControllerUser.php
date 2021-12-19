<?php
namespace mywishlist\mvc\controllers;

use Slim\Exception\{NotFoundException, MethodNotAllowedException};
use Slim\Container;
use \mywishlist\mvc\views\UserView;
use \mywishlist\mvc\Renderer;
use \mywishlist\Validator;
use \mywishlist\exceptions\{ForbiddenException, CookieNotSetException};
use mywishlist\mvc\models\{User, RescueCode};
use OTPHP\TOTP;

class ControllerUser{

    private Container $container;

    public function __construct(Container $c){
        $this->container = $c;
    }
    
    private function profile($request, $response, $args){
        switch($request->getMethod()){
            case 'GET':
                if(empty($_SESSION['LOGGED_IN']))
                    return $this->login($request, $response, $args);	
                else{
                    $user = User::find($_SESSION['USER_ID']);
                    if(empty($user)){
                        throw new ForbiddenException("Vous n'êtes pas autorisé à accéder à cette page");
                    }
                    $renderer = new UserView($this->container, $user, $request);
                    return $response->write($renderer->render(Renderer::PROFILE));
                }
                break;
            case 'POST':
                if(empty($_SESSION['LOGGED_IN']))
                    return $this->login($request, $response, $args);
                if(!empty($request->getParsedBodyParam("delete_btn")) && $request->getParsedBodyParam("delete_btn") === "delete")
                    return $this->deleteAvatar($request, $response, $args);
                $user = User::find($_SESSION['USER_ID']);
                if(empty($user))
                    throw new ForbiddenException("Vous n'êtes pas autorisé à accéder à cette page");
                if(!password_verify(filter_var($request->getParsedBodyParam("input-old-password"), FILTER_SANITIZE_STRING), $user->password))
                    return $response->withRedirect($this->container->router->pathFor('accounts',["action" => 'profile'], ["info" => "password"]));
                $toUpdate = array();
                $i = 0;
                if($request->getParsedBodyParam("input-email") !== $user->mail && filter_var($request->getParsedBodyParam("input-email"), FILTER_VALIDATE_EMAIL)){
                    $toUpdate["mail"] = filter_var($request->getParsedBodyParam("input-email"), FILTER_SANITIZE_EMAIL);
                    $i++;
                }
                if($request->getParsedBodyParam("input-first-name") !== $user->firstname){
                    $toUpdate["firstname"] = filter_var($request->getParsedBodyParam("input-first-name"), FILTER_SANITIZE_STRING);
                    $i++;
                }
                if($request->getParsedBodyParam("input-last-name") !== $user->lastname){
                    $toUpdate["lasntmae"] = filter_var($request->getParsedBodyParam("input-last-name"), FILTER_SANITIZE_STRING);
                    $i++;
                }
                if(Validator::validatePassword($request->getParsedBodyParam("input-new-password"), $request->getParsedBodyParam("input-new-password-c"))){
                    $pwd = filter_var($request->getParsedBodyParam("input-new-password"), FILTER_SANITIZE_STRING);
                    if(password_verify($pwd, $user->password))
                        return $response->withRedirect($this->container->router->pathFor('accounts',["action" => 'profile'], ["info" => "equals"]));
                    $toUpdate["password"] = password_hash($pwd, PASSWORD_DEFAULT, ['cost' => 12]);
                    return $response->withRedirect($this->container->router->pathFor('accounts',["action" => 'logout'], ["info" => "pc"]));
                }
                if($i > 0){
                    $toUpdate["updated"] = date("Y-m-d H:i:s");
                    $user->update($toUpdate);
                    return $response->withRedirect($this->container->router->pathFor('accounts',["action" => 'profile'], ["info" => "success"]));
                }
                return $response->withRedirect($this->container->router->pathFor('accounts',["action" => 'profile'], ["info" => "no-change"]));
                break;
            default:
                throw new MethodNotAllowedException($request, $response, ['GET', 'POST']);
        }
    }

    private function login($request, $response, $args){
        switch($request->getMethod()){
            case 'GET':
                if(!empty($_SESSION['LOGGED_IN']))
                    return $this->profile($request, $response, $args);
                else{
                    $renderer = new UserView($this->container, request:$request);
                    return $response->write($renderer->render(Renderer::LOGIN));
                }
                break;
            case 'POST':
                if($request->getParsedBodyParam('sendBtn') !== "OK")
                throw new ForbiddenException("Vous n'avez pas accès à cette page");
                $username = filter_var($request->getParsedBodyParam('username'), FILTER_SANITIZE_STRING);
                $password = filter_var($request->getParsedBodyParam('password'), FILTER_SANITIZE_STRING);
                $auth_2FA = filter_var($request->getParsedBodyParam('query-code'), FILTER_SANITIZE_NUMBER_INT) ?? null;
                $user = User::whereUsername($username)->first();
                if(empty($user))
                return $response->withRedirect($this->container->router->pathFor('accounts',["action" => 'login'],["info" => "nouser"]));
                if(!password_verify($password, $user->password))
                return $response->withRedirect($this->container->router->pathFor('accounts',["action" => 'login'],["info" => "password"]));
                if(!empty($user->totp_key)){
                    if(empty($auth_2FA))
                        return $response->write((new UserView($this->container, request:$request))->render(Renderer::LOGIN_2FA));
                    if(!(TOTP::create($user->totp_key))->verify($auth_2FA))
                        return $response->withRedirect($this->container->router->pathFor('accounts',["action" => 'login'],["info" => "2fanok"]));
                }
                session_regenerate_id();
                $user->update(['last_ip' => ip2long($_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']), "last_login" => date("Y-m-d H:i:s")]);
                $_SESSION['LOGGED_IN'] = true;
                $_SESSION['USER_ID'] = $user->user_id;
                $_SESSION['USER_NAME'] = $user->username;
                return $response->withRedirect($this->container->router->pathFor('home'));
            default:
                throw new MethodNotAllowedException($request, $response, ['GET', 'POST']);
        }
    }

    private function register($request, $response, $args){
        switch($request->getMethod()){
            case 'GET':
                if(!empty($_SESSION['LOGGED_IN']))
                    return $this->profile($request, $response, $args);
                else{
                    $renderer = new UserView($this->container, request:$request);
                    return $response->write($renderer->render(Renderer::REGISTER));
                }
                break;
            case 'POST':
                if($request->getParsedBodyParam('sendBtn') !== "OK")
                    throw new ForbiddenException("Vous n'avez pas accès à cette page");
                $username = filter_var($request->getParsedBodyParam('username'), FILTER_SANITIZE_STRING) ?? NULL;
                $lastname = filter_var($request->getParsedBodyParam('lastname'), FILTER_SANITIZE_STRING) ?? NULL;
                $firstname = filter_var($request->getParsedBodyParam('firstname'), FILTER_SANITIZE_STRING) ?? NULL;
                $email = filter_var($request->getParsedBodyParam('email'), FILTER_VALIDATE_EMAIL) ? (filter_var($request->getParsedBodyParam('email'), FILTER_SANITIZE_EMAIL) ?? NULL) : NULL;
                $password = filter_var($request->getParsedBodyParam('password'), FILTER_SANITIZE_STRING) ?? NULL;
                if(!Validator::validateStrings([$username, $lastname, $firstname, $email, $password]))
                    return $response->withRedirect($this->container->router->pathFor('accounts',["action" => 'register'],["info" => "invalid"]));
                if(!Validator::validatePassword($password, $password))
                    return $response->withRedirect($this->container->router->pathFor('accounts',["action" => 'register'],["info" => "password"]));
                $file = $request->getUploadedFiles()['file_img'];
                $user = new User();
                $user->username = $username;
                $user->lastname = $lastname;
                $user->firstname = $firstname;
                $user->mail = $email;
                $user->password = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
                $user->created_at = date("Y-m-d H:i:s");
                $user->save();
                if(!empty($file)){
                    $info = Validator::validateFile($this->container, $file, $user->user_id, "user"); 
                    if($info === "ok")
                        $user->update(["avatar" => $user->user_id]);
                }
                session_regenerate_id();
                $user->update(['last_ip' => ip2long($_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']), "last_login" => date("Y-m-d H:i:s")]);
                $_SESSION['LOGGED_IN'] = true;
                $_SESSION['USER_ID'] = $user->user_id;
                $_SESSION['USER_NAME'] = $user->username;
                return $response->withRedirect($this->container->router->pathFor('home'));
            default:
                throw new MethodNotAllowedException($request, $response, ['GET', 'POST']);
        }
    }

    private function logout($request, $response, $args){
        switch ($request->getMethod()) {
            case 'GET':
                session_destroy();
                //Double vérification pour éviter les problèmes de session
                unset($_SESSION);
                switch ($request->getQueryParam('info')){
                    case 'pc':
                        return $response->withRedirect($this->container->router->pathFor('accounts',["action" => 'login'], ["info" => "pc"]));
                    case '2fa':
                        return $response->withRedirect($this->container->router->pathFor('accounts',["action" => 'login'], ["info" => "2fa"]));
                    default:
                        return $response->withRedirect($this->container->router->pathFor('home'));
                }
                break;
            default:
                throw new MethodNotAllowedException($request, $response, ['GET']);
        }
    }

    private function deleteAvatar($request, $response, $args){
        $user = User::find($_SESSION['USER_ID']);
        if(empty($user->avatar))
            return $response->withRedirect($this->container->router->pathFor('accounts',["action" => 'profile'],["info" => "noavatar"]));
            $user->update(["avatar" => NULL]);
        return $response->withRedirect($this->container->router->pathFor('accounts',["action" => 'profile']));
    }

    public function show2FA($request, $response, $args){
        if(empty($_SESSION['LOGGED_IN']))
            return $response->withRedirect($this->container->router->pathFor('accounts',["action" => 'login'],["info" => "not_logged"]));    
        $user = User::find($_SESSION['USER_ID']);
        $renderer = new UserView($this->container, $user, $request);
        switch($request->getMethod()){
            case 'GET':
                if($args["action"] !== "manage")
                    throw new NotFoundException($request, $response);
                if(empty($user->totp_key)){
                    $secret_2fa = TOTP::create()->getSecret();
                    return $response->write($renderer->with2FA($secret_2fa)->render(Renderer::ENABLE_2FA));
                }
                return $response->write($renderer->render(Renderer::MANAGE_2FA));
                break;
            case 'POST':
                switch($args["action"]){
                    case "disable":
                        if($request->getParsedBodyParam('sendBtn') !== "ok")
                            throw new ForbiddenException("Vous n'avez pas accès à cette page");
                        if(empty($user->totp_key))
                            return $response->withRedirect($this->container->router->pathFor('2fa',["action" => 'manage']));
                        $user->update(["totp_key" => NULL]);
                        RescueCode::whereUser($user->user_id)->delete();
                        return $response->withRedirect($this->container->router->pathFor('accounts',["action" => 'profile'],["info" => "2fa_disabled"]));
                        break;
                    case "enable":
                        if($request->getParsedBodyParam('sendBtn') !== "ok")
                            throw new ForbiddenException("Vous n'avez pas accès à cette page");
                        if(!empty($user->totp_key))
                            return $response->withRedirect($this->container->router->pathFor('2fa',["action" => 'manage']));
                        $secret = filter_var($request->getParsedBodyParam('private_key'), FILTER_SANITIZE_STRING);
                        $code = filter_var($request->getParsedBodyParam('query-code'), FILTER_SANITIZE_NUMBER_INT);
                        if(TOTP::create($secret)->verify($code)){
                            if(RescueCode::whereUser(1)->get()->isEmpty()){
                                $user->update(["totp_key" => $secret]);
                                for ($i = 1; $i <= 6; $i++)
                                    RescueCode::create(["user" => $user->user_id, "code" => rand(10000000, 99999999)]);
                            }
                            return $response->write($renderer->render(Renderer::SHOW_2FA_CODES));
                        }
                        return $response->withRedirect($this->container->router->pathFor('accounts',["action" => 'profile'],["info" => "2fanok"]));
                        break;
                    default:
                        throw new NotFoundException($request, $response);
                }


                
                break;
            default:
                throw new MethodNotAllowedException($request, $response, ['GET', 'POST']);        
        }
    }


    public function process($request, $response, $args){
        switch($args['action']){
            case 'login':
                return $this->login($request, $response, $args);
            case 'profile':
                return $this->profile($request, $response, $args);
            case 'logout':
                return $this->logout($request, $response, $args);
            case 'register':
                return $this->register($request, $response, $args);
            default:
                throw new NotFoundException($request, $response);
        }
    }

}