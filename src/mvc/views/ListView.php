<?php

namespace mywishlist\mvc\views;

use Slim\Container;
use \mywishlist\mvc\models\{Liste, Reserved};
use \mywishlist\exceptions\{ForbiddenException, CookieNotSetException};
use mywishlist\mvc\Renderer;

class ListView
{

    private Liste $list;
    private Container $container;
    private string $public_key, $private_key;
    private $request;
    

    public function __construct(Container $c, $list = NULL, $request = null, string $public_key = "")
    {
        if(!empty($list))
            $this->list = $list;
        $this->container = $c;
        $this->request = $request;
        $this->public_key = $public_key;
    }

    private function showList()
    {
        $l = $this->list;
        $routeAddItem = $this->container->router->pathFor('lists_edit_items_id',['id' => $l->no]);
        $dataHeader = "\n\t".match(filter_var($this->request->getQueryParam('state'), FILTER_SANITIZE_STRING) ?? ""){
            "update" => "<div class='popup'>La liste a été mise à jour.</div>",
            "newItem"  => "<div class='popup'>Nouvel item ajouté.</div>",
            "modItem"  => "<div class='popup'>Item modifié.</div>",
            "delItem"  => "<div class='popup warning'>Item supprimé.</div>",
            default => ""
        };    
        $warnEdit = "\n\t".match(filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? ""){
            "ok" => "<div class='popup'>Image sauvegardée</div>",
            "typeerr"  => "<div class='popup warning'>Type non supporté [png,jpg,jpeg]</div>",
            "sizeerr"  => "<div class='popup warning'>Taille maximale : 10Mo</div>",
            "writeerr"  => "<div class='popup warning'>Permission en écriture refusée</div>",
            "fileexist"  => "<div class='popup warning'>Une image porte dejà ce nom</div>",
            "error"  => "<div class='popup warning'>Une erreur est survenue pendant l'envoi de l'image</div>",
            default => ""
        };        
        $html = genererHeader("Liste $l->no - MyWishList", ["list.css"]) . <<<EOD
            <h2>$l->titre</h2>$dataHeader$warnEdit
            <p>Utilisateur associé : $l->user_id</p>
            <p>Description : $l->description</p>
            <p>Date d'expiration : $l->expiration</p>
            <a href="$routeAddItem">Ajouter un item</a>
            <div class='items_list'>
                <div class='box'>
                    <h2>Items de la liste $l->no</h2>
                    <ul>
        EOD;
        foreach ($l->items as $pos => $item) {
            $pos++;
            $reserved = Reserved::find($item->id);
            $routeModItem = $this->container->router->pathFor('items_edit_id',['id' => $item->id]);
            $routeDelItem = $this->container->router->pathFor('items_delete_id',['id' => $item->id],["public_key" => $this->public_key]);
            $item_desc = "<span>$pos</span>$item->nom".(!empty($item->img) ? (file_exists(__DIR__."\..\..\..\assets\img\items\\$item->img") ? "<img class='list_item_img' alt='$item->nom' src='/assets/img/items/$item->img'>" : (preg_match("/^((https?:\/{2})?(\w[\w\-\/\.]+).(jpe?g|png))?$/",$item->img) ? "<img class='list_item_img' alt='$item->nom' src='$item->img'>" : "")):"");
            $item_res = ($this->request->getCookieParam("typeUser") === 'participant') ? (!empty($reserved) ? "<p>Réservé par $reserved->user_id -> $reserved->message</p>" : ($l->isExpired() ? "<p><i>Vous ne pouvez pas reserver cet item</i></p>" : "\n\t\t\t\t\t\t<form method='post' action='#'>\n\t\t\t\t\t\t\t<button class='sendBtn' type='submit' name='sendBtn' title='Réserver'><img src='/assets/img/checkmark.png'/></button>\n\t\t\t\t\t\t</form>\n\t\t\t\t\t")) : (!empty($reserved) ? ($l->isExpired() ? "<p>Réservé par $reserved->user_id -> $reserved->message</p>" : "<p>Item reservé</p>") : "<p>Item non réservé</p>");
            $item_mod = $this->request->getCookieParam("typeUser") === 'createur' && empty($reserved) ? "\n\t\t\t\t\t<div class='reservation_state'>\n\t\t\t\t\t\t<a href='$routeModItem'><img src='/assets/img/edit.png'/>\n\t\t\t\t\t</div>" : "";
            $item_del = $this->request->getCookieParam("typeUser") === 'createur' && empty($reserved) ? "\n\t\t\t\t\t<div class='reservation_state'>\n\t\t\t\t\t\t<a href='$routeDelItem'><img src='/assets/img/del.png'/>\n\t\t\t\t\t</div>" : "";
            $routeItemShow = $this->container->router->pathFor("items_show_id", ["id" => $item->id]);
            $html .= <<<EOD
                    
                            <li>
                                <form method="post" action="$routeItemShow">
                                    <input type="hidden" name="public_key" value="$this->public_key" /> 
                                    <input type="hidden" name="liste_id" value="$l->no" /> 
                                    <a onclick="this.parentNode.submit();">$item_desc</a>
                                </form>$item_mod
                                <div class="reservation_state">$item_res</div>$item_del
                            </li>
            EOD;
        }
        $routeListEdit = $this->container->router->pathFor('lists_edit_id',["id" => $l->no]);
        return $html . "\n\t\t\t</ul>\n\t\t</div>\n\t</div>\n\t<a href='$routeListEdit'>Editer la liste</a>\n</body>\n</html>";
    }

