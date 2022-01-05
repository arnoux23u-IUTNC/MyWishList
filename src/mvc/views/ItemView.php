<?php /** @noinspection PhpPossiblePolymorphicInvocationInspection */
/** @noinspection PhpUndefinedVariableInspection */
/** @noinspection PhpUndefinedFieldInspection */

namespace mywishlist\mvc\views;

use mywishlist\exceptions\ForbiddenException;
use Slim\Container;
use Slim\Http\Request;
use JetBrains\PhpStorm\Pure;
use mywishlist\mvc\{Renderer, View};
use mywishlist\mvc\models\{Item, Reserved};

/**
 * Item View
 * Inherits from View
 * @property $public_key
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\views
 */
class ItemView extends View
{

    /**
     * @var Item|null Item associated with the view
     */
    private ?Item $item;

    /**
     * Constructor
     * @param Container $c
     * @param Item|null $item
     * @param Request|null $request
     */
    #[Pure] public function __construct(Container $c, Item $item = NULL, Request $request = null)
    {
        $this->item = $item;
        parent::__construct($c, $request);
    }

    /**
     * Display an item
     * @return string html code
     * @throws ForbiddenException
     */
    protected function show(): string
    {
        $reserved = Reserved::find($this->item->id);
        if (!empty($reserved)) {
            $reservation_state = match ($this->access_level) {
                Renderer::ADMIN_MODE => $this->container->lang['list_reserved_by'] . $reserved->user . ' -> ' . $reserved->message,
                Renderer::OWNER_MODE => $this->item->liste->isExpired() ? $this->container->lang['list_reserved_by'] . $reserved->user . ' -> ' . $reserved->message : $this->container->lang['item_reserved'],
                Renderer::OTHER_MODE => $this->container->lang['list_reserved_by'] . $reserved->user,
                default => $this->container->lang['item_unreserved'],
            };
        } else {
            $reservation_state = $this->container->lang['item_unreserved'];
        }
        $liste_info = !empty($this->item->liste) ? (new ListView($this->container, $this->item->liste, $this->request))->render(Renderer::SHOW_FOR_ITEM, $this->access_level) : $this->container->lang['none'];
        $descr_info = $this->item->descr ?? $this->container->lang['none'];
        $url_info = $this->item->url ?? $this->container->lang['none'];
        $tarif_info = $this->item->tarif ?? $this->container->lang['nc'];
        $img_info = !empty($this->item->img) ? (file_exists(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "img" . DIRECTORY_SEPARATOR . "items" . DIRECTORY_SEPARATOR . "{$this->item->img}") ? "\n\t\t\t\t\t\t\t\t\t<img class='item-img' alt='{$this->item->nom}' src='/assets/img/items/{$this->item->img}'>" : (filter_var($this->item->img, FILTER_VALIDATE_URL) ? "\n\t\t\t\t\t\t\t\t\t<img class='item-img' alt='{$this->item->nom}' src='{$this->item->img}'>" : "")) : "";
        $html = <<<HTML
            <div class="main-content bg-gradient-default fullbg">
                <nav class="navbar navbar-top navbar-expand-md navbar-dark" id="navbar-main">
                    <div class="container-fluid">
                        <a class="h4 mb-0 text-white text-uppercase d-none d-lg-inline-block" href="{$this->container->router->pathFor('home')}"><img alt="logo" class="icon" src="/assets/img/logos/6.png"/>MyWishList</a>
                    </div>
                </nav>
                <div class="container-fluid pt-8 fs">
                    <div class="row">
                        <div class="col-xl-4 order-xl-2">
                            <div class="card bg-secondary shadow">
                                <div class="card-body flex">
                                    <div class="row">$img_info
                                    </div>
                                    <div class="row">
                                        <p class="form-control-label item-info">{$this->container->lang['item_associated_list']} :</p><p class="form-control-label item-info">$liste_info</p>
                                    </div>
                                    <div class="row">
                                        <p class="form-control-label item-info">{$this->container->lang['reservation_state']} :</p><p class="form-control-label item-info">$reservation_state</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-8 order-xl-1">
                            <div class="card bg-secondary shadow">
                                <div class="card-header bg-white border-0">
                                    <div class="row align-items-center">
                                        <div class="col-8">
                                            <h1 class="mb-0">Item {$this->container->lang['number']} {$this->item->id}</h1>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="pl-lg-4">
                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="form-group focused">
                                                    <span class="form-control-label">{$this->container->lang['name']}</span>
                                                    <p class="form-control-label item-info">{$this->item->nom}</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-8">
                                                <div class="form-group focused">
                                                    <span class="form-control-label">{$this->container->lang['description']}</span>
                                                    <p class="form-control-label item-info">$descr_info</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="form-group focused">
                                                    <span class="form-control-label">{$this->container->lang['price']} {$this->container->lang['price_warning']}</span>
                                                    <p class="form-control-label item-info">$tarif_info</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-8">
                                                <div class="form-group focused">
                                                    <span class="form-control-label">URL</span>
                                                    <p class="form-control-label item-info">$url_info</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        HTML;


        /*if(!empty($reserved))
            if(($l->isExpired() || $this->request->getCookieParam('typeUser') == "participant") && !empty($reserved->user_id))
                $reservation_state = "{$this->container->lang['list_reserved_by']} $reserved->user_id";
            else
                $reservation_state = "{$this->container->lang['item_reserved']}";
        else
            $reservation_state = "{$this->container->lang['item_unreserved']}";*/
        //$item_desc = "\t<div>\n\t\t<h2>{$this->item->nom}</h2>\n\t\t".(!empty($this->item->descr) ? "<p>{$this->container->lang['description']} : {$this->item->descr}</p>\n\t\t" : "").(!empty($this->item->url) ? "<p>URL : {$this->item->url}</p>\n\t\t" : "").(!empty($this->item->tarif) ? "<p>{$this->container->lang['price']} : {$this->item->tarif}</p>\n\t\t" : "").(!empty($this->item->img) ? (file_exists(__DIR__."\..\..\..\assets\img\items\\{$this->item->img}") ? "<img alt='{$this->item->nom}' src='/assets/img/items/{$this->item->img}'>\n\t\t" : (filter_var($this->item->img, FILTER_VALIDATE_URL) ? "<img alt='{$this->item->nom}' src='{$this->item->img}'>\n\t\t" : "")) : "")."<p>{$this->container->lang['item_associated_list']} : {!empty($l->titre) ? $l->titre | $l->description ($l->expiration)) : NULL}</p>\n\t\t"."<p>{$this->container->lang['reservation_state']} : $reservation_state</p>\n\t</div>\n</body>";
        return genererHeader("Item {$this->item->id} - MyWishList", ["profile.css"]) . $html;
    }

