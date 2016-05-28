<?php namespace IGonics\PayPalIpnLaravel\Models;

use Illuminate\Database\Eloquent\Model;

use IGonics\PayPalIpnLaravel\Models\IpnOrder;
use IGonics\PayPalIpnLaravel\Models\IpnOrderItemOption;

/**
 * An Eloquent Model: 'IGonics\PayPalIpnLaravel\Models\IpnOrderItem'
 *
 * @property integer $id
 * @property integer $ipn_order_id
 * @property string $item_name
 * @property string $item_number
 * @property string $quantity
 * @property float $mc_gross
 * @property float $mc_handling
 * @property float $mc_shipping
 * @property float $tax
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property-read \IGonics\PayPalIpnLaravel\Models\IpnOrder $order
 * @property-read \Illuminate\Database\Eloquent\Collection|\IGonics\PayPalIpnLaravel\Models\IpnOrderItemOption[] $options
 */
class IpnOrderItem extends Model
{

    protected $table = 'ipn_order_items';

    protected $softDelete = true;

    protected $fillable = array('item_name', 'item_number', 'item_number',
        'quantity', 'mc_gross', 'mc_handling', 'mc_shipping', 'tax'
    );

    public function order() {
        return $this->belongsTo(IpnOrder::class);
    }

    public function options() {
        return $this->hasMany(IpnOrderItemOption::class);
    }

}