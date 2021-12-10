<?php

namespace mywishlist\mvc\views;

use \mywishlist\mvc\models\{Item, Reserved};
use \mywishlist\exceptions\{ForbiddenException, CookieNotSetException};
use mywishlist\mvc\Renderer;

class ItemView
{

    private Item $item;
    private string $userMode;

    public function __construct(Item $item, string $mode)
    {
        $this->item = $item;
        $this->userMode = $mode;
    }

    private function showItem()
    {
        $i = $this->item;
        $reserved = Reserved::find($i->id);
        $l = $i->liste ?? null;
        if(!empty($reserved))
            if($l->isExpired() || $this->userMode == "participant")
                $reservation_state = "Réservé par $reserved->user_id";
            else
                $reservation_state = "Item reservé";
        else
            $reservation_state = "Item non réservé";
        $item_desc = "<body>\n\t<div>\n\t\t<h2>$i->nom</h2>\n\t\t".(!empty($i->descr) ? "<p>Description : $i->descr</p>\n\t\t" : "").(!empty($i->url) ? "<p>URL : $i->url</p>\n\t\t" : "").(!empty($i->tarif) ? "<p>Prix : $i->tarif</p>\n\t\t" : "").(!empty($i->img) && file_exists(__DIR__."\..\..\..\assets\img\items\\$i->img") ? "<img alt='$i->nom' src='/assets/img/items/$i->img'>\n\t\t" : "")."<p>Liste associée : $l->titre | $l->description ($l->expiration)</p>\n\t\t"."<p>Etat reservation : $reservation_state</p>\n\t</div>\n</body>";
        return genererHeader("Item $i->id - MyWishList", ["item.css"]).$item_desc;
    }

    public function render($method){
        switch ($method) {
            case Renderer::SHOW:
                return $this->showItem();
        }
    }

}