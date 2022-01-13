<?php /** @noinspection PhpUndefinedFieldInspection */

namespace mywishlist\mvc\models;

use mywishlist\mvc\models\Participation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cagnotte Model
 * Inherits from the Model class of Laravel
 * @property int $item_id
 * @property float $montant
 * @property date $expiration
 * @property mixed $item Goes to item() method, eloquent relation
 * @method static where(string $string, string $string1, string $string2) Eloquent method
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc\models
 */
class Cagnotte extends Model
{
    protected $table = 'cagnotte';
    protected $primaryKey = 'item_id';
    public $timestamps = false;
    protected $guarded = [];

    /**
     * Get the associated item of an pot
     * @return BelongsTo item belongsTo relation
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo('\mywishlist\mvc\models\Item', 'item_id');
    }

    /**
     * Check if a pot is expired
     * @return bool true if the pot is expired, false otherwise
     */
    public function isExpired(): bool
    {
        return !empty($this->limite) && $this->limite <= date('Y-m-d');
    }

    /**
     * Get all participants of a pot
     * @return string html code of the participants
     */
    public function participants(): string{
        $html = "";
        foreach (Participation::whereCagnotteItemid($this->item_id)->get() as $participant) {
            $html .= "\t<span class='form-control-label'>$participant->user_email -> $participant->montant €</span>\n\t\t\t\t\t\t\t\t\t\t";
        }
        return $html;
    }

    /**
     * Get total amount of a pot
     * @return float total amount
     */
    public function totalAmount(): float{
        $total = 0;
        foreach (Participation::where('cagnotte_itemid', $this->item_id)->get() as $participant) {
            $total += $participant->montant;
        }
        return $total;
    }

    /**
     * Calculate the remaining amount of a pot
     * @return float remaining amount
     */
    public function reste(): float{
        return $this->montant - $this->totalAmount();
    }

    /**
     * Delete a pot
     * Delete all participations of the pot
     */
    public function remove(): void {
        Participation::where('cagnotte_itemid', $this->item_id)->delete();
        $this->delete();
    }

}