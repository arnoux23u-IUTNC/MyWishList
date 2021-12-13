<?php

namespace mywishlist\mvc\controllers;

use Slim\Exception\{NotFoundException, MethodNotAllowedException};
use \Tokenly\TokenGenerator\TokenGenerator;
use \mywishlist\mvc\Renderer;
use \mywishlist\mvc\views\ListView;
use \mywishlist\mvc\models\Liste;
use \mywishlist\exceptions\{ForbiddenException, CookieNotSetException};

class ControllerList{

    private $container;

    public function __construct($c){
        $this->container = $c;
    }

    function process($request, $response, $args){
        $pathArgs = filter_var($args["path"] ?? "/", FILTER_SANITIZE_STRING);
        switch($request->getMethod()){
            case 'GET':
                return $this->get($request, $response, $pathArgs);
                break;
            case 'POST':
                return $this->post($request, $response, $pathArgs);
                break;
            default:
                throw new MethodNotAllowedException($request, $response, ['GET', 'POST']);
        }
    }

    private function get($rq, $rs, $pathArgs){
        #Récuperation des parametres
        $public_key = filter_var($rq->getQueryParam('public_key'), FILTER_SANITIZE_STRING);
        #Si on a un entier derrière le /list/
        if(preg_match("/^\d+(\/?)$/", $pathArgs)){
            #On récupère la liste | utilisation de where plutot que find pour que 01 ne soit pas transformé en 1
            $liste = Liste::where("no","LIKE",filter_var($pathArgs, FILTER_SANITIZE_NUMBER_INT))->first();
            /*
            Si la liste a un token, on verifie celui saisi par l'utilisateur
            Si il n'en saisi pas ou que le token est incorrect alors on renvoie une erreur 403
            */ 
            if(!empty($liste->public_key)){
                $liste = Liste::whereNoAndPublicKey($liste->no,$public_key)->first();
                if (is_null($liste) || empty($public_key))
                    throw new ForbiddenException("Token Incorrect", "Vous n'avez pas l'autorisation d'accéder à cette ressource");
            }
            if(!in_array($rq->getCookieParam('typeUser'), ['createur', 'participant']))
                throw new CookieNotSetException();
            $renderer = new ListView($liste, $rq, $public_key ?? "");
            return $rs->write($renderer->render(Renderer::SHOW));
        }
        else
            switch(str_replace([" ", "/"], "", $pathArgs)){
                case "create":
                    $renderer = new ListView();
                    return $rs->write($renderer->render(Renderer::CREATE));
                    break;
                default:
                    throw new NotFoundException($rq, $rs);
        }
    }

    private function post($rq, $rs, $pathArgs){
        switch(str_replace([" ", "/"], "", $pathArgs)){
            case "create":
                $liste = new Liste();
                $liste->titre = filter_var($rq->getParsedBodyParam('titre'), FILTER_SANITIZE_STRING);
                $liste->description = filter_var($rq->getParsedBodyParam('description'), FILTER_SANITIZE_STRING) ?? NULL;
                $liste->expiration = $rq->getParsedBodyParam('exp') !== "" ? filter_var($rq->getParsedBodyParam('exp'), FILTER_SANITIZE_STRING) : NULL;
                $liste->public_key = (!empty($rq->getParsedBodyParam('public_key')) && trim($rq->getParsedBodyParam('public_key')) !== "") ? filter_var($rq->getParsedBodyParam('public_key'), FILTER_SANITIZE_STRING) : NULL;
                $token = (new TokenGenerator())->generateToken(16);
                $liste->private_key = password_hash($token, PASSWORD_DEFAULT);
                $liste->save();
                $path = $this->container->router->pathFor('lists',["path" => $liste->no."?public_key=$liste->public_key"]);
                return $rs->write("<script type='text/javascript'>alert('Votre token de modification est $token');window.location.href='$path';</script>");
            default:
                throw new NotFoundException($rq, $rs);
        }
    }
}