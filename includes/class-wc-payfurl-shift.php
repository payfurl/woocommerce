<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Payfurl_Shift extends WC_Payfurl_Checkout
{
    public function __construct()
    {
        parent::__construct();
        $this->id = 'payfurl_shift';
        $this->method_title = 'Payfurl Shift';
    }
}
