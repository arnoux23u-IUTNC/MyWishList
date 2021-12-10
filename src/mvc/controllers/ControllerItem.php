<?php

namespace mywishlist\mvc\controllers;

use Slim\Exception\NotFoundException;
use \mywishlist\mvc\Renderer;
use \mywishlist\mvc\views\ItemView;
use \mywishlist\mvc\models\{Item, Liste};
use \mywishlist\exceptions\{ForbiddenException, CookieNotSetException};

class ControllerItem{

    function process($rq, $rs, $args){
        #Récuperation des parametres
        //$token = $rq->getQueryParam('token');
        $body = $rq->getParsedBody();

        $item_id = $args["path"] ?? "/";

        #Si on a un entier derrière le /item/
        if(preg_match("/^\d+(\/?)$/", $item_id)){
            /*
            On récupère l'item | utilisation de where plutot que find pour que 01 ne soit pas transformé en 1
            Récupération de la liste associée
            */
            $item = Item::where("id","LIKE",str_replace("/","",$item_id))->first();
            $liste = $item->liste->whereNo($body['liste_id'] ?? "")->first() ?? null;
            /*
            Si la liste a un token, on verifie celui saisi par l'utilisateur
            Si il n'en saisi pas ou que le token est incorrect alors on renvoie une erreur 403
            */ 
            if(!empty($liste->token))
                $liste = $liste->whereToken($body['token'] ?? "")->first();
            if (empty($liste))
                throw new ForbiddenException("Token Incorrect", "Vous n'avez pas l'autorisation d'accéder à cette ressource");
            if(!in_array($rq->getCookieParam('typeUser'), ['createur', 'participant']))
                throw new CookieNotSetException();
            $renderer = new ItemView($item, $rq->getCookieParam('typeUser'));
            return $rs->write($renderer->render(Renderer::SHOW));
        }
        else
            switch(str_replace(" ", "", $item_id)){
                case "/":
                case "":
                    throw new ForbiddenException("Accès Interdit","Vous n'avez pas l'autorisation d'accéder à cette page");
                default:
                    throw new NotFoundException($rq, $rs);
        }
    }

}