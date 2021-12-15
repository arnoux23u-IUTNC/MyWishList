<?php

require_once __DIR__.'\..\src\vendor\autoload.php';

use \mywishlist\bd\Eloquent as Eloquent;
use \mywishlist\mvc\controllers\{ControllerList, ControllerItem};
use \mywishlist\exceptions\{ExceptionHandler, ForbiddenException, CookieNotSetException};
use Slim\{App, Container};

#Container
//TODO REMOVE DISPLAY_ERROR_DETAILS
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

#Launch
Eloquent::start('..\src\conf\conf.ini');
$app = new App($container);

#Modifications temporaires de variables
$app->get('/participant', function ($request, $response, $args) {
    setcookie("typeUser", 'participant', time()+3600*24);  /* expire dans 1 heure */
    return $response->write("<h1>Participant</h1><a href='/'>Retour</a>");
});
$app->get('/createur', function ($request, $response, $args) {
    setcookie("typeUser", 'createur', time()+3600*24);  /* expire dans 1 heure */
    return $response->write("<h1>Createur</h1> <a href='/'>Retour</a>");
});



#Redirection du traffic dans l'application
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
$app->post("/items/{id:[0-9]+}[/]", function ($request, $response, $args) {
    return (new ControllerItem($this))->show($request, $response, $args);
})->setName('items_show_id');

$app->get('/', function ($request, $response, $args) {
    //TODO REMOVE
    if(empty($request->getCookieParam('typeUser'))){
        throw new CookieNotSetException();
    }
    $routeCreate = $this->router->pathFor('lists_create');
    $html = genererHeader("MyWishList",["style.css"]) . <<<EOD
    <body>
        <h3>Bienvenue sur MyWishList</h3>
        <span><a id="createBtn" href="$routeCreate"></a></span>
        <span><a class="disabled" id="lookBtn" href="#"></a></span>
    </body>
    EOD; 
    return $response->write($html);
})->setName('main');

$app->run();

function genererHeader($title, $styles){
    $html = <<<EOD
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset='UTF-8'>
        <link rel="icon" href="/assets/img/icons/favicon.ico">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://fonts.googleapis.com/css?family=Poiret+One" rel="stylesheet">
    EOD;
    $html .= "\n\t<title>$title</title>\n";
    foreach($styles as $style)
        $html .= "\t<link rel='stylesheet' href='/assets/css/$style'>\n";
    return $html."</head>\n";
}