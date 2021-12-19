<?php
session_start();
require_once __DIR__.'\..\src\vendor\autoload.php';

use \mywishlist\bd\Eloquent as Eloquent;
use \mywishlist\mvc\controllers\{ControllerUser, ControllerList, ControllerItem};
use \mywishlist\exceptions\{ExceptionHandler, ForbiddenException, CookieNotSetException};
use Slim\{App, Container};

#Container
//todo remove errors
$container = new Container(['settings' => ['displayErrorDetails' => true]]);
$container['notFoundHandler'] = function () {
    return function ($request, $response) {
        return $response->withStatus(404)->write(file_get_contents('..\errors\404.html'));
    };
};
$container['notAllowedHandler'] = function () {
    return function ($request, $response) {
        return $response->withStatus(405)->write(file_get_contents('..\errors\405.html'));
    };
};
$container['errorHandler'] = function () {
    return new ExceptionHandler();
};
$container['items_upload_dir'] = __DIR__.'\..\assets\img\items';
$container['users_upload_dir'] = __DIR__.'\..\assets\img\avatars';

#Launch
Eloquent::start('..\src\conf\conf.ini');
$app = new App($container);

#Modifications temporaires de variables
$app->get('/participant', function ($request, $response, $args) {
    setcookie("typeUser", 'participant', time()+3600*24);
    return $response->write("<h1>Participant</h1><a href='/'>Retour</a>");
});
$app->get('/createur', function ($request, $response, $args) {
    setcookie("typeUser", 'createur', time()+3600*24);
    return $response->write("<h1>Createur</h1> <a href='/'>Retour</a>");
});

#Redirection du traffic dans l'application
$app->any("/accounts/profile/2fa/{action:enable|disable|manage}[/]", function ($request, $response, $args) {
    return (new ControllerUser($this))->show2FA($request, $response, $args);
})->setName('2fa');
$app->any("/accounts/{action:login|profile|logout|register}[/]", function ($request, $response, $args) {
    return (new ControllerUser($this))->process($request, $response, $args);
})->setName('accounts');
$app->any("/lists/{id:[0-9]+}/edit/items[/]", function ($request, $response, $args) {
    return (new ControllerList($this))->addItem($request, $response, $args);
})->setName('lists_edit_items_id');
$app->any("/lists/{id:[0-9]+}/edit[/]", function ($request, $response, $args) {
    return (new ControllerList($this))->edit($request, $response, $args);
})->setName('lists_edit_id');
$app->any("/lists/new[/]", function ($request, $response, $args) {
    return (new ControllerList($this))->create($request, $response, $args);
})->setName('lists_create');
$app->get("/lists/{id:[0-9]+}[/]", function ($request, $response, $args) {
    return (new ControllerList($this))->show($request, $response, $args);
})->setName('lists_show_id');

/*$app->any("/items/new[/]", function ($request, $response, $args) {
    return (new ControllerItem($this))->create($request, $response, $args);
})->setName('items_list_add');*/
$app->any("/items/{id:[0-9]+}/delete[/]", function ($request, $response, $args) {
    return (new ControllerItem($this))->delete($request, $response, $args);
})->setName('items_delete_id');
$app->any("/items/{id:[0-9]+}/edit[/]", function ($request, $response, $args) {
    return (new ControllerItem($this))->edit($request, $response, $args);
})->setName('items_edit_id');
$app->post("/items/{id:[0-9]+}[/]", function ($request, $response, $args) {
    return (new ControllerItem($this))->show($request, $response, $args);
})->setName('items_show_id');

$app->get('/', function ($request, $response, $args){
    //TODO REMOVE
    if(empty($request->getCookieParam('typeUser'))){
        throw new CookieNotSetException();
    }
    $routeCreate = $this->router->pathFor('lists_create');
    $html = genererHeader("MyWishList",["style.css"]).file_get_contents(__DIR__.'\..\src\content\sidebar.phtml');
    $routeProfile = $this->router->pathFor('accounts', ['action' => 'profile']);
    $phtmlVars = array(
        'user_name' => $_SESSION["USER_NAME"] ?? "Se connecter",
        'iconclass' => empty($_SESSION["LOGGED_IN"]) ? "bx bx-lock-open-alt" : "bx bx-log-out",
        'href' => empty($_SESSION["LOGGED_IN"]) ? $this->router->pathFor('accounts',["action" => "login"]) : $this->router->pathFor('accounts',["action" => "logout"]),
        'userprofile' => empty($_SESSION["LOGGED_IN"]) ? "" : <<<EOD

                    <li>
                        <a href="$routeProfile">
                            <i class='bx bxs-user'></i>
                            <span class="links_name">Mon Profil</span>
                        </a>
                        <span class="tooltip">Mon Profil</span>
                    </li>
        EOD
    );
    foreach ($phtmlVars as $key => $value) {
        $html = str_replace("%".$key."%", $value, $html);
    };  
    $html.= <<<EOD
        <div class="main_container">
            <h3>Bienvenue sur MyWishList</h3>
            <span><a id="createBtn" href="$routeCreate"></a></span>
            <span><a class="disabled" id="lookBtn" href="#"></a></span>
        </div>
    </body>
    EOD;
    return $response->write($html);
})->setName('home');

$app->run();

function genererHeader($title, $styles = []) {
    $html = <<<EOD
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset='UTF-8'>
        <link rel="icon" href="/assets/img/icons/favicon.ico">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
        <link href="https://fonts.googleapis.com/css?family=Poiret+One" rel="stylesheet">
        <link href="/assets/css/forall.css" rel="stylesheet">
        <link href="/assets/css/navbar.css" rel="stylesheet">
        <title>$title</title>
    EOD;
    foreach($styles as $style)
        $html .= "\t<link rel='stylesheet' href='/assets/css/$style'>\n";
    return $html."\n</head>\n<body>\n";   
}