<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Payfurl_GooglePay extends WC_Payfurl_Checkout
{
    public function __construct()
    {
        parent::__construct();
        $this->id = 'payfurl_googlepay';
        $this->method_title = 'Payfurl GooglePay';
    }
}
