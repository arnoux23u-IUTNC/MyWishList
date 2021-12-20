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
            "update" => "<div class='popup'>{$this->container->lang['list_updated']}</div>",
            "newItem"  => "<div class='popup'>{$this->container->lang['list_item_added']}</div>",
            "modItem"  => "<div class='popup'>{$this->container->lang['list_item_updated']}</div>",
            "delItem"  => "<div class='popup warning'>{$this->container->lang['list_item_deleted']}</div>",
            default => ""
        };    
        $warnEdit = "\n\t".match(filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? ""){
            "ok" => "<div class='popup'>{$this->container->lang['image_saved']}</div>",
            "typeerr"  => "<div class='popup warning'>{$this->container->lang['image_type_error']}</div>",
            "sizeerr"  => "<div class='popup warning'>{$this->container->lang['image_size_error']}</div>",
            "writeerr"  => "<div class='popup warning'>{$this->container->lang['image_write_error']}</div>",
            "fileexist"  => "<div class='popup warning'>{$this->container->lang['image_exists']}</div>",
            "error"  => "<div class='popup warning'>{$this->container->lang['image_error']}</div>",
            default => ""
        };        
        $html = genererHeader("{$this->container->lang['list']} $l->no - MyWishList", ["list.css"]) . <<<EOD
            <h2>$l->titre</h2>$dataHeader$warnEdit
            <p>{$this->container->lang['list_associated_user']} : $l->user_id</p>
            <p>{$this->container->lang['description']} : $l->description</p>
            <p>{$this->container->lang['expiration_date']} : $l->expiration</p>
            <a href="$routeAddItem">{$this->container->lang['list_add_item']}</a>
            <div class='items_list'>
                <div class='box'>
                    <h2>{$this->container->lang['list_associated_items']} $l->no</h2>
                    <ul>
        EOD;
        foreach ($l->items as $pos => $item) {
            $pos++;
            $reserved = Reserved::find($item->id);
            $routeModItem = $this->container->router->pathFor('items_edit_id',['id' => $item->id]);
            $routeDelItem = $this->container->router->pathFor('items_delete_id',['id' => $item->id],["public_key" => $this->public_key]);
            $item_desc = "<span>$pos</span>$item->nom".(!empty($item->img) ? (file_exists(__DIR__."\..\..\..\assets\img\items\\$item->img") ? "<img class='list_item_img' alt='$item->nom' src='/assets/img/items/$item->img'>" : (preg_match("/^((https?:\/{2})?(\w[\w\-\/\.]+).(jpe?g|png))?$/",$item->img) ? "<img class='list_item_img' alt='$item->nom' src='$item->img'>" : "")):"");
            $item_res = ($this->request->getCookieParam("typeUser") === 'participant') ? (!empty($reserved) ? "<p>{$this->container->lang['list_reserved_by']} $reserved->user_id -> $reserved->message</p>" : ($l->isExpired() ? "<p><i>{$this->container->lang['reservation_not_possible']}</i></p>" : "\n\t\t\t\t\t\t<form method='post' action='#'>\n\t\t\t\t\t\t\t<button class='sendBtn' type='submit' name='sendBtn' title='{$this->container->lang['reserve']}'><img src='/assets/img/checkmark.png'/></button>\n\t\t\t\t\t\t</form>\n\t\t\t\t\t")) : (!empty($reserved) ? ($l->isExpired() ? "<p>{$this->container->lang['list_reserved_by']} $reserved->user_id -> $reserved->message</p>" : "<p>{$this->container->lang['item_reserved']}</p>") : "<p>{$this->container->lang['item_unreserved']}</p>");
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
        return $html . "\n\t\t\t</ul>\n\t\t</div>\n\t</div>\n\t<a href='$routeListEdit'>{$this->container->lang['list_edition']}</a>\n</body>\n</html>";
    }

    private function createList(){
        $routeCreate = $this->container->router->pathFor('lists_create');
        return genererHeader("{$this->container->lang['phtml_lists_create']}", ["list.css"]). <<<EOD
            <h2>{$this->container->lang['phtml_lists_create']}</h2>
            <div>
                <form class='form_container' method="post" action="$routeCreate">
                    <label for="titre">{$this->container->lang['title']}</label>
                    <input type="text" name="titre" id="titre" required autofocus />
                    <label for="description">{$this->container->lang['description']}</label>
                    <textarea name="description" id="description"/></textarea>
                    <label for="exp">{$this->container->lang['expiration_date']}</label>
                    <input type="date" name="exp" id="expiration"/>
                    <label for="public_key">{$this->container->lang['public_token']}</label>
                    <input type="text" name="public_key" id="public_key" />
                    <button type="submit" name="sendBtn">{$this->container->lang['create']}</button>
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
        return genererHeader("Items | {$this->container->lang['list']} $l->no", ["list.css"]). <<<EOD
            <h2>{$this->container->lang['list_add_item']} $l->no</h2>
            <div>
                <form class='form_container' method="post" action="$routeAddItem">
                    <label for="item_name">{$this->container->lang['name']}</label>
                    <input type="text" name="item_name" id="titre" required autofocus />
                    <label for="description">{$this->container->lang['description']}</label>
                    <textarea name="description" id="description"/></textarea>
                    <label for="price">{$this->container->lang['price']}</label>
                    <input type="number" min="0" step="0.01" name="price" id="price" />
                    <label for="url">URL</label>
                    <input type="url" name="url" id="url" />
                    <input type="hidden" name="auth" id="auth" value="$private_key"/>
                    <button type="submit" name="sendBtn">{$this->container->lang['create']}</button>
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
                throw new ForbiddenException($this->container->lang['exception_page_not_allowed']);
                break;
        }
        return genererHeader("{$this->container->lang['list_editing']} - {$this->container->lang['auth']}", ["list.css"]). <<<EOD
            <h2>{$this->container->lang['list_editing']}</h2>
            <div>
                <form class='form_container' method="post" action="$from">
                    <label for="private_key">{$this->container->lang['private_token_for']} $l->no</label>
                    <input type="password" name="private_key" id="private_key" required />
                    <button type="submit" name="sendBtn">{$this->container->lang["create"]}</button>
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
        return genererHeader("{$this->container->lang['list']} $l->no | {$this->container->lang["editing"]}", ["list.css"]). <<<EOD
            <h2>{$this->container->lang["liste_editing"]} $l->no</h2>
            <div>
                <form class='form_container' method="post" action="$routeListEdit">
                    <label for="title">{$this->container->lang['title']}</label>
                    <input type="text" name="titre" id="titre" required value="$l->titre"/>
                    <label for="descr">{$this->container->lang['description']}</label>
                    <input type="text" name="descr" id="description" value="$l->description"/>
                    <label for="user">{$this->container->lang['list_associated_user']}</label>
                    <input type="number" name="user" id="user" value="$l->user_id"/>
                    <label for="exp">{$this->container->lang['expiration_date']}</label>
                    <input type="date" name="exp" id="expiration" value="$l->expiration"/>
                    <label for="public_key">{$this->container->lang['public_token']}</label>
                    <input type="text" name="public_key" id="public_key" value="$l->public_key" />
                    <input type="hidden" name="auth" id="auth" value="$private_key"/>
                    <button type="submit" name="sendBtn">{$this->container->lang['update']}</button>
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
                throw new ForbiddenException($this->container->lang['exception_page_not_allowed']);
        }
    }

}
