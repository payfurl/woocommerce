<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Payfurl_PayByAccount extends WC_Payfurl_Checkout
{
    public function __construct()
    {
        parent::__construct();
        $this->id = 'payfurl_pay_by_account';
        $this->method_title = 'Payfurl Pay By Account';
    }
}
