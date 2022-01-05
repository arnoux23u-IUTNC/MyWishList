<?php /** @noinspection PhpUndefinedFieldInspection */

namespace mywishlist\mvc\controllers;

use JetBrains\PhpStorm\NoReturn;
use Slim\Container;
use Slim\Http\{Request, Response};
use mywishlist\mvc\Renderer;
use mywishlist\mvc\views\{ItemView, ListView};
use mywishlist\mvc\models\{Item, Liste, User};

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

    /**
     * Generate JSON response for an item
     * @return Response JSON response
     */
    public function itemsV1(): Response
    {
        switch ($this->request->getMethod()) {
            case 'GET':
                //Si la méthode est de type get, on récupère les arguments
                $path = $this->args['path'] ?? null;
                //Si l'argument est un nombre
                if (preg_match("/^\d+$/", $path)) {
                    //On récupère l'item associé, et on génre une erreur si elle n'existe pas
                    $item = Item::where("id", "LIKE", $path)->firstOr(function () {
                        self::unauthorized();
                    });
                    $renderer = new ItemView($this->container, $item, $this->request);
                    //Si l'utilisateur est admin, on lui montre l'item
                    if ($this->user->isAdmin())
                        return $this->response->write($renderer->encode(Renderer::ADMIN_MODE));
                    if (!empty($item->list) && $this->user->canInteractWithList($item->list))
                        return $this->response->write($renderer->encode(Renderer::OWNER_MODE));
                    self::unauthorized();
                }
                break;
            default:
                self::notAllowed();
        }
        self::forbidden();
    }

    /**
     * Generate JSON response with a list of items
     * @return Response JSON response
     */
    public function listsV1(): Response
    {
        switch ($this->request->getMethod()) {
            case 'GET':
                //Si la méthode est de type get, on récupère les arguments
                $path = $this->args['path'] ?? null;
                //Si l'argument est un nombre
                if (preg_match("/^\d+$/", $path)) {
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
                self::notAllowed();
        }
        self::forbidden();
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

    /**
     * Send a 405 Method Not Allowed response
     * @return void
     */
    #[NoReturn] public static function notAllowed()
    {
        header("HTTP/1.1 405 Method Not Allowed");
        die();
    }

    /**
     * Send a 403 Forbidden response
     * @return void
     */
    #[NoReturn] public static function forbidden()
    {
        header("HTTP/1.1 403 Forbidden");
        die();
    }

}