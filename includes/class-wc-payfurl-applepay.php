<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Payfurl_ApplePay extends WC_Payfurl_Checkout
{
    public function __construct()
    {
        parent::__construct();
        $this->id = 'payfurl_applepay';
        $this->method_title = 'Payfurl ApplePay';
    }
}
