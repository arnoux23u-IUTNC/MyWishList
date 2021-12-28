<?php

namespace mywishlist\mvc;

use Slim\Container;
use Slim\Http\Request;
use Illuminate\Database\Eloquent\Model;
use \mywishlist\exceptions\ForbiddenException;
use \mywishlist\mvc\Renderer;

abstract class View
{

    protected Request $request;
    protected Container $container;
    protected int $access_level;

    public function __construct(Container $c, Request $request = null)
    {
        $this->container = $c;
        $this->request = $request;
    }

    protected abstract function show();

    protected abstract function edit();

    protected function requestAuth(Model $model){
        $from = $this->request->getRequestTarget();
        switch ($from) {
            case (preg_match('/^\/lists\/[0-9]+\/edit\/items(\/?)/', $from) ? true : false) :
                $from = $this->container->router->pathFor('lists_edit_items_id',['id' => $model->no]);
                break;
            case (preg_match('/^\/lists\/[0-9]+\/edit(\/?)/', $from) ? true : false) :
                $from = $this->container->router->pathFor('lists_edit_id',['id' => $model->no]);
                break;
            case (preg_match('/^\/items\/[0-9]+\/edit(\/?)/', $from) ? true : false) :
                $from = $this->container->router->pathFor('items_edit_id',['id' => $model->id]);
                break;
            default: 
                print_r($from);
                throw new ForbiddenException(message: $this->container->lang['exception_page_not_allowed']);
        }
        $header = match($this->request->getQueryParam('info')){
            "errtoken" => "<div class='popup warning fit'><span style='color:black;'>{$this->container->lang['incorrect_token']}</span></div>",
            default => ""
        };
        if(get_class($model) == Liste::class){
            $title = $this->container->lang['list_editing'];
            $dataModel = $model->no;
        }else{
            $title = $this->container->lang['item_editing'];
            $dataModel = $model->id;
        }
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
                            <h1 class="text-center text-white">$title</h1>
                            $header
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 flex mt--7">
                <div class="fw">
                    <form method="post" action="{$from}">
                        <div class="card bg-secondary shadow">
                            <div class="card-body">
                                <div class="pl-lg-4">
                                    <div class="row fw">
                                        <div class="form-group focused fw">
                                            <label class="form-control-label" for="private_key">{$this->container->lang['private_token_for']} $dataModel</label>
                                            <input type="password" name="private_key" id="private_key" class="form-control form-control-alternative" autofocus required />
                                        </div>
                                    </div>
                                    <div class="row fw">
                                        <button type="submit" class="btn btn-sm btn-primary" name="sendBtn">{$this->container->lang["validate"]}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        HTML;
        return genererHeader("{$this->container->lang['list_editing']} - {$this->container->lang['auth']}", ["profile.css"]).$html;
    }

    public function render(int $method, int $access_level = Renderer::OTHER_MODE){
        $this->access_level = $access_level;
        switch ($method) {
            case Renderer::SHOW:
                return $this->show();
            case Renderer::EDIT:
                return $this->edit();
            default:
                throw new ForbiddenException(message: $this->container->lang['exception_page_not_allowed']);
        }
    }


}