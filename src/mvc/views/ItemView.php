<?php

namespace mywishlist\mvc\views;

use Slim\Container;
use \mywishlist\mvc\models\{Item, Reserved};
use \mywishlist\exceptions\{ForbiddenException, CookieNotSetException};
use \mywishlist\mvc\Renderer;


class ItemView
{

    private Item $item;
    private $request;
    private Container $container;
    private string $public_key;	

    public function __construct(Container $c, Item $item = NULL, $request = null,string $public_key = "")
    {
        $this->container = $c;
        $this->item = $item;
        $this->request = $request;
        $this->public_key = $public_key;
    }

    private function showItem()
    {
        $i = $this->item;
        $reserved = Reserved::find($i->id);
        $l = $i->liste ?? null;
        if(!empty($reserved))
            if(($l->isExpired() || $this->request->getCookieParam('typeUser') == "participant") && !empty($reserved->user_id))
                $reservation_state = "Réservé par $reserved->user_id";
            else
                $reservation_state = "Item reservé";
        else
            $reservation_state = "Item non réservé";
        $item_desc = "<body>\n\t<div>\n\t\t<h2>$i->nom</h2>\n\t\t".(!empty($i->descr) ? "<p>Description : $i->descr</p>\n\t\t" : "").(!empty($i->url) ? "<p>URL : $i->url</p>\n\t\t" : "").(!empty($i->tarif) ? "<p>Prix : $i->tarif</p>\n\t\t" : "").(!empty($i->img) && file_exists(__DIR__."\..\..\..\assets\img\items\\$i->img") ? "<img alt='$i->nom' src='/assets/img/items/$i->img'>\n\t\t" : "")."<p>Liste associée : $l->titre | $l->description ($l->expiration)</p>\n\t\t"."<p>Etat reservation : $reservation_state</p>\n\t</div>\n</body>";
        return genererHeader("Item $i->id - MyWishList", ["item.css"]).$item_desc;
    }

    private function requestAuth(){
        $i = $this->item;
        $l = $i->liste()->first();
        $from = $this->request->getRequestTarget();
        switch ($from) {
            case (preg_match('/^\/items\/[0-9]+\/edit(\/?)/', $from) ? true : false) :
                $from = $this->container->router->pathFor('items_edit_id',['id' => $i->id]);
                break;
            default: 
                throw new ForbiddenException("Vous n'avez pas accès à cette page");
                break;
        }
        return genererHeader("Edition d'Item - Authentification", ["list.css"]). <<<EOD
        <body>
            <h2>Edition d'un Item</h2>
            <div>
                <form class='form_container' method="post" action="$from">
                    <label for="private_key">Token de modification de la liste $l->no</label>
                    <input type="password" name="private_key" id="private_key" required />
                    <button type="submit" name="sendBtn">Log</button>
                </form>
            <div>
        </body>
        </html>
        EOD;
    }

    private function preventDelete(){
        $i = $this->item;
        $l = $i->liste()->first();
        $from = $this->container->router->pathFor('items_delete_id',['id' => $i->id],["public_key" => $this->public_key]);
        return genererHeader("Suppression Item - Authentification", ["list.css"]). <<<EOD
        <body>
            <h2>Supression d'un Item</h2>
            <div>
                <form class='form_container' method="post" action="$from">
                    <label for="private_key">Token de modification de la liste $l->no</label>
                    <input type="password" name="private_key" id="private_key" required />
                    <button type="submit" name="sendBtn">Supprimer</button>
                </form>
            <div>
        </body>
        </html>
        EOD;
    }

    private function confirmDelete(){
        $i = $this->item;
        $private_key = $this->request->getParsedBodyParam("private_key");
        $delete = $this->container->router->pathFor('items_delete_id',['id' => $i->id]);
        $back = $this->container->router->pathFor('lists_show_id',['id' => $i->liste()->first()->no],["public_key" => $this->public_key]);
        return genererHeader("Suppression Item $i->id", ["list.css"]). <<<EOD
        <body>
            <h2>Supression de l'item $i->id</h2>
            <div>
                <p class="warning">Êtes vous sur de vouloir supprimer l'item $i->id ?</p>
                <form class='form_container' method="post" action="$delete">
                    <input type="hidden" name="auth" id="auth" value="$private_key"/>
                    <button type="submit" name="sendBtn">Supprimer</button> 
                </form>
                <a href="$back">Retour</a>
            <div>
        </body>
        </html>
        EOD;
    }

    private function edit(){
        $i = $this->item;
        //Utilisation de POST plutot que de la requête slim pour éviter de passer des arguments inutiles à la méthode
        $private_key = $this->request->getParsedBodyParam("private_key");
        $routeItemEdit = $this->container->router->pathFor('items_edit_id',["id" => $i->id]);
        return genererHeader("Item $i->id | Edition", ["list.css"]). <<<EOD
        <body>
            <h2>Edition de l'item $i->id</h2>
            <div>
                <form class='form_container' method="post" action="$routeItemEdit">
                    <label for="item_name">Nom</label>
                    <input type="text" name="item_name" id="titre" required autofocus value="$i->nom" />
                    <label for="description">Description</label>
                    <textarea name="description" id="description"/>$i->descr</textarea>
                    <label for="price">Prix</label>
                    <input type="number" min="0" step="0.01" value="$i->tarif" name="price" id="price" />
                    <label for="url">URL</label>
                    <input type="url" name="url" id="url" value="$i->url" />
                    <input type="hidden" name="auth" id="auth" value="$private_key"/>
                    <button type="submit" name="sendBtn">Créer</button>            
                </form>
            <div>
        </body>
        </html>
        EOD;
    }


    public function render($method){
        switch ($method) {
            case Renderer::SHOW:
                return $this->showItem();
            case Renderer::REQUEST_AUTH:
                return $this->requestAuth();
            case Renderer::EDIT:
                return $this->edit();
            case Renderer::PREVENT_DELETE:
                return $this->preventDelete();
            case Renderer::DELETE:
                return $this->confirmDelete();
            default:
                throw new ForbiddenException("Vous n'avez pas accès à cette page");
        }
    }


}