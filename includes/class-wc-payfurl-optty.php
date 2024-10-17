<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Payfurl_Optty extends WC_Payfurl_Checkout
{
    public function __construct()
    {
        parent::__construct();
        $this->id = 'payfurl_optty';
        $this->method_title = 'Payfurl Optty';
    }
}
