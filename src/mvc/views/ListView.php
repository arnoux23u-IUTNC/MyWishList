<?php

namespace mywishlist\mvc\views;

use \mywishlist\mvc\models\{Liste, Reserved};
use \mywishlist\exceptions\{ForbiddenException, CookieNotSetException};
use mywishlist\mvc\Renderer;

class ListView
{

    private Liste $list;
    private string $token, $userMode;

    public function __construct(Liste $list, string $mode, string $token)
    {
        $this->list = $list;
        $this->userMode = $mode;
        $this->token = $token;
    }

    private function showList()
    {
        $l = $this->list;
        $html = genererHeader("Liste $l->no - MyWishList", ["list.css"]) . <<<EOD
        <body>
            <h2>$l->titre</h2>
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
            if($this->userMode === 'participant')
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
            $html .= <<<EOD
                    
                            <li>
                                <form method="post" action="/items/$item->id">
                                    <input type="hidden" name="token" value="$this->token" /> 
                                    <input type="hidden" name="liste_id" value="$l->no" /> 
                                    <a onclick="this.parentNode.submit();">$item_desc</a>
                                </form>
                                <div class="reservation_state">$item_res</div>
                            </li>
            EOD;
        }
        return $html . "\n\t\t\t</ul>\n\t\t</div>\n\t</div>\n</body>\n</html>";
    }

    public function render($method){
        switch ($method) {
            case Renderer::SHOW:
                return $this->showList();
        }
    }

}
