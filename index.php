<?php
session_start();

$lang = [];

require_once __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'i18n' . DIRECTORY_SEPARATOR . 'langs.php';

use mywishlist\db\Eloquent as Eloquent;
use mywishlist\mvc\controllers\{ControllerUser, ControllerList, ControllerItem, ControllerAPI};
use mywishlist\exceptions\ExceptionHandler;
use Slim\{App, Container};

#Container
$container = new Container();
$container['settings']['displayErrorDetails'] = true;
$container['notFoundHandler'] = function () use ($lang) {
    return function ($request, $response) use ($lang) {
        $html = file_get_contents('errors' . DIRECTORY_SEPARATOR . '404.html');
        preg_match_all("/{#(\w|_)+#}/", $html, $matches);
        foreach ($matches[0] as $match)
            $html = str_replace($match, $lang[str_replace(["{", "#", "}"], "", $match)], $html);
        return $response->withStatus(404)->write($html);
    };
};
$container['notAllowedHandler'] = function () use ($lang) {
    return function ($request, $response) use ($lang) {
        $html = file_get_contents('errors' . DIRECTORY_SEPARATOR . '405.html');
        preg_match_all("/{#(\w|_)+#}/", $html, $matches);
        foreach ($matches[0] as $match)
            $html = str_replace($match, $lang[str_replace(["{", "#", "}"], "", $match)], $html);
        return $response->withStatus(405)->write($html);
    };
};
$container['errorHandler'] = function () use ($lang, $container) {
    return new ExceptionHandler($lang, $container);
};
$container['items_img_dir'] = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'items';
$container['users_img_dir'] = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'avatars';
$container['lang'] = $lang;

#Connexion à la base de données
Eloquent::start('src' . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'conf.ini');
$app = new App($container);

#Redirection du traffic dans l'application
//Utilisateurs
$app->any("/accounts/profile/2fa/{action:enable|disable|manage|recover}[/]", function ($request, $response, $args) {
    return (new ControllerUser($this, $request, $response, $args))->auth2FA();
})->setName('2fa');
$app->any("/accounts/{action:login|profile|logout|register|forgot_password|reset_password|api_key|delete}[/]", function ($request, $response, $args) {
    return (new ControllerUser($this, $request, $response, $args))->process();
})->setName('accounts');
//Listes
$app->any("/lists/{id:[0-9]+}/edit/items[/]", function ($request, $response, $args) {
    return (new ControllerList($this, $request, $response, $args))->addItem();
})->setName('lists_edit_items_id');
$app->any("/lists/{id:[0-9]+}/addmessage[/]", function ($request, $response, $args) {
    return (new ControllerList($this, $request, $response, $args))->addMessage();
})->setName('lists_add_message_id');
$app->any("/lists/{id:[0-9]+}/edit[/]", function ($request, $response, $args) {
    return (new ControllerList($this, $request, $response, $args))->edit();
})->setName('lists_edit_id');
$app->any("/lists/{id:[0-9]+}/claim[/]", function ($request, $response, $args) {
    return (new ControllerList($this, $request, $response, $args))->claim();
})->setName('lists_claim_id');
$app->any("/lists/{id:[0-9]+}/delete[/]", function ($request, $response, $args) {
    return (new ControllerList($this, $request, $response, $args))->delete();
})->setName('lists_delete_id');
$app->any("/lists/{id:[0-9]+}[/]", function ($request, $response, $args) {
    return (new ControllerList($this, $request, $response, $args))->show();
})->setName('lists_show_id');
$app->any("/lists/new[/]", function ($request, $response, $args) {
    return (new ControllerList($this, $request, $response, $args))->create();
})->setName('lists_create');
$app->any("/lists[/]", function ($request, $response, $args) use ($lang) {
    return (new ControllerUser($this, $request, $response, $args))->publicLists();
})->setName('lists_home');
//Items
$app->any("/items/{id:[0-9]+}/pot/{action:delete|participate|create}[/]", function ($request, $response, $args) {
    return (new ControllerItem($this, $request, $response, $args))->actionPot();
})->setName('items_pot_id');
$app->any("/items/{id:[0-9]+}/reserve[/]", function ($request, $response, $args) {
    return (new ControllerItem($this, $request, $response, $args))->reserve();
})->setName('items_reserve_id');
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
$app->get("/createurs[/]", function ($request, $response, $args) {
    return (new ControllerUser($this, $request, $response, $args))->creators();
})->setName('createurs');
//Home
$app->get('/', function ($request, $response) {
    return (new ControllerUser($this, $request, $response, []))->home();
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
        <link rel="icon" href="assets/img/icons/favicon.ico">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="MyWishList est un projet permettant de gérer des listes de souhaits liées à des utilisateurs.">
        <meta property="og:title" content="MyWishList - GARNX.FR" />
        <meta property="og:type" content="website"/>
        <meta property="og:url" content="https://mywishlist.garnx.fr/" />
        <meta property="og:image" content="https://mywishlist.garnx.frassets/img/logos/2.png" />
        <meta property="og:description" content="MyWishList est un projet permettant de gérer des listes de souhaits liées à des utilisateurs." />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
        <link href="https://fonts.googleapis.com/css?family=Poiret+One" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="assets/css/forall.css" rel="stylesheet">
        <link href="assets/css/navbar.css" rel="stylesheet">
        <title>$title</title>
    EOD;
    foreach ($styles as $style)
        $html .= "\n\t<link rel='stylesheet' href='assets/css/$style'>";
    $html .= "\n</head>\n<body>\n";
    return empty($_SESSION['LOGGED_IN']) ? $html . "\t<header class='guestmode'>{$lang['__invited']}</header>\n" : $html;
}