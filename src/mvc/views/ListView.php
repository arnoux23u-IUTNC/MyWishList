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
        $dataHeader = $this->request->getQueryParam('updated') === "true" ? "<div class='popup'>La liste a été mise à jour.</div>" : "";
        $html = genererHeader("Liste $l->no - MyWishList", ["list.css"]) . <<<EOD
        <body>
            <h2>$l->titre</h2>$dataHeader
            <p>Utilisateur associé : $l->user_id</p>
            <p>Description : $l->description</p>
            <p>Date d'expiration : $l->expiration</p>
            <div class='items_list'>
                <div class='box'>
                    <h2>Items de la liste $l->no</h2>
                    <ul>
        EOD;
        foreach ($l->items as $pos => $item) {
            $pos++;
            $reserved = Reserved::find($item->id);
            $item_desc = "<span>$pos</span>$item->nom".(!empty($item->img) && file_exists(__DIR__."\..\..\..\assets\img\items\\$item->img") ? "<img class='list_item_img' alt='$item->nom' src='/assets/img/items/$item->img'>":'');
            if($this->request->getCookieParam("typeUser") === 'participant')
                if(!empty($reserved))
                    $item_res = "<p>Réservé par $reserved->user_id -> $reserved->message</p>";
                else
                    if($l->isExpired())
                        $item_res = "<p><i>Vous ne pouvez pas reserver cet item</i></p>";
                    else
                        $item_res = "<form method='post' action='#'>\n\t\t\t\t\t\t<button class='sendBtn' type='submit' name='sendBtn' title='Envoyer'><img src='/assets/img/checkmark.png'/></button>\n\t\t\t\t\t</form>";
            else
                if(!empty($reserved))
                    if($l->isExpired())
                        $item_res = "<p>Réservé par $reserved->user_id -> $reserved->message</p>";
                    else
                        $item_res = "<p>Item reservé</p>";
                else
                    $item_res = "<p>Item non réservé</p>";
            $routeItemShow = $this->container->router->pathFor("items_show_id", ["id" => $item->id]);
            $html .= <<<EOD
                    
                            <li>
                                <form method="post" action="$routeItemShow">
                                    <input type="hidden" name="public_key" value="$this->public_key" /> 
                                    <input type="hidden" name="liste_id" value="$l->no" /> 
                                    <a onclick="this.parentNode.submit();">$item_desc</a>
                                </form>
                                <div class="reservation_state">$item_res</div>
                            </li>
            EOD;
        }
        $routeListEdit = $this->container->router->pathFor('lists_edit_id',["id" => $l->no]);
        return $html . "\n\t\t\t</ul>\n\t\t</div>\n\t</div>\n\t<a href='$routeListEdit'>Editer la liste</a>\n</body>\n</html>";
    }

    private function createList(){
        $routeCreate = $this->container->router->pathFor('lists_create');
        return genererHeader("Créer une liste", ["list.css"]). <<<EOD
        <body>
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

    private function requestAuth(){
        $l = $this->list;
        $routeListEdit = $this->container->router->pathFor('lists_edit_id',["id" => $l->no]);
        return genererHeader("Edition de liste - Authentification", ["list.css"]). <<<EOD
        <body>
            <h2>Edition de liste</h2>
            <div>
                <form class='form_container' method="post" action="$routeListEdit">
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
        $private_key = $this->request->getParsedBodyParam("private_key");
        $routeListEdit = $this->container->router->pathFor('lists_edit_id',["id" => $this->list->no]);
        return genererHeader("Liste $l->no | Edition", ["list.css"]). <<<EOD
        <body>
            <h2>Edition de la liste $l->no</h2>
            <div>
                <form class='form_container' method="post" action="$routeListEdit">
                    <label for="title">Titre</label>
                    <input type="text" name="titre" id="titre" required value="$l->titre"/>
                    <label for="descr">Description</label>
                    <input type="text" name="descr" id="description" value="$l->description"/>
                    <label for="user">Utilisateur associé</label>
                    <input type="text" name="user" id="user" value="$l->user_id"/>
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
        }
    }

}
