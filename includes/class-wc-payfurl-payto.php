<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Payfurl_PayTo extends WC_Payfurl
{
    public function __construct()
    {
        parent::__construct();
        $this->id = 'payfurl_payto';
        $this->method_title = 'Payfurl PayTo';
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

        $payment_details = [
            'amount' => $order->get_total(),
            'currency' => $order->get_currency(),
            'reference' => 'Order #' . $order->get_id(),
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
            'firstName' => $order->get_billing_first_name(),
            'lastName' => $order->get_billing_last_name(),
            'order' => [
                'orderNumber' => strval($order->get_id()),
                'freightAmount' => $order->get_shipping_total(),
                'items' => [],
            ],
            'address' => [
                'line1' => $order->get_billing_address_1(),
                'line2' => $order->get_billing_address_2(),
                'city' => $order->get_billing_city(),
                'state' => $order->get_billing_state(),
                'postalCode' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country(),
            ]
        ];

        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $payment_details['order']['items'][] = [
                'product_code' => strval($product->get_id()),
                'description' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'amount' => $product->get_price(),
                'taxAmount' => $item->get_total_tax(),
            ];
        }

        if (!$data['payfurl_token']) {
            return $this->return_error($order, 'Token not found');
        }

        $res = WC_Payfurl_SDK::get_instance()->charge_by_token(array_merge([
                                                                               'token' => $data['payfurl_token'],
                                                                           ], $payment_details));
        if ($res['errorMessage']) {
            return $this->return_error($order, $res['errorMessage']);
        }

        return $this->finish_payment($order, $res['chargeId']);
    }
}
