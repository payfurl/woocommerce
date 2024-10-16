<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Payfurl_PayId extends WC_Payfurl_Checkout
{
    public function __construct()
    {
        parent::__construct();
        $this->id = 'payfurl_azupay';
        $this->method_title = 'Payfurl PayID';
    }
}
