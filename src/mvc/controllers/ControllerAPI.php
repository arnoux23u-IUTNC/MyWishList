<?php

namespace mywishlist\mvc\controllers;

use JetBrains\PhpStorm\NoReturn;
use mywishlist\mvc\Renderer;
use mywishlist\mvc\views\ListView;
use Slim\Container;
use Slim\Http\{Request, Response};
use Slim\Exception\MethodNotAllowedException;
use mywishlist\mvc\models\{Liste, User};

/**
 * Class ControllerAPI
 * Controller for the global API
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\controllers
 */
class ControllerAPI
{

    private Container $container;
    /**
     * @var User User associated to the Controller
     */
    private User $user;
    private Request $request;
    private Response $response;
    private array $args;

    /**
     * ControllerAPI constructor
     * @param Container $c
     * @param Request $request
     * @param Response $response
     * @param array $args
     */
    public function __construct(Container $c, Request $request, Response $response, array $args)
    {
        $this->container = $c;
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;
        //Récuperation de la clé API avec differentes variables
        $api_key = $_SERVER['HTTP_AUTHORIZATION'] ?? $this->request->getHeader("Authorization")[0] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        $this->user = User::where("api_key", "LIKE", $api_key)->firstOr(function () {
            self::unauthorized();
        });
    }

    //TODO DOC
    public function itemsV1()
    {
        return $this->response->write("");
    }

    /**
     * Generate JSON response with a list of items
     * @return Response JSON response
     * @throws MethodNotAllowedException If method is not allowed
     */
    public function listsV1(): Response
    {
        switch ($this->request->getMethod()) {
            case 'GET':
                //Si la méthode est de type get, on récupère les arguments
                $path = $this->args['path'] ?? null;
                //Si l'argument est un nombre
                if (preg_match("/^\d$/", $path)) {
                    //On récupère la liste correspondante, et on génre une erreur si elle n'existe pas
                    $liste = Liste::where("no", "LIKE", $path)->firstOr(function () {
                        self::unauthorized();
                    });
                    $renderer = new ListView($this->container, $liste, $this->request);
                    //Si l'utilisateur est admin, on lui montre la liste
                    if ($this->user->isAdmin())
                        return $this->response->write($renderer->encode(Renderer::ADMIN_MODE));
                    if ($this->user->canInteractWithList($liste))
                        return $this->response->write($renderer->encode(Renderer::OWNER_MODE));
                    self::unauthorized();
                }
                break;
            default:
                throw new MethodNotAllowedException($this->request, $this->response, ['GET']);
        }
        return $this->response->withStatus(404);
    }

    /*
    GET = Récupérer un élément
    POST = Créer un élément
    PUT = Modifier un élément
    DELETE = Supprimer un élément
    */

    /**
     * Send a 401 Unauthorized response
     * @return void
     */
    #[NoReturn] public static function unauthorized()
    {
        header("HTTP/1.1 401 Unauthorized");
        die();
    }

}