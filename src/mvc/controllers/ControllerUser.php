<?php

namespace mywishlist\mvc\controllers;

use Slim\Exception\{NotFoundException, MethodNotAllowedException};
use Slim\Container;
use Slim\Http\{Response, Request};
use \mywishlist\mvc\views\UserView;
use \mywishlist\mvc\Renderer;
use \mywishlist\Validator;
use \mywishlist\exceptions\ForbiddenException;
use mywishlist\mvc\models\{User, RescueCode};
use OTPHP\TOTP;

class ControllerUser
{

    private Container $container;
    private ?User $user;
    private UserView $renderer;
    private Request $request;
    private Response $response;
    private array $args;

    public function __construct(Container $c, Request $request, Response $response, array $args)
    {
        $this->container = $c;
        $this->user = User::find($_SESSION['USER_ID'] ?? -1) ?? NULL;
        $this->renderer = new UserView($this->container, $this->user, $request);
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;
    }

    private function profile()
    {
        //Si l'utilisateur n'est pas connecté, on l'envoie vers la page de connexion
        if (empty($_SESSION['LOGGED_IN']))
            return $this->login();
        //Si l'utilisateur n'existe pas, on affiche une erreur
        if(empty($this->user))
            throw new ForbiddenException("Vous n'êtes pas autorisé à accéder à cette page");
        //Selon le type de requete, on affiche ou on modifie le profil
        switch ($this->request->getMethod()) {
            case 'GET':
                if (empty($this->user))
                    throw new ForbiddenException(message: $this->container->lang['exception_page_not_allowed']);
                return $this->response->write($this->renderer->render(Renderer::PROFILE));
                break;
            case 'POST':
                //Si l'utilisateur donne l'ordre de supprimer l'avatar, on le supprime
                if (!empty($this->request->getParsedBodyParam("delete_btn")) && $this->request->getParsedBodyParam("delete_btn") === "delete")
                    return $this->deleteAvatar();
                //Sinon, on modifier l'utilisateur
                $file = $this->request->getUploadedFiles()['avatarinput'];
                //S'il y a un fichier uploadé, on le traite
                if(!empty($file)){
                    $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
                    $finfo = [$this->user->user_id,strtolower($extension)];
                    $oldfile = $this->container['users_upload_dir'].DIRECTORY_SEPARATOR.$this->user->avatar;
                    $info = Validator::validateFile($this->container, $file, $finfo, "user");
                    if($info === "ok"){
                        if($this->user->avatar !== $finfo[0].".".$finfo[1])
                            unlink($oldfile);
                        $this->user->update(['avatar' => $finfo[0].".".$finfo[1]]);
                    }
                    unset($_FILES);
                    return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'profile'], ['info' => $info]));
                }
                //Si l'ancien mot de passe ne correpond pas, on renvoie une erreur
                if(!password_verify(filter_var($this->request->getParsedBodyParam("input-old-password"), FILTER_SANITIZE_STRING), $this->user->password))
                    return $this->response->withRedirect($this->container->router->pathFor('accounts',["action" => 'profile'], ["info" => "password"]));
                //On construit un tableau dynamique avec les données à modifier et un itérateur
                $toUpdate = array();
                $i = 0;
                //Pour chaque élément du formulaire, on vérifie qu'il soit différent de celui déjà attribué à l'utilisateur. Si oui, on l'insére dans le tableau.
                if ($this->request->getParsedBodyParam("input-email") !== $this->user->mail && filter_var($this->request->getParsedBodyParam("input-email"), FILTER_VALIDATE_EMAIL)) {
                    $toUpdate["mail"] = filter_var($this->request->getParsedBodyParam("input-email"), FILTER_SANITIZE_EMAIL);
                    $i++;
                }
                if ($this->request->getParsedBodyParam("input-first-name") !== $this->user->firstname) {
                    $toUpdate["firstname"] = filter_var($this->request->getParsedBodyParam("input-first-name"), FILTER_SANITIZE_STRING);
                    $i++;
                }
                if ($this->request->getParsedBodyParam("input-last-name") !== $this->user->lastname) {
                    $toUpdate["lastname"] = filter_var($this->request->getParsedBodyParam("input-last-name"), FILTER_SANITIZE_STRING);
                    $i++;
                }
                //On verifie que le mot de passe soit valide
                if (Validator::validatePassword($this->request->getParsedBodyParam("input-new-password"), $this->request->getParsedBodyParam("input-new-password-c"))) {
                    $pwd = filter_var($this->request->getParsedBodyParam("input-new-password"), FILTER_SANITIZE_STRING);
                    //Si le nouveau mot de passe est le même que l'ancien, on affiche une erreur
                    if (password_verify($pwd, $this->user->password))
                        return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'profile'], ["info" => "equals"]));
                    $toUpdate["password"] = password_hash($pwd, PASSWORD_DEFAULT, ['cost' => 12]);
                    $i++;
                }
                //Si le tableau n'est pas vide, on effectue la MAJ sur l'utilisateur
                if ($i > 0) {
                    $toUpdate["updated"] = date("Y-m-d H:i:s");
                    $this->user->update($toUpdate);
                    //Si l'utilisateur a modifié son mot de passe, on le déconnecte
                    if(!empty($toUpdate["password"]))
                        return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'logout'], ["info" => "pc"]));
                    return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'profile'], ["info" => "success"]));
                }
                //Sinon, on renvoie une erreur
                return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'profile'], ["info" => "no-change"]));
                break;
            default:
                throw new MethodNotAllowedException($this->request, $this->response, ['GET', 'POST']);
        }
    }

    private function login()
    {
        //Si la requete est de type GET, on affiche la page de connexion, sinon, on verifie les valeurs passées
        switch ($this->request->getMethod()) {
            case 'GET':
                //Si l'utilisateur est deja connecté, on le redirige vers son profil
                if (!empty($_SESSION['LOGGED_IN']))
                    return $this->profile();
                else 
                    return $this->response->write($this->renderer->render(Renderer::LOGIN));
                break;
            case 'POST':
                //On verifie une variable venant du formulaire
                if ($this->request->getParsedBodyParam('sendBtn') !== "OK")
                    throw new ForbiddenException(message: $this->container->lang['exception_page_not_allowed']);
                //On recupere les informations passées
                $username = filter_var($this->request->getParsedBodyParam('username'), FILTER_SANITIZE_STRING);
                $password = filter_var($this->request->getParsedBodyParam('password'), FILTER_SANITIZE_STRING);
                $auth_2FA = filter_var($this->request->getParsedBodyParam('query-code'), FILTER_SANITIZE_NUMBER_INT) ?? NULL;
                //On teste le nom d'utilisateur. S'il est incorrect, on renvoie une erreur
                $user = User::whereUsername($username)->first();
                if (empty($user))
                    return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'login'], ["info" => "nouser"]));
                //Si le mot de passe ne correspond pas, on renvoie une erreur
                if (!password_verify($password, $user->password))
                    return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'login'], ["info" => "password"]));
                //Enfin, si l'utilisateur a activé la double authentification, on verifie le code. S'il est incorrect, on renvoie une erreur
                if (!empty($user->totp_key)) {
                    if (empty($auth_2FA))
                        return $this->response->write((new UserView($this->container, request: $this->request))->render(Renderer::LOGIN_2FA));
                    if (!(TOTP::create($user->totp_key))->verify($auth_2FA))
                        return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'login'], ["info" => "2fanok"]));
                }
                //Regeneration du token de session et remplissage du tableau de session
                session_regenerate_id();
                //On mets a jour certaines variables de l'utilisateur
                $user->update(['last_ip' => ip2long($_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']), "last_login" => date("Y-m-d H:i:s")]);
                $_SESSION['LOGGED_IN'] = true;
                $_SESSION['USER_ID'] = $user->user_id;
                $_SESSION['USER_NAME'] = $user->username;
                return $this->response->withRedirect($this->container->router->pathFor('home'));
            default:
                throw new MethodNotAllowedException($this->request, $this->response, ['GET', 'POST']);
        }
    }

    private function register()
    {
        //Si l'utilisateur est connecté, on le redirige vers son profil.
        if (!empty($_SESSION['LOGGED_IN']))
            return $this->profile();
        switch ($this->request->getMethod()) {
            case 'GET':
                return $this->response->write($this->renderer->render(Renderer::REGISTER));
                break;
            case 'POST':
                //On verifie une variable venant du formulaire
                if ($this->request->getParsedBodyParam('sendBtn') !== "OK")
                    throw new ForbiddenException(message: $this->container->lang['exception_page_not_allowed']);
                //On recupere les variables du formulaire
                $username = filter_var($this->request->getParsedBodyParam('username'), FILTER_SANITIZE_STRING) ?? NULL;
                $lastname = filter_var($this->request->getParsedBodyParam('lastname'), FILTER_SANITIZE_STRING) ?? NULL;
                $firstname = filter_var($this->request->getParsedBodyParam('firstname'), FILTER_SANITIZE_STRING) ?? NULL;
                $email = filter_var($this->request->getParsedBodyParam('email'), FILTER_VALIDATE_EMAIL) ? (filter_var($this->request->getParsedBodyParam('email'), FILTER_SANITIZE_EMAIL) ?? NULL) : NULL;
                $password = filter_var($this->request->getParsedBodyParam('password'), FILTER_SANITIZE_STRING) ?? NULL;
                $password_confirm = filter_var($this->request->getParsedBodyParam('password-confirm'), FILTER_SANITIZE_STRING) ?? NULL;
                //On valide les informations de l'utilisateur grace à un algorithme
                if (!Validator::validateStrings([$username, $lastname, $firstname, $email, $password, $password_confirm]))
                    return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'register'], ["info" => "invalid"]));
                if (!Validator::validatePassword($password, $password_confirm))
                    return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'register'], ["info" => "password"]));
                //Création d'un utilisateur
                $user = new User();
                //On force l'ID de l'utilisateur à NULL pour éviter les problèmes de clonage
                $user->user_id = NULL;
                //Si un fichier est uploadé, on le traite
                $file = $this->request->getUploadedFiles()['file_img'];
                if (!empty($file->getClientFilename())) {
                    $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
                    $finfo = [$this->user->user_id,strtolower($extension)];
                    $info = Validator::validateFile($this->container, $file, $finfo, "user");
                    //Si le fichier est validé
                    if ($info === "ok")
                        $user->avatar = $finfo[0].".".$finfo[1];
                }
                //On vide le tableau FILES pour eviter les abus
                unset($_FILES);
                $user->username = $username;
                $user->lastname = $lastname;
                $user->firstname = $firstname;
                $user->mail = $email;
                $user->password = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
                $user->created_at = date("Y-m-d H:i:s");
                //Sauvegarde dans la base
                $user->save();
                session_regenerate_id();
                $user->update(['last_ip' => ip2long($_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']), "last_login" => date("Y-m-d H:i:s")]);
                //Remplissage du tableau de session
                $_SESSION['LOGGED_IN'] = true;
                $_SESSION['USER_ID'] = $user->user_id;
                $_SESSION['USER_NAME'] = $user->username;
                return $this->response->withRedirect($this->container->router->pathFor('home'));
            default:
                throw new MethodNotAllowedException($this->request, $this->response, ['GET', 'POST']);
        }
    }

    private function logout()
    {
        //Si l'utilisateur n'est pas connecté, on le renvoie vers la page d'accueil
        if (empty($_SESSION['LOGGED_IN']))
            return $this->response->withRedirect($this->container->router->pathFor('home'));
        //On deconnecte l'utilisateur
        User::logout();
        //Selon le cas de figure (déconnexion volontaire, changement du mdp, 2fa, ..), on renvoie vers une page différente
        switch ($this->request->getQueryParam('info')) {
            case 'pc':
                return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'login'], ["info" => "pc"]));
            case '2fa':
                return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'login'], ["info" => "2fa"]));
            default:
                return $this->response->withRedirect($this->container->router->pathFor('home'));
        }
    }

    private function deleteAvatar()
    {
        //S'il n'a pas d'avatar, on l'informe
        if (empty($this->user->avatar))
            return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'profile'], ["info" => "noavatar"]));
        //On supprime l'image precedente et on insere NULL dans la DB
        unlink($this->container['users_upload_dir'].DIRECTORY_SEPARATOR  .$this->user->avatar);
        $this->user->update(["avatar" => NULL]);
        return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'profile']));
    }

    public function auth2FA()
    {
        //Si l'utilisateur n'est pas connecté, on lui demande de se connecter
        if (empty($_SESSION['LOGGED_IN']))
            return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'login'], ["info" => "not_logged"]));
        //Si l'utilisateur n'existe pas, on renvoie une erreur
        if (empty($this->user))
            throw new ForbiddenException(message: $this->container->lang['exception_page_not_allowed']);
        //Si l'utilisateur entre dans le mode "RECOVER" (récupération 2FA)
        if ($this->args["action"] == "recover") {
            switch ($this->request->getMethod()) {
                case 'GET':
                    //Affichage de la page de récupération
                    return $this->response->write((new UserView($this->container, request: $this->request))->render(Renderer::RECOVER_2FA));
                case 'POST':
                    if ($this->request->getParsedBodyParam('sendBtn') !== "OK")
                        throw new ForbiddenException(message: $this->container->lang['exception_page_not_allowed']);
                    //Si l'utilisateur fourni le bon code de secours, on supprime 2FA sur son compte
                    $rescue = filter_var($this->request->getParsedBodyParam('rescue'), FILTER_SANITIZE_NUMBER_INT);
                    $user = User::whereUsername(filter_var($this->request->getParsedBodyParam('username'), FILTER_SANITIZE_STRING))->first();
                    $rescueObj = RescueCode::whereUserAndCode($user->user_id, $rescue)->first();
                    if (empty($rescueObj))
                        return $this->response->withRedirect($this->container->router->pathFor('2fa', ["action" => 'recover'], ["info" => "nok", "username" => $user->username]));
                    $rescueObj->delete();
                    $user->remove2FA();
                    return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'login'], ["info" => "2farec"]));
                    break;
                default:
                    throw new MethodNotAllowedException($this->request, $this->response, ['GET', 'POST']);
            }
        }
        //Si l'utilisateur ne précise rien
        else {
            //S'il n'est pas connecté, on le redirige vers une autre page
            if (empty($_SESSION['LOGGED_IN']))
                return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'login'], ["info" => "not_logged"]));
            switch ($this->request->getMethod()) {
                case 'GET':
                    //Si la commande n'est autre que "MANAGE", on renvoie une erreur
                    if ($this->args["action"] !== "manage")
                        throw new NotFoundException($this->request, $this->response);
                    //Si l'utilisateur n'a pas de 2FA, on le renvoie vers la page d'activation
                    if (empty($this->user->totp_key)) {
                        //Création de la clé secrète
                        $secret_2fa = TOTP::create()->getSecret();
                        return $this->response->write($this->renderer->with2FA($secret_2fa)->render(Renderer::ENABLE_2FA));
                    }
                    //Sinon, on le renvoie vers la page de gestion
                    return $this->response->write($this->renderer->render(Renderer::MANAGE_2FA));
                    break;
                case 'POST':
                    //On vérifie une valeur venant du formulaire
                    if ($this->request->getParsedBodyParam('sendBtn') !== "ok")
                        throw new ForbiddenException(message: $this->container->lang['exception_page_not_allowed']);
                    switch ($this->args["action"]) {
                        //Désactivation du 2FA
                        case "disable":
                            //Si l'utilisateur n'a pas de 2FA, on le renvoie vers la page d'activation
                            if (empty($this->user->totp_key))
                                return $this->response->withRedirect($this->container->router->pathFor('2fa', ["action" => 'manage']));
                            //On désaactive le 2FA
                            $this->user->remove2FA();
                            return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'profile'], ["info" => "2fa_disabled"]));
                            break;
                        //Activation du 2FA
                        case "enable":
                            //Si l'utilisateur a déjà un 2FA, on le renvoie vers la page de gestion
                            if (!empty($this->user->totp_key))
                                return $this->response->withRedirect($this->container->router->pathFor('2fa', ["action" => 'manage']));
                            //On vérifie le code entré par l'utilisateur
                            $secret = filter_var($this->request->getParsedBodyParam('private_key'), FILTER_SANITIZE_STRING);
                            $code = filter_var($this->request->getParsedBodyParam('query-code'), FILTER_SANITIZE_NUMBER_INT);
                            if (TOTP::create($secret)->verify($code)) {
                                //Si le code est correct, on crée des codes de récupération
                                if (RescueCode::whereUser(1)->get()->isEmpty())
                                    $this->user->create2FA($secret);
                                //On déconnecte l'utilisateur mais en le redirigeant vers la page d'affichage des codes
                                User::logout();
                                return $this->response->write($this->renderer->render(Renderer::SHOW_2FA_CODES));
                            }
                            return $this->response->withRedirect($this->container->router->pathFor('accounts', ["action" => 'profile'], ["info" => "2fanok"]));
                            break;
                        default:
                            throw new NotFoundException($this->request, $this->response);
                    }
                    break;
                default:
                    throw new MethodNotAllowedException($this->request, $this->response, ['GET', 'POST']);
            }
        }
    }


    public function process()
    {
        switch ($this->args['action']) {
            case 'login':
                return $this->login();
            case 'profile':
                return $this->profile();
            case 'logout':
                return $this->logout();
            case 'register':
                return $this->register();
            default:
                throw new NotFoundException($this->request, $this->response);
        }
    }

}