    /**
     * Display prevent delete item page
     * @return string html code
     */
    private function preventDelete(): string
    {
        $l = $this->item->liste()->first();
        $from = $this->container->router->pathFor('items_delete_id', ['id' => $this->item->id], ["public_key" => $this->public_key]);
        return genererHeader("{$this->container->lang['item_deleting']}", ["list.css"]) . <<<EOD
            <h2>{$this->container->lang['item_deleting']}</h2>
            <div>
                <form class='form_container' method="post" action="$from">
                    <label for="private_key">{$this->container->lang['private_token_for']} $l->no</label>
                    <div class="pfield"><input type="password" name="private_key" id="private_key" required /><i onclick="pwd('private_key', event)" class="pwdicon far fa-eye"></i></div>
                    <button type="submit" name="sendBtn">{$this->container->lang['delete']}</button>
                </form>
            <div>
            <script src="/assets/js/password-viewer.js"></script>
        </body>
        </html>
        EOD;
    }

    /**
     * Display delete item page
     * @return string html code
     */
    private function confirmDelete(): string
    {
        $private_key = filter_var($this->request->getParsedBodyParam("private_key"), FILTER_SANITIZE_STRING);
        $delete = $this->container->router->pathFor('items_delete_id', ['id' => $this->item->id]);
        $back = $this->container->router->pathFor('lists_show_id', ['id' => $this->item->liste()->first()->no], ["public_key" => $this->public_key]);
        return genererHeader("{$this->container->lang['item_delete']} $this->item->id", ["list.css"]) . <<<EOD
            <h2>{$this->container->lang['item_delete']} $this->item->id</h2>
            <div>
                <p class="warning">{$this->container->lang['item_delete_confirm']} $this->item->id ?</p>
                <form class='form_container' method="post" action="$delete">
                    <input type="hidden" name="auth" id="auth" value="$private_key"/>
                    <button type="submit" name="sendBtn">{$this->container->lang['delete']}</button> 
                </form>
                <a href="$back">{$this->container->lang['html_btn_back']}</a>
            <div>
        </body>
        </html>
        EOD;
    }

