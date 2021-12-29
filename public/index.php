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
    return (new ControllerUser($this, $request, $response, $args))->auth2FA();
})->setName('2fa');
$app->any("/accounts/{action:login|profile|logout|register}[/]", function ($request, $response, $args) {
    return (new ControllerUser($this, $request, $response, $args))->process();
})->setName('accounts');
$app->any("/lists/{id:[0-9]+}/edit/items[/]", function ($request, $response, $args) {
    return (new ControllerList($this,$request, $response, $args))->addItem();
})->setName('lists_edit_items_id');
$app->any("/lists/{id:[0-9]+}/edit[/]", function ($request, $response, $args) {
    return (new ControllerList($this,$request, $response, $args))->edit();
})->setName('lists_edit_id');
$app->any("/lists/{id:[0-9]+}[/]", function ($request, $response, $args) {
    return (new ControllerList($this,$request, $response, $args))->show();
})->setName('lists_show_id');
$app->any("/lists/new[/]", function ($request, $response, $args) {
    return (new ControllerList($this,$request, $response, $args))->create();
})->setName('lists_create');
$app->any("/lists[/]", function ($request, $response, $args) {
    //TODO return (new ControllerUser($this,$request, $response, $args))->create();
})->setName('lists_home');
$app->any("/items/{id:[0-9]+}/delete[/]", function ($request, $response, $args) {
    return (new ControllerItem($this,$request, $response, $args))->delete();
})->setName('items_delete_id');
$app->any("/items/{id:[0-9]+}/edit[/]", function ($request, $response, $args) {
    return (new ControllerItem($this,$request, $response, $args))->edit();
})->setName('items_edit_id');
$app->any("/items/{id:[0-9]+}[/]", function ($request, $response, $args) {
    return (new ControllerItem($this,$request, $response, $args))->show();
})->setName('items_show_id');

$app->get('/', function ($request, $response, $args) use ($lang) {
    $routeCreate = $this->router->pathFor('lists_create');
    $routeProfile = $this->router->pathFor('accounts', ['action' => 'profile']);
    $html = genererHeader("{$lang['home_title']} MyWishList",["style.css","lang.css"]).file_get_contents(__DIR__ . '\..\src\content\sidebar.phtml');
    $phtmlVars = array(
        'iconclass' => empty($_SESSION["LOGGED_IN"]) ? "bx bx-lock-open-alt" : "bx bx-log-out",
        'user_name' => $_SESSION["USER_NAME"] ?? "{$lang['login_title']}",
        'my_lists_route' => $this->router->pathFor('lists_home'),
        'create_list_route' => $routeCreate,
        'flag_img' => "<img class='selected' alt='".strtolower($_SESSION["lang"])."-flag' src='/assets/img/flags/flag-".strtolower($_SESSION["lang"]).".png'>",
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

function genererHeader($title, $styles = [])
{
    global $lang;
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
        $html .= "\n\t<link rel='stylesheet' href='/assets/css/$style'>";
    $html .= "\n</head>\n<body>\n";
    return empty($_SESSION['LOGGED_IN']) ? $html."\t<header class='guestmode'>{$lang['__invited']}</header>\n" : $html;
}