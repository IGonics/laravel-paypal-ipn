<?php namespace IGonics\PayPalIpnLaravel;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

use IGonics\PayPalIpnLaravel\Exception\InvalidIpnException;
use IGonics\PayPalIpnLaravel\Exception\IpnVerificationException;
use IGonics\PayPalIpnLaravel\Models\IpnOrder;
use IGonics\PayPalIpnLaravel\Models\IpnOrderItem;
use IGonics\PayPalIpnLaravel\Models\IpnOrderItemOption;

use Mdb\PayPal\Ipn\Event\MessageInvalidEvent;
use Mdb\PayPal\Ipn\Event\MessageVerificationFailureEvent;
use Mdb\PayPal\Ipn\Event\MessageVerifiedEvent;
use Mdb\PayPal\Ipn\ListenerBuilder\Guzzle\ArrayListenerBuilder as ListenerBuilder;

/**
 * Class PayPalIpn
 * @package IGonics\PayPalIpnLaravel
 *
 * References:
 * https://github.com/mike182uk/paypal-ipn-listener
 * https://github.com/orderly/symfony2-paypal-ipn/blob/master/src/Orderly/PayPalIpnBundle/Ipn.php
 */
class PayPalIpn
{

    public function getListener($data=[], $startListening=true){
         $listenerBuilder = new ListenerBuilder();
         $listenerBuilder->setData($data); 
         if($this->getEnvironment()==='sandbox'){
            $listenerBuilder->useSandbox();
         }
         $listener = $listenerBuilder->build();

         $listener->onInvalid([$this,'onInvalidMessage']);
         
         $listener->onVerified([$this,'onVerifiedMessage']);
         
         $listener->onVerificationFailure([$this,'onVerificationFailure']);
         
         if($startListening == true){
             $listener->listen();
         }

         return $listener;
    }

    public function onInvalidMessage(MessageInvalidEvent $event){
        $ipnMessage = $event->getMessage();
        throw new InvalidIpnException("INVALID Paypal IPN Message. $ipnMessage");
    }

    public function onVerifiedMessage(MessageVerifiedEvent $event){
        $ipnMessage = $event->getMessage();
        return $this->store($ipnMessage);
    }

    public function onVerificationFailure(MessageVerificationFailureEvent $event){
        $error = $event->getError();
        throw new IpnVerificationException("VERIFICATION FAILURE Paypal IPN Message",$error);
    }

    /**
     * Listens for and stores PayPal IPN requests.
     *
     * @return IpnOrder
     * @throws InvalidIpnException
     * @throws UnexpectedResponseBodyException
     * @throws UnexpectedResponseStatusException
     */
    public function getOrder($requestData=[], $startListening=true )
    {
        $this->getListener($requestData,$startListening);
    }

    /**
     * Get the PayPal environment configuration value.
     *
     * @return string
     */
    public function getEnvironment()
    {
        return Config::get('paypal-ipn-laravel::environment', 'production');
    }

    /**
     * Set the PayPal environment runtime configuration value.
     *
     * @param string $environment
     */
    public function setEnvironment($environment)
    {
        Config::set('paypal-ipn-laravel::environment', $environment);
    }

    /**
     * Stores the IPN contents and returns the IpnOrder object.
     *
     * @param array $data
     * @return IpnOrder
     */
    private function store($data)
    {
        if (array_key_exists('txn_id', $data)) {
            $order = IpnOrder::firstOrNew(array('txn_id' => $data['txn_id']));
            $order->fill($data);
        } else {
            $order = new IpnOrder($data);
        }
        $order->full_ipn = json_encode(Input::all());
        $order->save();

        $this->storeOrderItems($order, $data);

        return $order;
    }

    /**
     * Stores the order items from the IPN contents.
     *
     * @param IpnOrder $order
     * @param array $data
     */
    private function storeOrderItems($order, $data)
    {
        $cart = isset($data['num_cart_items']);
        $numItems = (isset($data['num_cart_items'])) ? $data['num_cart_items'] : 1;

        for ($i = 0; $i < $numItems; $i++) {

            $suffix = ($numItems > 1 || $cart) ? ($i + 1) : '';
            $suffixUnderscore = ($numItems > 1 || $cart) ? '_' . $suffix : $suffix;

            $item = new IpnOrderItem();
            if (isset($data['item_name' . $suffix]))
                $item->item_name = $data['item_name' . $suffix];
            if (isset($data['item_number' . $suffix]))
                $item->item_number = $data['item_number' . $suffix];
            if (isset($data['quantity' . $suffix]))
                $item->quantity = $data['quantity' . $suffix];
            if (isset($data['mc_gross' . $suffixUnderscore]))
                $item->mc_gross = $data['mc_gross' . $suffixUnderscore];
            if (isset($data['mc_handling' . $suffix]))
                $item->mc_handling = $data['mc_handling' . $suffix];
            if (isset($data['mc_shipping' . $suffix]))
                $item->mc_shipping = $data['mc_shipping' . $suffix];
            if (isset($data['tax' . $suffix]))
                $item->tax = $data['tax' . $suffix];

            $order->items()->save($item);

            // Set the order item options if any
            // $count = 7 because PayPal allows you to set a maximum of 7 options per item
            // Reference: https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_html_Appx_websitestandard_htmlvariables
            for ($ii = 1, $count = 7; $ii < $count; $ii++) {
                if (isset($data['option_name' . $ii . '_' . $suffix])) {
                    $option = new IpnOrderItemOption();
                    $option->option_name = $data['option_name' . $ii . '_' . $suffix];
                    if (isset($data['option_selection' . $ii . '_' . $suffix])) {
                        $option->option_selection = $data['option_selection' . $ii . '_' . $suffix];
                    }
                    $item->options()->save($option);
                }
            }
        }
    }

}