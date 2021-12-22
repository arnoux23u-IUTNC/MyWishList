<?php

namespace mywishlist\mvc\controllers;

use \mywishlist\Validator;
use Slim\Exception\{NotFoundException, MethodNotAllowedException};
use Slim\Container;
use \mywishlist\mvc\Renderer;
use \mywishlist\mvc\views\ItemView;
use \mywishlist\mvc\models\{Item,Reserved};
use \mywishlist\exceptions\{ForbiddenException, CookieNotSetException};

class ControllerItem
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
                $item = Item::where("id", "LIKE", filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT))->first();
                if (empty($item))
                    throw new NotFoundException($request, $response);
                $renderer = new ItemView($this->container, $item, $request);
                return $response->write($renderer->render(Renderer::REQUEST_AUTH));
            case 'POST':
                $item = Item::where("id", "LIKE", filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT))->first();
                $liste = $item->liste()->first();
                $private_key = filter_var($request->getParsedBodyParam('auth') ?? $request->getParsedBodyParam('private_key'), FILTER_SANITIZE_STRING);
                if (empty($item))
                    throw new NotFoundException($request, $response);
                if (!password_verify($private_key, $liste->private_key)){
                    $renderer = new ItemView($this->container, $item, $request);
                    return $response->withRedirect($this->container->router->pathFor('items_delete_id',['id' => $item->id],["info"=>"err"]));
                }
                if(!empty(Reserved::find($item->id)))
                    return $response->withRedirect($this->container->router->pathFor('lists_show_id', ["id" => $liste->no], ["public_key" => $liste->public_key, "state" => "resItem"]));
                if (!empty($request->getParsedBodyParam('auth')) && password_verify(filter_var($request->getParsedBodyParam('auth'), FILTER_SANITIZE_STRING), $liste->private_key)) {
                    $file = $request->getUploadedFiles()['file_img'];
                    $filename = $file->getClientFilename();
                    if ($request->getParsedBodyParam('type') === "upload" && !empty($file))
                        $info = Validator::validateFile($this->container, $file, $filename, "item");
                    $item->update([
                        'nom' => filter_var($request->getParsedBodyParam('item_name'), FILTER_SANITIZE_STRING),
                        'descr' => filter_var($request->getParsedBodyParam('description'), FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                        'tarif' => filter_var($request->getParsedBodyParam('price'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
                        'url' => filter_var($request->getParsedBodyParam('url') ?? "", FILTER_VALIDATE_URL) ? filter_var($request->getParsedBodyParam('url'), FILTER_SANITIZE_URL) : NULL,
                        'img' => $request->getParsedBodyParam('type') === "link" ? filter_var($request->getParsedBodyParam('url_img'), FILTER_SANITIZE_URL) : ($request->getParsedBodyParam('type') === "upload" ? ($info === "ok" ? $filename : $item->img) : NULL)
                    ]);
                    return $response->withRedirect($this->container->router->pathFor('lists_show_id', ["id" => $liste->no], ["public_key" => $liste->public_key, "state" => "modItem", "info" => $info]));
                } else {
                    $renderer = new ItemView($this->container, $item, $request);
                    return $response->write($renderer->render(Renderer::EDIT));
                }
            default:
                throw new MethodNotAllowedException($request, $response, ['GET', 'POST']);
        }
    }

    public function delete($request, $response, $args)
    {
        //TODO REMOVE
        if ($request->getCookieParam('typeUser') !== "createur")
            throw new CookieNotSetException("Vous n'êtes pas connecté", "Vous devez être connecté pour accéder à cette ressource");
        //END TODO REMOVE
        $public_key = filter_var($request->getQueryParam('public_key'), FILTER_SANITIZE_STRING);
        switch ($request->getMethod()) {
            case 'GET':
                $item = Item::where("id", "LIKE", filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT))->first();
                if (empty($item))
                    throw new NotFoundException($request, $response);
                $renderer = new ItemView($this->container, $item, $request, $public_key);
                return $response->write($renderer->render(Renderer::PREVENT_DELETE));
            case 'POST':
                $item = Item::where("id", "LIKE", filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT))->first();
                $liste = $item->liste()->first();
                $private_key = filter_var($request->getParsedBodyParam('auth') ?? $request->getParsedBodyParam('private_key'), FILTER_SANITIZE_STRING);
                if (empty($item))
                    throw new NotFoundException($request, $response);
                if (!password_verify($private_key, $liste->private_key)){
                    $renderer = new ItemView($this->container, $item, $request, $public_key);
                    return $response->withRedirect($this->container->router->pathFor('items_delete_id',['id' => $item->id],["public_key" => $public_key,"info"=>"err"]));
                }
                if (!empty($request->getParsedBodyParam('auth')) && password_verify(filter_var($request->getParsedBodyParam('auth'), FILTER_SANITIZE_STRING), $liste->private_key)) {
                    if(!empty(Reserved::find($item->id)))
                        return $response->withRedirect($this->container->router->pathFor('lists_show_id', ["id" => $liste->no], ["public_key" => $liste->public_key, "state" => "resItem"]));
                    $item->delete();
                    return $response->withRedirect($this->container->router->pathFor('lists_show_id', ["id" => $liste->no], ["public_key" => $liste->public_key, "state" => "delItem"]));
                } else {
                    $renderer = new ItemView($this->container, $item, $request, $public_key);
                    return $response->write($renderer->render(Renderer::DELETE));
                }
            default:
                throw new MethodNotAllowedException($request, $response, ['GET', 'POST']);
        }
    }

    public function show($request, $response, $args)
    {
        #Récuperation des parametres
        switch ($request->getMethod()) {
            case 'POST':
                /*
                On récupère l'item | utilisation de where plutot que find pour que 01 ne soit pas transformé en 1
                Récupération de la liste associée
                */
                $item = Item::where("id", "LIKE", filter_var(filter_var($args["id"], FILTER_SANITIZE_STRING), FILTER_SANITIZE_NUMBER_INT))->first();
                if (empty($item) )
                    throw new NotFoundException($request, $response);
                if (empty($item->liste))
                    throw new ForbiddenException($this->container->lang['exception_forbidden'], $this->container->lang['exception_ressource_not_allowed']);
                $liste = $item->liste()->first();
                /*
                Si la liste a un token de visibilite, on verifie celui saisi par l'utilisateur
                Si il n'en saisi pas ou que le token est incorrect alors on renvoie une erreur 403
                */
                if ($liste->no != filter_var($request->getParsedBodyParam('liste_id'), FILTER_SANITIZE_STRING) ?? "" || (!empty($liste->public_key) && $liste->public_key !== filter_var($request->getParsedBodyParam('public_key'), FILTER_SANITIZE_STRING) ?? ""))
                    throw new ForbiddenException($this->container->lang['exception_incorrect_token'], $this->container->lang['exception_ressource_not_allowed']);
                //TODO REMOVE
                if (!in_array($request->getCookieParam('typeUser'), ['createur', 'participant']))
                    throw new CookieNotSetException();
                //END TODO REMOVE
                $renderer = new ItemView($this->container, $item, $request);
                return $response->write($renderer->render(Renderer::SHOW));
            default:
                throw new MethodNotAllowedException($request, $response, ['POST']);
        }
    }
}