    private function createList(){
        $routeCreate = $this->container->router->pathFor('lists_create');
        return genererHeader("Créer une liste", ["list.css"]). <<<EOD
            <h2>Créer une liste</h2>
            <div>
                <form class='form_container' method="post" action="$routeCreate">
                    <label for="titre">Titre</label>
                    <input type="text" name="titre" id="titre" required autofocus />
                    <label for="description">Description</label>
                    <textarea name="description" id="description"/></textarea>
                    <label for="exp">Date d'expiration </label>
                    <input type="date" name="exp" id="expiration"/>
                    <label for="public_key">Token d'accès public </label>
                    <input type="text" name="public_key" id="public_key" />
                    <button type="submit" name="sendBtn">Créer</button>
                </form>
            <div>
        </body>
        </html>
        EOD;
    }

    private function addItem(){
        $l = $this->list;
        $routeAddItem = $this->container->router->pathFor('lists_edit_items_id',['id' => $l->no]);
        $private_key = filter_var($this->request->getParsedBodyParam("private_key"), FILTER_SANITIZE_STRING);
        return genererHeader("Items | Liste $l->no", ["list.css"]). <<<EOD
            <h2>Ajouter un item à la liste $l->no</h2>
            <div>
                <form class='form_container' method="post" action="$routeAddItem">
                    <label for="item_name">Nom</label>
                    <input type="text" name="item_name" id="titre" required autofocus />
                    <label for="description">Description</label>
                    <textarea name="description" id="description"/></textarea>
                    <label for="price">Prix</label>
                    <input type="number" min="0" step="0.01" name="price" id="price" />
                    <label for="url">URL</label>
                    <input type="url" name="url" id="url" />
                    <input type="hidden" name="auth" id="auth" value="$private_key"/>
                    <button type="submit" name="sendBtn">Créer</button>
                </form>
            <div>
        </body>
        </html>
        EOD;
    }

    private function requestAuth(){
        $l = $this->list;
        $from = $this->request->getRequestTarget();
        switch ($from) {
            case (preg_match('/^\/lists\/[0-9]+\/edit\/items(\/?)/', $from) ? true : false) :
                $from = $this->container->router->pathFor('lists_edit_items_id',['id' => $l->no]);
                break;
            case (preg_match('/^\/lists\/[0-9]+\/edit(\/?)/', $from) ? true : false) :
            $from = $this->container->router->pathFor('lists_edit_id',['id' => $l->no]);
                break;
            default: 
                throw new ForbiddenException("Vous n'avez pas accès à cette page");
                break;
        }
        return genererHeader("Edition de liste - Authentification", ["list.css"]). <<<EOD
            <h2>Edition de liste</h2>
            <div>
                <form class='form_container' method="post" action="$from">
                    <label for="private_key">Token de modification de la liste $l->no</label>
                    <input type="password" name="private_key" id="private_key" required />
                    <button type="submit" name="sendBtn">Créer</button>
                </form>
            <div>
        </body>
        </html>
        EOD;
    }

    private function edit(){
        $l = $this->list;
        //Utilisation de POST plutot que de la requête slim pour éviter de passer des arguments inutiles à la méthode
        $private_key = filter_var($this->request->getParsedBodyParam("private_key"), FILTER_SANITIZE_STRING);
        $routeListEdit = $this->container->router->pathFor('lists_edit_id',["id" => $this->list->no]);
        return genererHeader("Liste $l->no | Edition", ["list.css"]). <<<EOD
            <h2>Edition de la liste $l->no</h2>
            <div>
                <form class='form_container' method="post" action="$routeListEdit">
                    <label for="title">Titre</label>
                    <input type="text" name="titre" id="titre" required value="$l->titre"/>
                    <label for="descr">Description</label>
                    <input type="text" name="descr" id="description" value="$l->description"/>
                    <label for="user">Utilisateur associé</label>
                    <input type="number" name="user" id="user" value="$l->user_id"/>
                    <label for="exp">Date d'expiration </label>
                    <input type="date" name="exp" id="expiration" value="$l->expiration"/>
                    <label for="public_key">Token d'accès public </label>
                    <input type="text" name="public_key" id="public_key" value="$l->public_key" />
                    <input type="hidden" name="auth" id="auth" value="$private_key"/>
                    <button type="submit" name="sendBtn">Sauvegarder</button>
                </form>
            <div>
        </body>
        </html>
        EOD;
    }

    public function render($method){
        switch ($method) {
            case Renderer::SHOW:
                return $this->showList();
            case Renderer::CREATE:
                return $this->createList();
            case Renderer::REQUEST_AUTH:
                return $this->requestAuth();
            case Renderer::EDIT:
                return $this->edit();
            case Renderer::EDIT_ADD_ITEM:
                return $this->addItem();
            default:
                throw new ForbiddenException("Vous n'avez pas accès à cette page");
        }
    }

}
