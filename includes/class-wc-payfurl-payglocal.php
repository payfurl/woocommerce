<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Payfurl_PayGLocal extends WC_Payfurl_Checkout
{
    public function __construct()
    {
        parent::__construct();
        $this->id = 'payfurl_payglocal';
        $this->method_title = 'Payfurl PayGlocal';
    }
}
