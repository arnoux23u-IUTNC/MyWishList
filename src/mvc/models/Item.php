<?php /** @noinspection PhpUndefinedFieldInspection */

namespace mywishlist\mvc\models;

use stdClass;
use Slim\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item Model
 * Inherits from the Model class of Laravel
 * @property int $id
 * @property int $liste_id
 * @property string $nom
 * @property string $descr
 * @property string $img
 * @property string $url
 * @property float $tarif
 * @property mixed $liste Goes to liste() method, eloquent relation
 * @method static where(string $string, string $string1, string $string2) Eloquent method
 * @method static find(int $item_id) Eloquent method
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\models
 */
class Item extends Model
{
    protected $table = 'item';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $hidden = ['liste', 'liste_id'];
    protected $fillable = ['liste_id', 'nom', 'descr', 'img', 'url', 'tarif'];

    /**
     * Get the associated list of an item
     * @return BelongsTo liste belongsTo relation
     */
    public function liste(): BelongsTo
    {
        return $this->belongsTo('\mywishlist\mvc\models\Liste', 'liste_id');
    }

    /**
     * Internal method for toJSON() method of Slim
     * @param Container $container
     * @param int $access_level
     * @param bool $object if true, returns an object, else a string
     * @return stdClass|string state of the reservation, object or string
     */
    public function getReservationState(Container $container, int $access_level, bool $object = false): stdClass|string
    {
        $reservation = Reservation::find($this->id);
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
        $list = $this->liste;
        $state_item = ($access_level + (empty($reservation) ? 0 : 5)) . ($list->isExpired() ? 1 : 2);
        switch ($state_item) {
            case 101:
            case 102:
            case 1001:
            case 1002:
            case 10001:
            case 10002:
                $reservation_state = $container->lang['item_unreserved'];
                break;
            case 151:
            case 1051:
            case 10051:
            case 10052:
                if ($object) {
                    $reservation_state = new stdClass();
                    $reservation_state->user = ltrim($reservation->getUser(), ' ');
                    $reservation_state->message = $reservation->message;
                } else
                    $reservation_state = $container->lang['list_reserved_by'] . $reservation->getUser() . (empty($reservation->message) ? "" : ' -> ' . $reservation->message);
                break;
            case 152:
                if ($object) {
                    $reservation_state = new stdClass();
                    $reservation_state->user = ltrim($reservation->getUser(), ' ');
                } else
                    $reservation_state = $container->lang['list_reserved_by'] . $reservation->getUser();
                break;
            case 1052:
                if ($object) {
                    $reservation_state = new stdClass();
                    $reservation_state->user = "[HIDDEN UNTIL EXPIRATION]";
                } else
                    $reservation_state = $container->lang['item_reserved'];
                break;
            default:
                $reservation_state = "";
                break;
        }
        return $reservation_state;
    }

}