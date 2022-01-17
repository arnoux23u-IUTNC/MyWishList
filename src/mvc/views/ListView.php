<?php /** @noinspection PhpUndefinedVariableInspection */

/** @noinspection PhpUndefinedFieldInspection */

namespace mywishlist\mvc\views;

use Slim\Container;
use Slim\Http\Request;
use JetBrains\PhpStorm\Pure;
use mywishlist\mvc\{Renderer, View};
use mywishlist\mvc\models\{Liste, Reservation, User};

/**
 * List View
 * Inherits from View
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\views
 */
class ListView extends View
{

    /**
     * @var Liste|null List associated to the view
     */
    private ?Liste $list;


    /**
     * ListView constructor
     * @param Container $c
     * @param Liste|null $list
     * @param Request|null $request
     */
    #[Pure] public function __construct(Container $c, Liste $list = NULL, Request $request = NULL)
    {
        $this->list = $list;
        parent::__construct($c, $request);
    }

    /**
     * Display the list
     * @return string html code
     */
    protected function show(): string
    {
        $l = $this->list;
        $public_key = $this->request->getQueryParam('public_key');
        //$routeAddItem = $this->container->router->pathFor('lists_edit_items_id',['id' => $l->no]);

        //Headers HTML
        $dataHeader = match (filter_var($this->request->getQueryParam('state'), FILTER_SANITIZE_STRING) ?? "") {
            "update" => "<div class='popup fit'><span style='color:black;'>{$this->container->lang['list_updated']}</span></div>",
            "newItem" => "<div class='popup fit'><span style='color:black;'>{$this->container->lang['list_item_added']}</span></div>",
            "modItem" => "<div class='popup fit'><span style='color:black;'>{$this->container->lang['list_item_updated']}</span></div>",
            "delItem" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['list_item_deleted']}</span></div>",
            "resItem" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['list_item_reserved_action']}</span></div>",
            "reserved" => "<div class='popup fit'><span style='color:black;'>{$this->container->lang['item_reserved']}</span></div>",
            "error" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['phtml_error_error']}</span></div>",
            default => ""
        };
        $warnEdit = match (filter_var($this->request->getQueryParam('info'), FILTER_SANITIZE_STRING) ?? "") {
            "ok" => "<div class='popup fit'><span style='color:black;'>{$this->container->lang['image_saved']}</span></div>",
            "typeerr" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_type_error']}</span></div>",
            "sizeerr" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_size_error']}</span></div>",
            "writeerr" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_write_error']}</span></div>",
            "fileexist" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_exists']}</span></div>",
            "error" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['image_error']}</span></div>",
            "errtoken" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['incorrect_token']}</span></div>",
            "errPot" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['item_err_pot']}</span></div>",
            "createdPot" => "<div class='popup fit'><span style='color:black;'>{$this->container->lang['item_created_pot']}</span></div>",
            "deletedPot" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['pot_deleted']}</span></div>",
            "alreadyPot" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['item_already_pot_participate']}</span></div>",
            "potOk" => "<div class='popup fit'><span style='color:black;'>{$this->container->lang['pot_participate_ok']}</span></div>",
            default => ""
        };
        $descr_info = $this->list->description ?? $this->container->lang['none'];
        $expiration_info = !empty($this->list->expiration) ? date_format(date_create($this->list->expiration), "d-m-Y") : $this->container->lang['nc'];
        $associated_user = User::find($this->list->user_id);
        $claim = (!empty($_SESSION['LOGGED_IN']) && !$this->list->isClaimed()) ? "<a href=".$this->container->router->pathFor('lists_claim_id', ['id' => $l->no])." class='btn btn-sm btn-default'>".$this->container->lang['list_claim']."</a>" : "<a href='#' class='btn btn-sm btn-default disabled'>".$this->container->lang['list_claim']."</a>";
        $user_info = empty($associated_user) ? $this->container->lang['nc'] : $associated_user->lastname . ' ' . $associated_user->firstname;
        $items_list = "";
        foreach ($l->items as $pos => $item) {
            $pos++;
            $routeModItem = $this->container->router->pathFor('items_edit_id', ['id' => $item->id]);
            $reserved = Reservation::find($item->id);
            /*Declaration d'une variable qui donnera le resultat suivant
            *  101 : Liste expirée, Item non reservé
            *  1001 : Propriétaire, Liste expirée, Item non reservé
            *  10001 : Admin, Liste expirée, Item non reservé
            *  151 : Liste expirée, Item reservé
            *  1051 : Propriétaire, Liste expirée, Item reservé
            *  10051 : Admin, Liste expirée, Item reservé
            *  102 : Liste non expirée, Item non reservé
            *  1002 : Propriétaire, Liste non expirée, Item non reservé
            *  10002 : Admin, Liste non expirée, Item non reservé
            *  152 : Liste non expirée, Item reservé
            *  1052 : Propriétaire, Liste non expirée, Item reservé
            *  10052 : Admin, Liste non expirée, Item reservé
            */
            $state_item = ($this->access_level + (empty($reserved) ? 0 : 5)) . ($l->isExpired() ? 1 : 2);
            $item_mod = "";
            $item_del = "";
            $item_res = "";
            switch ($state_item) {
                case 101:
                    $reservation_state = $this->container->lang['item_unreserved'];
                    $item_del = "\n\t\t\t\t\t\t\t\t\t\t\t\t<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<a class='pointer' id='popup$item->id' href='#popup'><img alt='delete' src='/assets/img/del.png'/></a>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>";
                    break;
                case 1001:
                    $reservation_state = $this->container->lang['item_unreserved'];
                    $item_del = "\n\t\t\t\t\t\t\t\t\t\t\t\t<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<a class='pointer' id='popup$item->id' href='#popup'><img alt='delete' src='/assets/img/del.png'/></a>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>";
                    break;
                case 10001:
                    $reservation_state = $this->container->lang['item_unreserved'];
                    $item_mod = "\n\t\t\t\t\t\t\t\t\t\t\t\t<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<a href='$routeModItem'><img alt='edit' src='/assets/img/edit.png'/></a>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>";
                    $item_del = "\n\t\t\t\t\t\t\t\t\t\t\t\t<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<a class='pointer' id='popup$item->id' href='#popup'><img alt='delete' src='/assets/img/del.png'/></a>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>";
                    break;
                case 151:
                    $reservation_state = $this->container->lang['list_reserved_by'] . $reserved->getUser() . (empty($reserved->message) ? "" : ' -> ' . $reserved->message);
                    break;
                case 1051:
                    $reservation_state = $this->container->lang['list_reserved_by'] . $reserved->getUser() . (empty($reserved->message) ? "" : ' -> ' . $reserved->message);
                    break;
                case 10051:
                    $reservation_state = $this->container->lang['list_reserved_by'] . $reserved->getUser() . (empty($reserved->message) ? "" : ' -> ' . $reserved->message);
                    $item_mod = "\n\t\t\t\t\t\t\t\t\t\t\t\t<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<a href='$routeModItem'><img alt='edit' src='/assets/img/edit.png'/></a>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>";
                    $item_del = "\n\t\t\t\t\t\t\t\t\t\t\t\t<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<a class='pointer' id='popup$item->id' href='#popup'><img alt='delete' src='/assets/img/del.png'/></a>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>";
                    break;
                case 102:
                    $reservation_state = $this->container->lang['item_unreserved'];
                    $item_res = "<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<form method='post' action='{$this->container->router->pathfor('items_reserve_id', ['id' => $item->id], ['public_key' => $public_key])}'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t<button class='sendBtn' type='submit' name='sendBtn' title='{$this->container->lang['reserve']}'><img src='/assets/img/checkmark.png'/></button>\n\t\t\t\t\t\t\t\t\t\t\t\t\t</form>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>";
                    $item_mod = "\n\t\t\t\t\t\t\t\t\t\t\t\t<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<a href='$routeModItem'><img alt='edit' src='/assets/img/edit.png'/></a>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>";
                    $item_del = "\n\t\t\t\t\t\t\t\t\t\t\t\t<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<a class='pointer' id='popup$item->id' href='#popup'><img alt='delete' src='/assets/img/del.png'/></a>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>";
                    break;
                case 1002:
                    $reservation_state = $this->container->lang['item_unreserved'];
                    $item_mod = "\n\t\t\t\t\t\t\t\t\t\t\t\t<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<a href='$routeModItem'><img alt='edit' src='/assets/img/edit.png'/></a>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>";
                    $item_del = "\n\t\t\t\t\t\t\t\t\t\t\t\t<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<a class='pointer' id='popup$item->id' href='#popup'><img alt='delete' src='/assets/img/del.png'/></a>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>";
                    break;
                case 10002:
                    $reservation_state = $this->container->lang['item_unreserved'];
                    $item_res = "<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<form method='post' action='{$this->container->router->pathfor('items_reserve_id', ['id' => $item->id], ['public_key' => $public_key])}'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t<button class='sendBtn' type='submit' name='sendBtn' title='{$this->container->lang['reserve']}'><img src='/assets/img/checkmark.png'/></button>\n\t\t\t\t\t\t\t\t\t\t\t\t\t</form>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>";
                    $item_mod = "\n\t\t\t\t\t\t\t\t\t\t\t\t<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<a href='$routeModItem'><img alt='edit' src='/assets/img/edit.png'/></a>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>";
                    $item_del = "\n\t\t\t\t\t\t\t\t\t\t\t\t<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<a class='pointer' id='popup$item->id' href='#popup'><img alt='delete' src='/assets/img/del.png'/></a>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>";
                    break;
                case 152:
                    $reservation_state = in_array($l->no, json_decode($_COOKIE['claimed_lists'], true)) ? $this->container->lang['item_reserved'] : $this->container->lang['list_reserved_by'] . $reserved->getUser();
                    break;
                case 1052:
                    $reservation_state = $this->container->lang['item_reserved'];
                    break;
                case 10052:
                    $reservation_state = $this->container->lang['list_reserved_by'] . $reserved->getUser() . (empty($reserved->message) ? "" : ' -> ' . $reserved->message);
                    $item_mod = "\n\t\t\t\t\t\t\t\t\t\t\t\t<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<a href='$routeModItem'><img alt='edit' src='/assets/img/edit.png'/></a>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>";
                    $item_del = "\n\t\t\t\t\t\t\t\t\t\t\t\t<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<a class='pointer' id='popup$item->id' href='#popup'><img alt='delete' src='/assets/img/del.png'/></a>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>";
                    break;
            }

            //$item_mod =  empty($reserved) ? "\n\t\t\t\t\t\t\t\t\t\t\t\t<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<a href='$routeModItem'><img alt='edit' src='/assets/img/edit.png'/></a>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>" : "";
            /*$reservation_state = $this->container->lang['item_unreserved'];
                switch ($this->access_level){
                    case Renderer::ADMIN_MODE:
                        $item_mod = "\n\t\t\t\t\t\t\t\t\t\t\t\t<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<a href='$routeModItem'><img alt='edit' src='/assets/img/edit.png'/></a>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>";
                        $reservation_state = $this->container->lang['list_reserved_by'] . $reserved->user_id .' -> '. $reserved->message;
                        break;
                    case Renderer::OWNER_MODE:
                        
                        $item_mod =  empty($reserved) ? "\n\t\t\t\t\t\t\t\t\t\t\t\t<div class='flex'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t<a href='$routeModItem'><img alt='edit' src='/assets/img/edit.png'/></a>\n\t\t\t\t\t\t\t\t\t\t\t\t</div>" : "";
                    
                        $reservation_state = $this->list->isExpired() ? $this->container->lang['list_reserved_by'] . $reserved->user_id .' -> '. $reserved->message : $this->container->lang['item_reserved'];
                        break;
                    case Renderer::OTHER_MODE:
                        $reservation_state = $this->container->lang['list_reserved_by'] . $reserved->user_id;
                        break;
                }
            }*/
            //$routeDelItem = $this->container->router->pathFor('items_delete_id',['id' => $item->id],["public_key" => $this->public_key]);
            $item_desc = "<span class='pos'>$pos</span>$item->nom" . (!empty($item->img) ? (file_exists($this->container['items_img_dir'].DIRECTORY_SEPARATOR . "$item->img") ? "<img class='list_item_img' alt=\"$item->nom\" src='/assets/img/items/$item->img'>" : (preg_match("/^((https?:\/{2})?(\w[\w\-\/.]+).(jpe?g|png))?$/", $item->img) ? "<img class='list_item_img' alt='$item->nom' src='$item->img'>" : "")) : "");

            // $item_res = !empty($reserved) ? "<p>{$this->container->lang['list_reserved_by']} $reserved->user_id_id -> $reserved->message</p>" : ($l->isExpired() ? "<p><i>{$this->container->lang['reservation_not_possible']}</i></p>" : "\n\t\t\t\t\t\t\t\t\t\t\t\t\t<form method='post' action='{$this->container->router->pathfor('items_reserve_id', ['id' => $item->id], ['public_key' => '{filter_var($this->request->getqueryparam('public_key', ''), filter_sanitize_string))}')}'>\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t<button class='sendBtn' type='submit' name='sendBtn' title='{$this->container->lang['reserve']}'><img src='/assets/img/checkmark.png'/></button>\n\t\t\t\t\t\t\t\t\t\t\t\t\t</form>\n\t\t\t\t\t\t\t\t\t\t\t\t");

            $pk = $this->request->getQueryParam('public_key') ?? $this->request->getParsedBodyParam('public_key') ?? "";
            $routeItemShow = $this->container->router->pathFor("items_show_id", ["id" => $item->id]);
            $items_list .= <<<HTML

                                                        <li>
                                                            <form class='col-2 float-left' method="post" action="$routeItemShow">
                                                                <input type="hidden" name="public_key" value="$pk" /> 
                                                                <input type="hidden" name="liste_id" value="$l->no" /> 
                                                                <a onclick="this.parentNode.submit();">$item_desc</a>
                                                            </form>$item_mod$item_del
                                                            <div class="reservation_state">
                                                                <span>$reservation_state</span>
                                                            </div>
                                                            $item_res
                                                        </li>
            HTML;
        }
        $list_confidentiality = $this->list->isPublic() ? "" : "🔒";
        $html = <<<HTML
            <div class="main-content">
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
                                    <div class="row">
                                        <p class="form-control-label item-info">{$this->container->lang['list_associated_user']} :</p><p class="form-control-label item-info">$user_info</p>
                                    </div>
                                </div>
                                <div class="card-body flex">
                                    $dataHeader
                                    $warnEdit
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-8 order-xl-1">
                            <div class="card bg-secondary shadow">
                                <div class="card-header bg-white border-0">
                                    <div class="row align-items-center">
                                        <div class="col-8">
                                            <h1 class="mb-0">{$this->container->lang['list']} {$this->container->lang['number']} {$this->list->no} $list_confidentiality</h1>
                                        </div>
                                        <div class="col-4 text-right">
                                            <a href="{$this->container->router->pathFor('lists_edit_id', ['id' => $this->list->no])}" class="btn btn-sm btn-default">{$this->container->lang['list_edition']}</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="pl-lg-4">
                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="form-group focused">
                                                    <span class="form-control-label">{$this->container->lang['name']}</span>
                                                    <p class="form-control-label item-info">{$this->list->titre}</p>
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
                                                    <span class="form-control-label">{$this->container->lang['expiration_date']}</span>
                                                    <p class="form-control-label item-info">$expiration_info</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-8">
                                                <div class="box">
                                                    <span class="form-control-label item-info">{$this->container->lang['list_associated_items']}</span>
                                                    <ul>$items_list
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="{$this->container->router->pathFor('lists_edit_items_id', ['id' => $l->no])}" class="btn btn-sm btn-primary">{$this->container->lang['list_add_item']}</a>
                                        $claim
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="popup" class="overlay">
                <div class="popup1">
                    <h2 style="padding-bottom:2vh;">--</h2>
                    <a class="close" href="#">&times;</a>
                    <form method="POST" id='pform' action="#" class="content">
                        <h3>--</h3>
                        <div class="form-group focused">
                            <label class="form-control-label" for="private_key"></label>
                            <div class="pfield"><input type="password" name="private_key" id="private_key" class="form-control form-control-alternative" autofocus /><i data-associated="private_key" class="pwdicon far fa-eye"></i></div>
                        </div>
                        <a href="#" class="btn btn-sm btn-default">{$this->container->lang['html_btn_back']}</a>
                        <button type="submit" name="sendBtn" value="ok" class="btn btn-sm btn-danger">{$this->container->lang['delete']}</button>
                    </form>
                    
                </div>
            </div>
            <script>
                $(".pointer").each(function() {
                    $(this).on('click', function() {
                        const id = $(this).attr("id");
                        const itemid = id.substr(5);
                        //Exceptionnellement : Utilisation de la route directement plutot que du container pour eviter des conflits JS / PHP 
                        $('#pform').attr('action', "/items/"+itemid+"/delete/?public_key=$public_key");
                        let popups = $(".popup1");
                        popups.find('h2').text("{$this->container->lang['item_delete']} "+itemid);
                        popups.find('h3').text("{$this->container->lang['item_delete_confirm']} "+itemid);
                        popups.find('label').text("{$this->container->lang['private_token_for']} "+itemid+" {$this->container->lang['token_onlyifnotlogged']}");
                    });
                });
            </script>
            <script src="/assets/js/password-viewer.js"></script>
        </body>
        </html>
        HTML;
        return genererHeader("{$this->container->lang['list']} {$this->list->no} - MyWishList", ["list.css", "profile.css"]) . $html;
    }

    /**
     * Display list creation page
     * @return string
     */
    private function createList(): string
    {
        $email = User::find($_SESSION['USER_ID'] ?? "")->mail ?? "";
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
                            <h1 class="text-center text-white">{$this->container->lang['phtml_lists_create']}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 flex mt--7">
                <div class="fw">
                    <form method="post" action="{$this->container->router->pathFor('lists_create')}">
                        <div class="card bg-secondary shadow">
                            <div class="card-body">
                                <div class="pl-lg-4">
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="titre">{$this->container->lang['title']}</label>
                                            <input type="text" id="titre" name="titre" class="form-control form-control-alternative" required autofocus>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="description">{$this->container->lang['description']}</label>
                                            <textarea type="text" id="description" name="description" class="form-control form-control-alternative"></textarea>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="expiration">{$this->container->lang['expiration_date']}</label>
                                            <input type="date" id="expiration" name="expiration" class="form-control form-control-alternative"/>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="public_key">{$this->container->lang['public_token']}</label>
                                            <input type="text" id="public_key" name="public_key" class="form-control form-control-alternative"/>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="email">{$this->container->lang['list_owner_email']}</label>
                                            <input type="email" id="email" name="email" class="form-control form-control-alternative" required value="$email">
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <button type="submit" class="btn btn-sm btn-primary" name="sendBtn">{$this->container->lang['create']}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        HTML;
        return genererHeader("{$this->container->lang['phtml_lists_create']}", ["profile.css"]) . $html;
    }

    /**
     * Display add an item to a list
     * @return string
     */
    private function addItem(): string
    {
        $private_key = filter_var($this->request->getParsedBodyParam("private_key"), FILTER_SANITIZE_STRING);
        $l = $this->list;
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
                            <h1 class="text-center text-white">{$this->container->lang["list_add_item"]} | {$this->container->lang['list']} $l->no</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 flex mt--7">
                <div class="fw">
                    <form method="post" onsubmit="return checkForm()" enctype="multipart/form-data" action="{$this->container->router->pathFor('lists_edit_items_id', ['id' => $l->no])}">
                        <div class="card bg-secondary shadow">
                            <div class="card-body">
                                <div class="pl-lg-4">
                                <div class="row fw">
                                    <div class="form-group focused fw">
                                            <label class="form-control-label" for="item_name">{$this->container->lang['name']}</label>
                                            <input type="text" id="item_name" name="item_name" class="form-control form-control-alternative" required autofocus>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="description">{$this->container->lang['description']}</label>
                                            <textarea type="text" id="description" name="description" class="form-control form-control-alternative"></textarea>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="price">{$this->container->lang['price']}</label>
                                            <input type="number" id="price" min="0" step="0.01" name="price" class="form-control form-control-alternative"/>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="url">URL</label>
                                            <input type="url" id="url" name="url" class="form-control form-control-alternative"/>
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
                                                <input type="text" class="form-control form-control-alternative choose" name="url_img" id="url_img"/>
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
                                        <button type="submit" class="btn btn-sm btn-primary" name="sendBtn">{$this->container->lang['list_add_item']}</button>
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
        return genererHeader("{$this->container->lang["list_add_item"]} | {$this->container->lang['list']} $l->no", ["profile.css", "toggle.css"]) . $html;
    }

    /**
     * Display list edition
     * @return string
     */
    protected function edit(): string
    {
        $private_key = filter_var($this->request->getParsedBodyParam("private_key"), FILTER_SANITIZE_STRING);
        $l = $this->list;
        $list_confidentiality = $l->isPublic() ? "<label class='form-control-label' for='public'>{$this->container->lang['public']}</label>\n\t\t\t\t\t\t\t\t\t\t<input type='radio' checked name='conf' id='public' value='1' name='public'>\n\t\t\t\t\t\t\t\t\t\t<label class='form-control-label' for='private'>{$this->container->lang['private']}</label>\n\t\t\t\t\t\t\t\t\t\t<input type='radio' name='conf' id='private' value='0' name='private'>" : "<label class='form-control-label' for='public'>{$this->container->lang['public']}</label>\n\t\t\t\t\t\t\t\t\t\t<input type='radio' name='conf' id='public' value='1' name='public'>\n\t\t\t\t\t\t\t\t\t\t<label class='form-control-label' for='private'>{$this->container->lang['private']}</label>\n\t\t\t\t\t\t\t\t\t\t<input type='radio' checked name='conf' id='private' value='0' name='private'>";
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
                            <h1 class="text-center text-white">{$this->container->lang["list_editing"]} $l->no</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 flex mt--7">
                <div class="fw">
                    <form method="post" action="{$this->container->router->pathFor('lists_edit_id', ['id' => $this->list->no])}">
                        <div class="card bg-secondary shadow">
                            <div class="card-body">
                                <div class="pl-lg-4">
                                <div class="row fw">
                                    <div class="form-group focused fw">
                                            <label class="form-control-label" for="titre">{$this->container->lang['title']}</label>
                                            <input type="text" id="titre" name="titre" class="form-control form-control-alternative" value="$l->titre" required autofocus>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="description">{$this->container->lang['description']}</label>
                                            <textarea type="text" id="description" name="description" class="form-control form-control-alternative">$l->description</textarea>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="expiration">{$this->container->lang['expiration_date']}</label>
                                            <input type="date" id="expiration" name="expiration" value="$l->expiration" class="form-control form-control-alternative"/>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="public_key">{$this->container->lang['public_token']}</label>
                                            <input type="text" id="public_key" name="public_key" value="$l->public_key" class="form-control form-control-alternative"/>
                                            <input type="hidden" id="auth" name="auth" value="1" class="form-control form-control-alternative"/>
                                            <input type="hidden" id="private_key" name="private_key" value="$private_key" class="form-control form-control-alternative"/>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="expiration">{$this->container->lang['list_confidentiality']}</label>
                                            <div>
                                                $list_confidentiality
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <button type="submit" class="btn btn-sm btn-primary" name="sendBtn">{$this->container->lang['list_edition']}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        HTML;
        return genererHeader("{$this->container->lang['list']} $l->no | {$this->container->lang["editing"]}", ["profile.css"]) . $html;
    }

    /**
     * Display the list for an item
     * @return string
     */
    private function showForItem(): string
    {
        return "[" . $this->list->no . "] " . $this->list->titre . " | " . $this->list->description;
    }

    /**
     * Display the list for public lists
     * @return string html code
     */
    private function showForMenu(){
        return "\n\t\t\t<div class='mb-2'>\n\t\t\t\t<a href='{$this->container->router->pathFor('lists_show_id', ['id' => $this->list->no])}'>\n\t\t\t\t\t<div class='mw list form-control form-control-alternative flex flex-row'>\n\t\t\t\t\t\t<span class='mw text-white form-control-label'>{$this->list->titre}</span>\n\t\t\t\t\t</div>\n\t\t\t\t</a>\n\t\t\t</div>";
    }

    /**
     * {@inheritDoc}
     */
    public function render(int $method, int $access_level = Renderer::OTHER_MODE): string
    {
        $this->access_level = $access_level;
        return match ($method) {
            Renderer::SHOW_FOR_ITEM => $this->showForItem(),
            Renderer::SHOW_FOR_MENU => $this->showForMenu(),
            Renderer::CREATE => $this->createList(),
            Renderer::EDIT_ADD_ITEM => $this->addItem(),
            Renderer::REQUEST_AUTH => $this->requestAuth($this->list),
            default => parent::render($method, $access_level),
        };
    }

    /**
     * {@inheritDoc}
     */
    public function encode(int $access_level): string
    {
        $items = $this->list->items->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        foreach ($this->list->items as $item) {
            $item->reservation_state = $item->getReservationState($this->container, $access_level, true);
        }
        $this->list->items = $items;
        $data = $this->list->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return "<pre>" . $data . "</pre>";
    }
}
