<?php
session_start();

$lang = [];

require_once __DIR__ . '\..\src\vendor\autoload.php';
require_once __DIR__ . '\..\src\i18n\langs.php';

use \mywishlist\bd\Eloquent as Eloquent;
use \mywishlist\mvc\controllers\{ControllerUser, ControllerList, ControllerItem};
use \mywishlist\exceptions\ExceptionHandler;
use Slim\{App, Container};

#Container
//todo remove errors
$container = new Container(['settings' => ['displayErrorDetails' => true]]);
//end todo
$container['notFoundHandler'] = function () use ($lang) {
    return function ($request, $response) use ($lang) {
        $html = file_get_contents('..\errors\404.html');
        preg_match_all("/{#(\w|_)+#}/", $html, $matches);
        foreach ($matches[0] as $match)
            $html = str_replace($match, $lang[str_replace(["{", "#", "}"], "", $match)], $html);
        return $response->withStatus(404)->write($html);
    };
};
$container['notAllowedHandler'] = function () use ($lang) {
    return function ($request, $response) use ($lang) {
        $html = file_get_contents('..\errors\405.html');
        preg_match_all("/{#(\w|_)+#}/", $html, $matches);
        foreach ($matches[0] as $match)
            $html = str_replace($match, $lang[str_replace(["{", "#", "}"], "", $match)], $html);
        return $response->withStatus(405)->write($html);
    };
};
$container['errorHandler'] = function () use ($lang){
    return new ExceptionHandler($lang);
};
$container['items_upload_dir'] = __DIR__ . '\..\assets\img\items';
$container['users_upload_dir'] = __DIR__ . '\..\assets\img\avatars';
$container['lang'] = $lang;

#Launch
Eloquent::start('..\src\conf\conf.ini');
$app = new App($container);

#Redirection du traffic dans l'application
$app->any("/accounts/profile/2fa/{action:enable|disable|manage|recover}[/]", function ($request, $response, $args) {
    return (new ControllerUser($this))->auth2FA($request, $response, $args);
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
$app->any("/items/{id:[0-9]+}/delete[/]", function ($request, $response, $args) {
    return (new ControllerItem($this))->delete($request, $response, $args);
})->setName('items_delete_id');
$app->any("/items/{id:[0-9]+}/edit[/]", function ($request, $response, $args) {
    return (new ControllerItem($this))->edit($request, $response, $args);
})->setName('items_edit_id');
$app->post("/items/{id:[0-9]+}[/]", function ($request, $response, $args) {
    return (new ControllerItem($this))->show($request, $response, $args);
})->setName('items_show_id');

$app->get('/', function ($request, $response, $args) use ($lang) {
    $routeCreate = $this->router->pathFor('lists_create');
    $routeProfile = $this->router->pathFor('accounts', ['action' => 'profile']);
    $html = genererHeader("{$lang['home_title']} MyWishList",["style.css"]).file_get_contents(__DIR__ . '\..\src\content\sidebar.phtml');
    $phtmlVars = array(
        'iconclass' => empty($_SESSION["LOGGED_IN"]) ? "bx bx-lock-open-alt" : "bx bx-log-out",
        'user_name' => $_SESSION["USER_NAME"] ?? "{$lang['login_title']}",
        'create_list_route' => $routeCreate,
        'href' => empty($_SESSION["LOGGED_IN"]) ? $this->router->pathFor('accounts', ["action" => "login"]) : $this->router->pathFor('accounts', ["action" => "logout"]),
        'userprofile' => empty($_SESSION["LOGGED_IN"]) ? "" : <<<EOD

                    <li>
                        <a href="$routeProfile">
                            <i class='bx bxs-user'></i>
                            <span class="links_name">{$lang['home_my_profile']}</span>
                        </a>
                        <span class="tooltip">{$lang['home_my_profile']}</span>
                    </li>
        EOD
    );
    foreach ($phtmlVars as $key => $value) {
        $html = str_replace("%" . $key . "%", $value, $html);
    };
    preg_match_all("/{#(\w|_)+#}/", $html, $matches);
    foreach ($matches[0] as $match) {
        $html = str_replace($match, $lang[str_replace(["{", "#", "}"], "", $match)], $html);
    }
    $html .= <<<EOD
        <div class="main_container">
            <h3>{$lang["home_welcome"]}</h3>
            <span><a id="createBtn" content="{$lang['phtml_lists_create']}" href="$routeCreate"></a></span>
            <span><a class="disabled" content="{$lang['html_btn_list']}" id="lookBtn" href="#"></a></span>
        </div>
    </body>
    EOD;
    return $response->write($html);
})->setName('home');

$app->run();

//TODO REMOVE
if(empty($_SESSION['LOGGED_IN']))
    print_r("ATTENTION VOUS ETES EN MODE INVITE");

function genererHeader($title, $styles = [])
{
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
    foreach ($styles as $style)
        $html .= "\t<link rel='stylesheet' href='/assets/css/$style'>\n";
    return $html . "\n</head>\n<body>\n";
}