<?php 

namespace IGonics\PayPalIpnLaravel\Facades;

use Illuminate\Support\Facades\Facade;

class IPN extends Facade {

    protected static function getFacadeAccessor() { return 'paypalipn'; }

}