<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Payfurl_Card extends WC_Payfurl
{
    public function __construct()
    {
        parent::__construct();
        $this->id = 'payfurl_card';
        $this->method_title = 'Payfurl Card';
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

        if ($data['issavedtoken']) {
            $token = WC_Payment_Tokens::get($data['token']);
            $res = WC_Payfurl_SDK::get_instance()->charge_by_payment_method(array_merge([
                                                                       'paymentMethodId' => $token->get_token(),
                                                                   ], $payment_details));
            if ($res['errorMessage']) {
                return $this->return_error($order, $res['errorMessage']);
            }

            return $this->finish_payment($order, $res['chargeId']);
        }

        if ($data['wc-payfurl_card-new-payment-method']) {
            $res = WC_Payfurl_SDK::get_instance()->create_payment_method($data['payfurl_token']);
            if ($res['errorMessage']) {
                return $this->return_error($order, $res['errorMessage']);
            }

            if ($res['paymentMethodId']) {
//                $token = new WC_Payment_Token_Payfurl();
//                $token->set_gateway_id($this->id);
//                $token->set_token($res['paymentMethodId']);
//                $token->set_user_id(get_current_user_id());
//                $token->set_token_type($res['type']);
//                $token->set_bin($res['card']['cardIin']);
//                $token->set_last4(substr($res['card']['cardNumber'], -4));
//                $token->set_card_type($res['card']['type']);
//                $token->set_cardholder($res['card']['cardholder']);
//                $parts = explode('/', $res['card']['expiryDate']);
//                $token->set_expiry_year($parts[1]);
//                $token->set_expiry_month($parts[0]);
//                $token->save();

                $tokenCC = new WC_Payment_Token_CC();
                $tokenCC->set_gateway_id($this->id);
                $tokenCC->set_token($res['paymentMethodId']);
                $tokenCC->set_user_id(get_current_user_id());
                $tokenCC->set_last4(substr($res['card']['cardNumber'], -4));
                $tokenCC->set_card_type($res['card']['type']);
                $parts = explode('/', $res['card']['expiryDate']);
                $tokenCC->set_expiry_year('20' . $parts[1]);
                $tokenCC->set_expiry_month($parts[0]);
                $tokenCC->save();
            }

            $res = WC_Payfurl_SDK::get_instance()->charge_by_payment_method(array_merge([
                                                                       'paymentMethodId' => $res['paymentMethodId'],
                                                                   ], $payment_details));
            if ($res['errorMessage']) {
                return $this->return_error($order, $res['errorMessage']);
            }
        }
        else {

            $res = WC_Payfurl_SDK::get_instance()->charge_by_token(array_merge([
                                                                       'token' => $data['payfurl_token'],
                                                                   ], $payment_details));
            if ($res['errorMessage']) {
                return $this->return_error($order, $res['errorMessage']);
            }
        }

        return $this->finish_payment($order, $res['chargeId']);
    }

    public function process_refund($order_id, $amount = null, $reason = '')
    {
        return false;
    }
}
