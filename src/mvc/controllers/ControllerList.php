<?php

namespace mywishlist\mvc\controllers;

use Slim\Exception\{NotFoundException, MethodNotAllowedException};
use \mywishlist\mvc\Renderer;
use \mywishlist\mvc\views\ListView;
use \mywishlist\mvc\models\Liste;
use \mywishlist\exceptions\{ForbiddenException, CookieNotSetException};

class ControllerList{

    function process($request, $response, $args){
        switch($request->getMethod()){
            case 'GET':
                $this->get($request, $response, $args);
                break;
            default:
                throw new MethodNotAllowedException($request, $response, ['GET', 'POST']);
        }
    }

    private function get($rq, $rs, $args){
        #Récuperation des parametres
        $token = filter_var($rq->getQueryParam('token'), FILTER_SANITIZE_STRING);
        $list_id = filter_var($args["path"] ?? "/", FILTER_SANITIZE_STRING);
        #Si on a un entier derrière le /list/
        if(preg_match("/^\d+(\/?)$/", $list_id)){
            #On récupère la liste | utilisation de where plutot que find pour que 01 ne soit pas transformé en 1
            $liste = Liste::where("no","LIKE",filter_var($list_id, FILTER_SANITIZE_NUMBER_INT))->first();
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
            $renderer = new ListView($liste, $rq->getCookieParam('typeUser'), $token ?? "");
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

    private function post($rq, $rs, $args){
        throw new NotFoundException($rq, $rs);
    }
}