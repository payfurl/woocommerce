<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Payfurl_Checkout extends WC_Payfurl
{
    public function __construct()
    {
        parent::__construct();
        $this->id = 'payfurl_checkout';
        $this->method_title = 'Payfurl Checkout';
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $data = $this->get_post_data();
//        print_r($data);
//        print_r($order->get_items());
//        die;
//        print_r($data);
//        print_r($order);
//        die;

        if (!$data['payfurl_transaction_id']) {
            wc_add_notice('Transaction ID not found', 'error');
            return;
        }


        return $this->finish_payment($order, $data['payfurl_transaction_id']);
    }

}
