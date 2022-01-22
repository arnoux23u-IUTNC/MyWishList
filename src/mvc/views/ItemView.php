<?php /** @noinspection PhpUndefinedFieldInspection */

namespace mywishlist\mvc\views;

use mywishlist\exceptions\ForbiddenException;
use Slim\Container;
use Slim\Http\Request;
use JetBrains\PhpStorm\Pure;
use mywishlist\mvc\{Renderer, View};
use mywishlist\mvc\models\{Item, Reservation, User, Cagnotte};

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
        $reserved = Reservation::find($this->item->id);
        $pot = Cagnotte::find($this->item->id);
        if (!empty($reserved)) {
            $reservation_state = match ($this->access_level) {
                Renderer::ADMIN_MODE => $this->container->lang['list_reserved_by'] . $reserved->getUser() . (empty($reserved->message) ? "" : ' -> ' . $reserved->message),
                Renderer::OWNER_MODE => $this->item->liste->isExpired() ? $this->container->lang['list_reserved_by'] . $reserved->getUser() . (empty($reserved->message) ? "" : ' -> ' . $reserved->message) : $this->container->lang['item_reserved'],
                Renderer::OTHER_MODE => $this->container->lang['list_reserved_by'] . $reserved->getUser(),
                default => $this->container->lang['item_unreserved'],
            };
        } else {
            $reservation_state = $this->container->lang['item_unreserved'];
        }
        $popup = match (filter_var($this->request->getQueryParam('state'), FILTER_SANITIZE_STRING) ?? "") {
            "alreadyPot" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['item_already_pot']}</span></div>",
            "noPot" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['item_no_pot']}</span></div>",
            "errPot" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['item_err_pot']}</span></div>",
            default => ""
        };
        $pk = filter_var($this->request->getParsedBodyParam('public_key') ?? "", FILTER_SANITIZE_STRING);
        $liste_info = !empty($this->item->liste) ? (new ListView($this->container, $this->item->liste, $this->request))->render(Renderer::SHOW_FOR_ITEM, $this->access_level) : $this->container->lang['none'];
        $descr_info = $this->item->descr ?? $this->container->lang['none'];
        $url_info = "<a href=\"{$this->item->url}\">{$this->container->lang['link']}</a>" ?? $this->container->lang['none'];
        $tarif_info = $this->item->tarif ?? $this->container->lang['nc'];
        if (!empty($pot->montant)) {
            $collected = $pot->totalAmount();
            $pot_participate = match ($this->access_level) {
                Renderer::ADMIN_MODE, Renderer::OWNER_MODE => "<div class='form-group focused'>\n\t\t\t\t\t\t\t\t\t\t" . $pot->participants() . "</div>",
                Renderer::OTHER_MODE => ($collected < $pot->montant && !$pot->isExpired()) ? "<a href='{$this->container->router->pathFor('items_pot_id', ['id' => $this->item->id, 'action' => 'participate'], ["public_key" => $pk])}' class='btn btn-sm btn-default'>{$this->container->lang['participate_pot']}</a>" : "<a href='#' class='btn btn-sm btn-default disabled'>{$this->container->lang['participate_pot']}</a>",
            };
            $pot_participate .= "\n\t\t\t\t\t\t\t\t\t\t<a href='{$this->container->router->pathFor('items_pot_id', ['id' => $this->item->id, 'action' => 'delete'])}' class='btn btn-sm btn-danger'>{$this->container->lang['delete_pot']}</a>";
            $pot_amount = number_format($collected, 2) . " / $pot->montant €";
            $end_date_pot = $pot->limite ?? $this->container->lang['none'];
            $pot_desc = <<<HTML
            <div class="card-body">
                                                <h5 class="card-title pb-md-4">Cagnotte</h5>
                                                <div class="pl-lg-4">
                                                    <div class="row">
                                                        <div class="col-lg-4">
                                                            <div class="form-group focused">
                                                                <span class="form-control-label">{$this->container->lang['amount']}</span>
                                                                <p class="form-control-label item-info">$pot_amount</p>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-8">
                                                            <div class="form-group focused">
                                                                <span class="form-control-label">{$this->container->lang['end_date']}</span>
                                                                <p class="form-control-label item-info">$end_date_pot</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    $pot_participate
                                                </div>
                                            </div>
            HTML;
        } else {
            $pot_desc = "<a href='{$this->container->router->pathFor('items_pot_id', ['id' => $this->item->id, 'action' => 'create'])}' class='btn btn-sm btn-default'>{$this->container->lang['create_pot']}</a>";
        }
        $img_info = !empty($this->item->img) ? (file_exists($this->container['items_img_dir'] . DIRECTORY_SEPARATOR . "{$this->item->img}") ? "\n\t\t\t\t\t\t\t\t\t<img class='item-img' alt='{$this->item->nom}' src='/assets/img/items/{$this->item->img}'>" : (filter_var($this->item->img, FILTER_VALIDATE_URL) ? "\n\t\t\t\t\t\t\t\t\t<img class='item-img' alt='{$this->item->nom}' src='{$this->item->img}'>" : "")) : "";
        $html = <<<HTML
            <div class="main-content bg-gradient-default fullbg">
                <nav class="navbar navbar-top navbar-expand-md navbar-dark" id="navbar-main">
                    <div class="container-fluid">
                        <a class="h4 mb-0 text-white text-uppercase d-none d-lg-inline-block" href="{$this->container->router->pathFor('home')}"><img alt="logo" class="icon" src="/assets/img/logos/6.png"/>MyWishList</a>
                    </div>
                </nav>
                <div class="container-fluid pt-8 fs">
                    $popup
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
                                <div class="card-body">
                                    <div class="row">
                                        $pot_desc                                   
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
        return genererHeader("{$this->container->lang['item']} {$this->item->id} - MyWishList", ["profile.css"]) . $html;
    }

    /**
     * Display pot creation page
     * @return string
     */
    private function createPot(): string
    {
        $private_key = filter_var($this->request->getParsedBodyParam("private_key") ?? "", FILTER_SANITIZE_STRING);
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
                                <h1 class="text-center text-white">{$this->container->lang['create_pot']}</h1>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 flex mt--7">
                    <div class="fw">
                        <form method="post" action="{$this->container->router->pathFor('items_pot_id', ['id' => $this->item->id, 'action' => 'create'])}">
                            <div class="card bg-secondary shadow">
                                <div class="card-body">
                                    <div class="pl-lg-4">
                                        <div class="row fw">
                                            <div class="form-group focused fw">
                                                <label class="form-control-label" for="amount">{$this->container->lang['amount']}</label>
                                                <input type="number" id="amount" step="0.01" name="amount" max="100000" min="1" class="form-control form-control-alternative" required autofocus>
                                            </div>
                                        </div>
                                        <div class="row fw">
                                            <div class="form-group focused fw">
                                                <label class="form-control-label" for="expiration">{$this->container->lang['end_date']}</label>
                                                <input type="date" id="expiration" name="expiration" class="form-control form-control-alternative"/>
                                            </div>
                                        </div>
                                        <div class="row fw">
                                            <button type="submit" class="btn btn-sm btn-primary" name="sendBtn">{$this->container->lang['create']}</button>
                                            <input type="hidden" name="private_key" value="$private_key"/>
                                            <input type="hidden" name="auth" value="1"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </body>
        </html>
        HTML;
        return genererHeader("{$this->container->lang['create_pot']}", ["profile.css"]) . $html;
    }

    /**
     * Display pot participation page
     * @return string
     */
    private function participatePot(): string
    {
        $pot = Cagnotte::find($this->item->id);
        $email = User::find($_SESSION['USER_ID'] ?? -1)->mail ?? "";
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
                                <h1 class="text-center text-white">{$this->container->lang['item_participate_pot']}</h1>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 flex mt--7">
                    <div class="fw">
                        <form method="post" action="{$this->container->router->pathFor('items_pot_id', ['id' => $this->item->id, 'action' => 'participate'])}">
                            <div class="card bg-secondary shadow">
                                <div class="card-body">
                                    <div class="pl-lg-4">
                                        <div class="row fw">
                                            <div class="form-group focused fw">
                                                <label class="form-control-label" for="amount">{$this->container->lang['amount']}</label>
                                                <input type="number" id="amount" step="0.01" name="amount" max="{$pot->reste()}" min="1" class="form-control form-control-alternative" required autofocus>
                                            </div>
                                        </div>
                                        <div class="row fw">
                                            <div class="form-group focused fw">
                                                <label class="form-control-label" for="email">{$this->container->lang['user_email']}</label>
                                                <input type="email" id="email" name="email" class="form-control form-control-alternative" required value="$email">
                                            </div>
                                        </div>
                                        <div class="row fw">
                                            <button type="submit" class="btn btn-sm btn-primary" name="sendBtn">{$this->container->lang['participate']}</button>
                                            <input type="hidden" name="public_key" value="{$this->request->getQueryParam('public_key', '')}"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </body>
        </html>
        HTML;
        return genererHeader("{$this->container->lang['item_participate_pot']}", ["profile.css"]) . $html;
    }

    /**
     * Display item reservation form
     * @return string html code
     */
    protected function reserve(): string
    {
        $email = User::find($_SESSION['USER_ID'] ?? "")->mail ?? $_COOKIE['user_email'] ?? "";
        $public_key = filter_var($this->request->getQueryParam("public_key", ''), FILTER_SANITIZE_STRING);
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
                                <h1 class="text-center text-white">{$this->container->lang["item_reservation"]} {$this->item->id}</h1>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 flex mt--7">
                    <div class="fw">
                        <form method="post" enctype="multipart/form-data" action="{$this->container->router->pathFor('items_reserve_id', ['id' => $this->item->id], ['public_key' => $public_key])}">
                            <div class="card bg-secondary shadow">
                                <div class="card-body">
                                    <div class="pl-lg-4">
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                                <label class="form-control-label" for="email">{$this->container->lang['user_email']}</label>
                                                <input type="email" id="email" name="email" class="form-control form-control-alternative" value="$email" required autofocus>
                                            </div>
                                        </div>
                                        <div class="row fw">
                                            <div class="form-group focused fw">
                                                <label class="form-control-label" for="message">{$this->container->lang['message']}</label>
                                                <input type="text" name="message" class="form-control form-control-alternative"/>
                                            </div>
                                        </div>
                                        <div class="row fw">
                                            <button type="submit" class="btn btn-sm btn-primary" value="OK" name="sendBtn">{$this->container->lang['reserve']}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </body>
        </html>
        HTML;
        return genererHeader("{$this->container->lang['item']} {$this->item->id} | {$this->container->lang["reservation"]}", ["profile.css", "toggle.css"]) . $html;
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
                                                <textarea id="description" name="description" class="form-control form-control-alternative">{$this->item->descr}</textarea>
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
        </body>
        </html>
        HTML;
        return genererHeader("{$this->container->lang['item']} {$this->item->id} | {$this->container->lang["editing"]}", ["profile.css", "toggle.css"]) . $html;
    }

    /**
     * {@inheritDoc}
     */
    public function render(int $method, int $access_level = Renderer::OTHER_MODE): string
    {
        $this->access_level = $access_level;
        return match ($method) {
            Renderer::RESERVATION_FORM => $this->reserve(),
            Renderer::REQUEST_AUTH => $this->requestAuth($this->item),
            Renderer::POT_CREATE => $this->createPot(),
            Renderer::POT_PARTICIPATE => $this->participatePot(),
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