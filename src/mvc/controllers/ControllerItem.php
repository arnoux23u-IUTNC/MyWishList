<?php

namespace mywishlist\mvc\controllers;

use Slim\Exception\{NotFoundException, MethodNotAllowedException};
use Slim\Container;
use \mywishlist\mvc\Renderer;
use \mywishlist\mvc\views\ItemView;
use \mywishlist\mvc\models\Item;
use \mywishlist\exceptions\{ForbiddenException, CookieNotSetException};

class ControllerItem{

    private Container $container;

    public function __construct(Container $c){
        $this->container = $c;
    }

    public function show($request, $response, $args){
        #Récuperation des parametres
        
        switch($request->getMethod()){
            case 'POST':
                /*
                On récupère l'item | utilisation de where plutot que find pour que 01 ne soit pas transformé en 1
                Récupération de la liste associée
                */
                $item = Item::where("id","LIKE",filter_var(filter_var($args["id"], FILTER_SANITIZE_STRING), FILTER_SANITIZE_NUMBER_INT))->first();

                if(empty($item->liste)){
                    throw new ForbiddenException("Accès interdit", "Vous n'avez pas l'autorisation d'accéder à cette ressource");
                }
                $liste = $item->liste->whereNo(filter_var($request->getParsedBodyParam('liste_id'), FILTER_SANITIZE_STRING) ?? "")->first() ?? null;
                /*
                Si la liste a un token de visibilite, on verifie celui saisi par l'utilisateur
                Si il n'en saisi pas ou que le token est incorrect alors on renvoie une erreur 403
                */ 
                if(!empty($liste->public_key))
                    $liste = $liste->where("public_key", filter_var($request->getParsedBodyParam('public_key'), FILTER_SANITIZE_STRING) ?? "")->first();
                if (empty($liste))
                    throw new ForbiddenException("Token Incorrect", "Vous n'avez pas l'autorisation d'accéder à cette ressource");
                if(!in_array($request->getCookieParam('typeUser'), ['createur', 'participant']))
                    throw new CookieNotSetException();
                $renderer = new ItemView($this->container, $item, $request->getCookieParam('typeUser'));
                return $response->write($renderer->render(Renderer::SHOW));
            default:
                throw new MethodNotAllowedException($request, $response, ['GET']);
        }
    }

}