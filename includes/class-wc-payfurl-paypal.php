<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Payfurl_Paypal extends WC_Payfurl_Checkout
{
    public function __construct()
    {
        parent::__construct();
        $this->id = 'payfurl_paypal';
        $this->method_title = 'Payfurl PayPal';
    }
}