    /**
     * Display edit item page
     * @return string html code
     */
    protected function edit(): string
    {
        $private_key = filter_var($this->request->getParsedBodyParam("private_key"), FILTER_SANITIZE_STRING);
        $html = <<<HTML
        <div class="main-content">
            <nav class="navbar navbar-top navbar-expand-md navbar-dark" id="navbar-main">
                <div class="container-fluid">
                    <a class="h4 mb-0 text-white text-uppercase d-none d-lg-inline-block" href="{$this->container->router->pathFor('home')}"><img alt="logo" class="icon" src="/assets/img/logos/6.png"/>MyWishList</a>
                </div>
            </nav>
            <div class="header pb-8 pt-5 pt-lg-8 d-flex align-items-center" style="min-height: 300px;  background-size: cover; background-position: center top;">
                <span class="mask bg-gradient-default opacity-8"></span>
                <div class="container-fluid align-items-center">
                    <div class="row">
                        <div class="fw" style="position:relative;">
                            <h1 class="text-center text-white">{$this->container->lang["item_edit"]} {$this->item->id}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 flex mt--7">
                <div class="fw">
                    <form method="post" enctype="multipart/form-data" action="{$this->container->router->pathFor('items_edit_id', ['id' => $this->item->id])}">
                        <div class="card bg-secondary shadow">
                            <div class="card-body">
                                <div class="pl-lg-4">
                                <div class="row fw">
                                    <div class="form-group focused fw">
                                            <label class="form-control-label" for="name">{$this->container->lang['name']}</label>
                                            <input type="text" id="name" name="name" class="form-control form-control-alternative" value="{$this->item->nom}" required autofocus>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="description">{$this->container->lang['description']}</label>
                                            <textarea type="text" id="description" name="description" class="form-control form-control-alternative">{$this->item->descr}</textarea>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                        <label class="form-control-label" for="url">URL</label>
                                            <input type="url" id="url" name="url" value="{$this->item->url}" class="form-control form-control-alternative"/>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="price">{$this->container->lang['price']}</label>
                                            <input type="number" id="price" min="0" step="0.01" name="price" value="{$this->item->tarif}" class="form-control form-control-alternative"/>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw noradio">
                                            <span class="form-control-label">{$this->container->lang['image']}</span>
                                            <div class='file'>
                                                <div class="toggle">
                                                    <input type="radio" value="link" checked id="link" name="type">
                                                    <label for="link">URL</label>
                                                    <input type="radio" value="upload" id="upload" name="type">
                                                    <label for="upload">{$this->container->lang['upload']}</label>
                                                </div>
                                                <input class="invisible" accept="image/*" type="file" name="file_img" id="file_img"/>
                                                <input type="text" value="{$this->item->img}" class="form-control form-control-alternative choose" name="url_img" id="url_img"/>
                                                <button type="button" class="sendBtn" id="delete"><img class="dimg" alt="delete" id="delete_img" src="/assets/img/del.png" /></button>    
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <input type="hidden" id="auth" name="auth" value="1" class="form-control form-control-alternative"/>
                                            <input type="hidden" id="private_key" name="private_key" value="$private_key" class="form-control form-control-alternative"/>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <button type="submit" class="btn btn-sm btn-primary" name="sendBtn">{$this->container->lang['editing']}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script src="/assets/js/form-delete.js"></script>
        HTML;
        return genererHeader("Item {$this->item->id} | {$this->container->lang["editing"]}", ["profile.css", "toggle.css"]) . $html;
    }

    /**
     * {@inheritDoc}
     */
    public function render(int $method, int $access_level = Renderer::OTHER_MODE): string
    {
        $this->access_level = $access_level;
        return match ($method) {
            Renderer::PREVENT_DELETE => $this->preventDelete(),
            Renderer::DELETE => $this->confirmDelete(),
            Renderer::REQUEST_AUTH => $this->requestAuth($this->item),
            default => parent::render($method, $access_level),
        };
    }

    /**
     * {@inheritDoc}
     */
    public function encode(int $access_level): string
    {
        if (!empty($this->item->liste))
            $this->item->reservation_state = $this->item->getReservationState($this->container, $access_level, true);
        $this->item->list_id = $this->item->liste_id;
        $data = $this->item->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        /*$data = $this->list->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        */
        return "<pre>" . $data . "</pre>";
    }
}