<?php

namespace mywishlist\mvc\controllers;

use Slim\Exception\{NotFoundException, MethodNotAllowedException};
use Slim\Container;
use \Tokenly\TokenGenerator\TokenGenerator;
use \mywishlist\mvc\Renderer;
use \mywishlist\mvc\views\ListView;
use \mywishlist\mvc\models\Liste;
use \mywishlist\exceptions\{ForbiddenException, CookieNotSetException};

class ControllerList
{

    private Container $container;

    public function __construct(Container $c)
    {
        $this->container = $c;
    }

    public function edit($request, $response, $args)
    {
        //TODO REMOVE
        if ($request->getCookieParam('typeUser') !== "createur")
            throw new CookieNotSetException("Vous n'êtes pas connecté", "Vous devez être connecté pour accéder à cette ressource");
        //END TODO REMOVE
        switch ($request->getMethod()) {
            case 'GET':
                $liste = Liste::where("no", "LIKE", filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT))->first();
                if (empty($liste))
                    throw new NotFoundException($request, $response);
                $renderer = new ListView($this->container, $liste, $request);
                return $response->write($renderer->render(Renderer::REQUEST_AUTH));
            case 'POST':
                $liste = Liste::where("no", "LIKE", filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT))->first();
                $private_key = filter_var($request->getParsedBodyParam('auth') ?? $request->getParsedBodyParam('private_key'), FILTER_SANITIZE_STRING);
                if (empty($liste) || !password_verify($private_key, $liste->private_key))
                    throw new ForbiddenException($this->container->lang['exception_incorrect_token'], $this->container->lang['exception_ressource_not_allowed']);
                if (!empty($request->getParsedBodyParam('auth')) && password_verify(filter_var($request->getParsedBodyParam('auth'), FILTER_SANITIZE_STRING), $liste->private_key)) {
                    $liste->update([
                        'titre' => filter_var($request->getParsedBodyParam('titre'), FILTER_SANITIZE_STRING),
                        'user_id' => filter_var($request->getParsedBodyParam('user'), FILTER_SANITIZE_STRING),
                        'description' => filter_var($request->getParsedBodyParam('descr'), FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                        'expiration' => filter_var($request->getParsedBodyParam('exp'), FILTER_SANITIZE_STRING),
                        'public_key' => filter_var($request->getParsedBodyParam('public_key'), FILTER_SANITIZE_STRING)
                    ]);
                    return $response->withRedirect($this->container->router->pathFor('lists_show_id', ["id" => $liste->no], ["public_key" => $liste->public_key, "state" => "update"]));
                } else {
                    $renderer = new ListView($this->container, $liste, $request);
                    return $response->write($renderer->render(Renderer::EDIT));
                }
            default:
                throw new MethodNotAllowedException($request, $response, ['GET', 'POST']);
        }
    }

    public function addItem($request, $response, $args)
    {
        //TODO REMOVE
        if ($request->getCookieParam('typeUser') !== "createur")
            throw new CookieNotSetException("Vous n'êtes pas connecté", "Vous devez être connecté pour accéder à cette ressource");
        //END TODO REMOVE
        $list_id = filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT);
        switch ($request->getMethod()) {
            case 'GET':
                $liste = Liste::where("no", "LIKE", $list_id)->first();
                if (empty($liste))
                    throw new NotFoundException($request, $response);
                $renderer = new ListView($this->container, $liste, $request);
                return $response->write($renderer->render(Renderer::REQUEST_AUTH));
            case 'POST':
                $liste = Liste::where("no", "LIKE", $list_id)->first();
                $private_key = filter_var($request->getParsedBodyParam('auth') ?? $request->getParsedBodyParam('private_key'), FILTER_SANITIZE_STRING);
                if (empty($liste) || !password_verify($private_key, $liste->private_key))
                    throw new ForbiddenException($this->container->lang['exception_incorrect_token'], $this->container->lang['exception_ressource_not_allowed']);
                if (!empty($request->getParsedBodyParam('auth')) && password_verify(filter_var($request->getParsedBodyParam('auth'), FILTER_SANITIZE_STRING), $liste->private_key)) {
                    $liste->items()->create([
                        'liste_id' => $list_id,
                        'nom' => filter_var($request->getParsedBodyParam('item_name'), FILTER_SANITIZE_STRING),
                        'descr' => filter_var($request->getParsedBodyParam('description'), FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                        'tarif' => filter_var($request->getParsedBodyParam('price'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
                        'url' => filter_var($request->getParsedBodyParam('url'), FILTER_SANITIZE_URL),
                    ]);
                    return $response->withRedirect($this->container->router->pathFor('lists_show_id', ["id" => $liste->no], ["public_key" => $liste->public_key, "state" => "newItem"]));
                } else {
                    $renderer = new ListView($this->container, $liste, $request);
                    return $response->write($renderer->render(Renderer::EDIT_ADD_ITEM));
                }
            default:
                throw new MethodNotAllowedException($request, $response, ['GET', 'POST']);
        }
    }

    public function create($request, $response, $args)
    {
        //TODO REMOVE
        if ($request->getCookieParam('typeUser') !== "createur")
            throw new CookieNotSetException("Vous n'êtes pas connecté", "Vous devez être connecté pour accéder à cette ressource");
        //END TODO REMOVE
        switch ($request->getMethod()) {
            case 'GET':
                $renderer = new ListView($this->container);
                return $response->write($renderer->render(Renderer::CREATE));
            case 'POST':
                $liste = new Liste();
                $liste->titre = filter_var($request->getParsedBodyParam('titre'), FILTER_SANITIZE_STRING);
                $liste->description = filter_var($request->getParsedBodyParam('description'), FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? NULL;
                $liste->expiration = $request->getParsedBodyParam('exp') !== "" ? filter_var($request->getParsedBodyParam('exp'), FILTER_SANITIZE_STRING) : NULL;
                $liste->public_key = (!empty($request->getParsedBodyParam('public_key')) && trim($request->getParsedBodyParam('public_key')) !== "") ? filter_var($request->getParsedBodyParam('public_key'), FILTER_SANITIZE_STRING) : NULL;
                $token = (new TokenGenerator())->generateToken(16);
                $liste->private_key = password_hash($token, PASSWORD_DEFAULT);
                $liste->save();
                $path = $this->container->router->pathFor('lists_show_id', ["id" => $liste->no], ["public_key" => $liste->public_key]);
                return $response->write("<script type='text/javascript'>alert('{$this->container->lang['alert_modify_token']} $token');window.location.href='$path';</script>");
            default:
                throw new MethodNotAllowedException($request, $response, ['GET', 'POST']);
        }
    }

    public function show($request, $response, $args)
    {
        switch ($request->getMethod()) {
            case 'GET':
                $public_key = filter_var($request->getQueryParam('public_key'), FILTER_SANITIZE_STRING);
                $liste = Liste::where("no", "LIKE", filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT))->first();
                /*
                Si la liste a un token, on verifie celui saisi par l'utilisateur
                Si il n'en saisi pas ou que le token est incorrect alors on renvoie une erreur 403
                */
                if (!empty($liste->public_key))
                    $liste = Liste::whereNoAndPublicKey($liste->no, $public_key)->first();
                if (empty($liste))
                    throw new ForbiddenException($this->container->lang['exception_incorrect_token'], $this->container->lang['exception_ressource_not_allowed']);
                //TODO REMOVE
                if (!in_array($request->getCookieParam('typeUser'), ['createur', 'participant']))
                    throw new CookieNotSetException();
                //END TODO REMOVE
                $renderer = new ListView($this->container, $liste, $request, $public_key ?? "");
                return $response->write($renderer->render(Renderer::SHOW));
            default:
                throw new MethodNotAllowedException($request, $response, ['GET']);
        }
    }

}