<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Payfurl_Pesapal extends WC_Payfurl_Checkout
{
    public function __construct()
    {
        parent::__construct();
        $this->id = 'payfurl_pesapal';
        $this->method_title = 'Payfurl Pesapal';
    }
}
