<?php
session_start();

$lang = [];

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'i18n' . DIRECTORY_SEPARATOR . 'langs.php';

use mywishlist\bd\Eloquent as Eloquent;
use mywishlist\mvc\controllers\{ControllerUser, ControllerList, ControllerItem, ControllerAPI};
use mywishlist\exceptions\ExceptionHandler;
use Slim\{App, Container};

#Container
$container = new Container();
$container['settings']['displayErrorDetails'] = true;
$container['notFoundHandler'] = function () use ($lang) {
    return function ($request, $response) use ($lang) {
        $html = file_get_contents('..' . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . '404.html');
        preg_match_all("/{#(\w|_)+#}/", $html, $matches);
        foreach ($matches[0] as $match)
            $html = str_replace($match, $lang[str_replace(["{", "#", "}"], "", $match)], $html);
        return $response->withStatus(404)->write($html);
    };
};
$container['notAllowedHandler'] = function () use ($lang) {
    return function ($request, $response) use ($lang) {
        $html = file_get_contents('..' . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . '405.html');
        preg_match_all("/{#(\w|_)+#}/", $html, $matches);
        foreach ($matches[0] as $match)
            $html = str_replace($match, $lang[str_replace(["{", "#", "}"], "", $match)], $html);
        return $response->withStatus(405)->write($html);
    };
};
$container['errorHandler'] = function () use ($lang) {
    return new ExceptionHandler($lang);
};
$container['items_img_dir'] = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'items';
$container['users_img_dir'] = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'avatars';
$container['lang'] = $lang;

#Connexion à la base de données
Eloquent::start('..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'conf.ini');
$app = new App($container);

#Redirection du traffic dans l'application
//Utilisateurs
$app->any("/accounts/profile/2fa/{action:enable|disable|manage|recover}[/]", function ($request, $response, $args) {
    return (new ControllerUser($this, $request, $response, $args))->auth2FA();
})->setName('2fa');
$app->any("/accounts/{action:login|profile|logout|register|forgot_password|reset_password|api_key}[/]", function ($request, $response, $args) {
    return (new ControllerUser($this, $request, $response, $args))->process();
})->setName('accounts');
//Listes
$app->any("/lists/{id:[0-9]+}/edit/items[/]", function ($request, $response, $args) {
    return (new ControllerList($this, $request, $response, $args))->addItem();
})->setName('lists_edit_items_id');
$app->any("/lists/{id:[0-9]+}/edit[/]", function ($request, $response, $args) {
    return (new ControllerList($this, $request, $response, $args))->edit();
})->setName('lists_edit_id');
$app->any("/lists/{id:[0-9]+}[/]", function ($request, $response, $args) {
    return (new ControllerList($this, $request, $response, $args))->show();
})->setName('lists_show_id');
$app->any("/lists/new[/]", function ($request, $response, $args) {
    return (new ControllerList($this, $request, $response, $args))->create();
})->setName('lists_create');
$app->any("/lists[/]", function ($request, $response, $args) {
    //TODO return (new ControllerUser($this,$request, $response, $args))->create();
})->setName('lists_home');
//Items
$app->any("/items/{id:[0-9]+}/pot/{action:delete|participate|create}[/]", function ($request, $response, $args) {
    return (new ControllerItem($this, $request, $response, $args))->actionPot();
})->setName('items_pot_id');
$app->any("/items/{id:[0-9]+}/delete[/]", function ($request, $response, $args) {
    return (new ControllerItem($this, $request, $response, $args))->delete();
})->setName('items_delete_id');
$app->any("/items/{id:[0-9]+}/edit[/]", function ($request, $response, $args) {
    return (new ControllerItem($this, $request, $response, $args))->edit();
})->setName('items_edit_id');
$app->any("/items/{id:[0-9]+}[/]", function ($request, $response, $args) {
    return (new ControllerItem($this, $request, $response, $args))->show();
})->setName('items_show_id');
//Api
$app->any("/api/v1/lists/{path:.*}[/]", function ($request, $response, $args) {
    return (new ControllerAPI($this, $request, $response, $args))->listsV1();
})->setName('api_v1_lists');
$app->any("/api/v1/items/{path:.*}[/]", function ($request, $response, $args) {
    return (new ControllerAPI($this, $request, $response, $args))->itemsV1();
})->setName('api_v1_items');

#Route principale
$app->get('/', function ($request, $response) use ($lang) {
    $routeCreate = $this->router->pathFor('lists_create');
    $routeProfile = $this->router->pathFor('accounts', ['action' => 'profile']);
    $html = genererHeader("{$lang['home_title']} MyWishList", ["style.css", "lang.css"]) . file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'sidebar.phtml');
    $phtmlVars = array(
        'iconclass' => empty($_SESSION["LOGGED_IN"]) ? "bx bx-lock-open-alt" : "bx bx-log-out",
        'user_name' => $_SESSION["USER_NAME"] ?? "{$lang['login_title']}",
        'my_lists_route' => $this->router->pathFor('lists_home'),
        'create_list_route' => $routeCreate,
        'flag_img' => "<img class='selected' alt='" . strtolower($_SESSION["lang"]) . "-flag' src='/assets/img/flags/flag-" . strtolower($_SESSION["lang"]) . ".png'>",
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
    }
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

#Demmarage de l'application
try {
    $app->run();
} catch (Throwable $e) {
    header($_SERVER["SERVER_PROTOCOL"] . ' 500 Internal Server Error', true, 500);
    echo '<h1>Something went wrong!</h1>';
    print_r($e);
    exit;
}

/**
 * Method who generates the header of the page
 * @param string $title title of the page
 * @param array $styles stylesheets to include
 * @return string html code
 */
function genererHeader(string $title, array $styles = []): string
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
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="/assets/css/forall.css" rel="stylesheet">
        <link href="/assets/css/navbar.css" rel="stylesheet">
        <title>$title</title>
    EOD;
    foreach ($styles as $style)
        $html .= "\n\t<link rel='stylesheet' href='/assets/css/$style'>";
    $html .= "\n</head>\n<body>\n";
    return empty($_SESSION['LOGGED_IN']) ? $html . "\t<header class='guestmode'>{$lang['__invited']}</header>\n" : $html;
}