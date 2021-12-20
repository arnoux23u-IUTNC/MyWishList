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
                $reservation_state = "{$lang['list_reserved_by']} $reserved->user_id";
            else
                $reservation_state = "{$lang['item_reserved']}";
        else
            $reservation_state = "{$lang['item_unreserved']}";
        $item_desc = "\t<div>\n\t\t<h2>$i->nom</h2>\n\t\t".(!empty($i->descr) ? "<p>{$lang['description']} : $i->descr</p>\n\t\t" : "").(!empty($i->url) ? "<p>URL : $i->url</p>\n\t\t" : "").(!empty($i->tarif) ? "<p>{$lang['price']} : $i->tarif</p>\n\t\t" : "").(!empty($i->img) && file_exists(__DIR__."\..\..\..\assets\img\items\\$i->img") ? "<img alt='$i->nom' src='/assets/img/items/$i->img'>\n\t\t" : "")."<p>{$lang['item_associated_list']} : $l->titre | $l->description ($l->expiration)</p>\n\t\t"."<p>{$lang['reservation_state']} : $reservation_state</p>\n\t</div>\n</body>";
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
                throw new ForbiddenException("{$lang['exception_page_not_allowed']}");
                break;
        }
        return genererHeader("{$lang['item_edition']} - {$lang['auth']}", ["list.css"]). <<<EOD
            <h2>{$lang['item_editing']}</h2>
            <div>
                <form class='form_container' method="post" action="$from">
                    <label for="private_key">{$lang['private_token_for']} $l->no</label>
                    <input type="password" name="private_key" id="private_key" required />
                    <button type="submit" name="sendBtn">{$lang['validate']}</button>
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
        return genererHeader("{$lang['item_deleting']}", ["list.css"]). <<<EOD
            <h2>{$lang['item_deleting']}</h2>
            <div>
                <form class='form_container' method="post" action="$from">
                    <label for="private_key">{$lang['private_token_for']} $l->no</label>
                    <input type="password" name="private_key" id="private_key" required />
                    <button type="submit" name="sendBtn">{$lang['delete']}</button>
                </form>
            <div>
        </body>
        </html>
        EOD;
    }

    private function confirmDelete(){
        $i = $this->item;
        $private_key = filter_var($this->request->getParsedBodyParam("private_key"), FILTER_SANITIZE_STRING);
        $delete = $this->container->router->pathFor('items_delete_id',['id' => $i->id]);
        $back = $this->container->router->pathFor('lists_show_id',['id' => $i->liste()->first()->no],["public_key" => $this->public_key]);
        return genererHeader("{$lang['item_delete']} $i->id", ["list.css"]). <<<EOD
            <h2>{$lang['item_delete']} $i->id</h2>
            <div>
                <p class="warning">{$lang['item_delete_confirm']} $i->id ?</p>
                <form class='form_container' method="post" action="$delete">
                    <input type="hidden" name="auth" id="auth" value="$private_key"/>
                    <button type="submit" name="sendBtn">{$lang['delete']}</button> 
                </form>
                <a href="$back">{$lang['html_btn_back']}</a>
            <div>
        </body>
        </html>
        EOD;
    }

    private function edit(){
        $i = $this->item;
        //Utilisation de POST plutot que de la requête slim pour éviter de passer des arguments inutiles à la méthode
        $private_key = filter_var($this->request->getParsedBodyParam("private_key"), FILTER_SANITIZE_STRING);
        $routeItemEdit = $this->container->router->pathFor('items_edit_id',["id" => $i->id]);
        return genererHeader("Item $i->id | {$lang['editing']}", ["list.css"]). <<<EOD
            <h2>{$lang['item_edit']} $i->id</h2>
            <div>
                <form onsubmit="return checkForm()" class='form_container' enctype="multipart/form-data" method="post" action="$routeItemEdit">
                    <label for="item_name">{$lang['name']}</label>
                    <input type="text" name="item_name" id="titre" required autofocus value="$i->nom" />
                    <label for="description">{$lang['description']}</label>
                    <textarea name="description" id="description"/>$i->descr</textarea>
                    <label for="price">{$lang['price']}</label>
                    <input type="number" min="0" step="0.01" value="$i->tarif" name="price" id="price" />
                    <label for="url">URL</label>
                    <input type="url" name="url" id="url" value="$i->url" />
                    <div>
                        <label for="link">URL</label>
                        <input type="radio" checked id="link" name="type" value="link">
                        <label for="upload">{$lang['upload']}</label>
                        <input type="radio" id="upload" name="type" value="upload">
                        <input class="invisible" type="file" name="file_img" id="file_img"/>
                        <input type="text" name="url_img" id="url_img" value="$i->img"/>
                        <button type="button" class="sendBtn" id="delete"><img alt="delete" id="delete_img" src="/assets/img/del.png" /></button>
                    </div>                   
                    <input type="hidden" name="auth" id="auth" value="$private_key"/>
                    <button type="submit" name="sendBtn">{$lang['update']}</button>            
                </form>
            <div>
            <script src="/assets/js/form-delete.js"></script>
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
                throw new ForbiddenException($lang['exception_page_not_allowed']);
        }
    }


}