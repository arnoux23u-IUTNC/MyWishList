<?php

require_once __DIR__.'\..\src\vendor\autoload.php';

use \mywishlist\bd\Eloquent as Eloquent;
use \mywishlist\mvc\controllers\{ControllerList, ControllerItem};
use \mywishlist\exceptions\{ExceptionHandler, ForbiddenException};
use Slim\{App, Container};

#Container
$container = new Container(['settings' => ['displayErrorDetails' => true]]);
$container['notFoundHandler'] = function () {
    return function ($request, $response) {
        return $response->withStatus(404)->write(file_get_contents('..\errors\404.html'));
    };
};
$container['errorHandler'] = function ($c) {
    return new ExceptionHandler();
};

#Controllers
$controllerList = new ControllerList();
$controllerItem = new ControllerItem();

#Launch
Eloquent::start('..\src\conf\conf.ini');
$app = new App($container);

#Modifications temporaires de variables
$app->get('/participant', function ($request, $response, $args) {
    setcookie("typeUser", 'participant', time()+3600);  /* expire dans 1 heure */
    return $response->write("<h1>Participant</h1>");
});
$app->get('/createur', function ($request, $response, $args) {
    setcookie("typeUser", 'createur', time()+3600);  /* expire dans 1 heure */
    return $response->write("<h1>Createur</h1>");
});

#On redirige tout le traffic de /lists vers le ControllerList
$app->get("/lists[/{path:.*}]", function ($request, $response, $args) use ($controllerList) {
    return $controllerList->process($request, $response, $args);
});
#On redirige tout le traffic de /items vers le ControllerItem
$app->get("/items[/{path:.*}]", function ($request, $response, $args) use ($controllerItem) {
    throw new ForbiddenException("Accès Interdit","Vous n'avez pas l'autorisation d'accéder à cette page");
});
$app->post("/items[/{path:.*}]", function ($request, $response, $args) use ($controllerItem) {
    return $controllerItem->process($request, $response, $args);
});

$app->get('/', function ($request, $response, $args) {
    //TODO REMOVE
    if(!isset($_COOKIE["typeUser"]) || empty($_COOKIE["typeUser"])){
        return $response->write("<h1>Veuillez choisir votre type d'utilisateur</h1>");
    }
    $html = genererHeader("MyWishList",["style.css"]) . <<<EOD
    <body>
        <h3>Bienvenue sur MyWishList</h3>
        <span><a id="createBtn" href="/lists/create"></a></span>
        <span><a id="lookBtn" href="/lists"></a></span>
    </body>
    EOD; 
    return $response->write($html);
});

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