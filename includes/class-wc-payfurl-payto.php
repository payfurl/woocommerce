<?php

class WC_Payfurl_PayTo extends WC_Payfurl_Checkout
{
    public function __construct()
    {
        parent::__construct();
        $this->id = 'payfurl_payto';
        $this->method_title = 'Payfurl PayTo';
    }
}
