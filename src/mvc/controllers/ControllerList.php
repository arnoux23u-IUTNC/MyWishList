<?php

namespace mywishlist\mvc\controllers;

use Slim\Exception\NotFoundException;
use \mywishlist\mvc\Renderer;
use \mywishlist\mvc\views\ListView;
use \mywishlist\mvc\models\Liste;
use \mywishlist\exceptions\{ForbiddenException, CookieNotSetException};

class ControllerList{

    function process($rq, $rs, $args){
        #Récuperation des parametres
        $token = $rq->getQueryParam('token');
        $list_id = $args["path"] ?? "/";
        #Si on a un entier derrière le /list/
        if(preg_match("/^\d+(\/?)$/", $list_id)){
            #On récupère la liste | utilisation de where plutot que find pour que 01 ne soit pas transformé en 1
            $liste = Liste::where("no","LIKE",str_replace("/","",$list_id))->first();
            /*
            Si la liste a un token, on verifie celui saisi par l'utilisateur
            Si il n'en saisi pas ou que le token est incorrect alors on renvoie une erreur 403
            */ 
            if(isset($liste->token) && !empty($liste->token)){
                $liste = $liste->whereToken($token)->first();
                if (is_null($liste) || empty($token))
                    throw new ForbiddenException("Token Incorrect", "Vous n'avez pas l'autorisation d'accéder à cette ressource");
            }
            if(!in_array($rq->getCookieParam('typeUser'), ['createur', 'participant']))
                throw new CookieNotSetException();
            $renderer = new ListView($liste, $rq->getCookieParam('typeUser'), $token);
            return $rs->write($renderer->render(Renderer::SHOW));
        }
            //return $rs->write(ListView::afficherListe($list_id, $token));
        else
            switch(str_replace(" ", "", $list_id)){
                case "/":
                case "":
                    throw new ForbiddenException("Accès Interdit","Vous n'avez pas l'autorisation d'accéder à cette page");
                default:
                    throw new NotFoundException($rq, $rs);
        }
    }

}