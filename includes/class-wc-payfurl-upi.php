<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Payfurl_Upi extends WC_Payfurl_Checkout
{
    public function __construct()
    {
        parent::__construct();
        $this->id = 'payfurl_upi';
        $this->method_title = 'Payfurl UPI';
    }
}
