<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// https://woocommerce.github.io/code-reference/files/woocommerce-includes-abstracts-abstract-wc-payment-gateway.html
class WC_Payfurl extends WC_Payment_Gateway
{
    public function __construct()
    {

        $this->id = 'payfurl';
        $this->icon = '';
        $this->has_fields = true;
        $this->method_title = 'Payfurl';
        $this->method_description = 'Payfurl Payment Orchestrator';

        $this->supports = [
            'products',
            'refunds',
            'tokenization',
            'add_payment_method',
        ];

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->debug = $this->get_option('debug');
        $this->enable_applepay = $this->get_option('enable_applepay');
        $this->enable_googlepay = $this->get_option('enable_googlepay');
        $this->environment = $this->get_option('environment');
        $this->local_public_key = $this->get_option('local_public_key');
        $this->local_private_key = $this->get_option('local_private_key');
        $this->development_public_key = $this->get_option('development_public_key');
        $this->development_private_key = $this->get_option('development_private_key');
        $this->sandbox_public_key = $this->get_option('sandbox_public_key');
        $this->sandbox_private_key = $this->get_option('sandbox_private_key');
        $this->production_public_key = $this->get_option('production_public_key');
        $this->production_private_key = $this->get_option('production_private_key');

        add_action('woocommerce_update_options_payment_gateways_payfurl', [$this, 'process_admin_options']);
        add_action('wp_enqueue_scripts', [$this, 'register_scripts']);
        $this->init_payfurl_sdk();
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable Payfurl Gateway',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'enable_applepay' => array(
                'title' => 'Apple Pay',
                'label' => 'Enable Apple Pay',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'enable_googlepay' => array(
                'title' => 'Google Pay',
                'label' => 'Enable Google Pay',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'default' => 'Debit / Credit Card',
            ),
            'environment' => array(
                'title' => 'Environment',
                'type' => 'select',
                'default' => 'sandbox',
                'options' => array(
                    'sandbox' => 'Sandbox',
                    'production' => 'Production',
                ),
            ),
            'sandbox_public_key' => array(
                'title' => 'Sandbox Public Key',
                'type' => 'password',
            ),
            'sandbox_private_key' => array(
                'title' => 'Sandbox Private Key',
                'type' => 'password',
            ),
            'production_public_key' => array(
                'title' => 'Production Public Key',
                'type' => 'password',
            ),
            'production_private_key' => array(
                'title' => 'Production Private Key',
                'type' => 'password',
            ),
            'debug' => array(
                'title' => 'Debug',
                'label' => 'Enable Debug Mode',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
        );
    }

    public function get_public_key()
    {
        switch ($this->environment) {
            case 'local':
                return $this->local_public_key;
            case 'development':
                return $this->development_public_key;
            case 'sandbox':
                return $this->sandbox_public_key;
            case 'production':
                return $this->production_public_key;
        }
    }

    public function get_private_key()
    {
        switch ($this->environment) {
            case 'local':
                return $this->local_private_key;
            case 'development':
                return $this->development_private_key;
            case 'sandbox':
                return $this->sandbox_private_key;
            case 'production':
                return $this->production_private_key;
        }
    }

    public function register_scripts()
    {

        // we need JavaScript to process a token only on cart/checkout pages, right?
        if (!is_cart() && !is_checkout()) {
            return;
        }

        // if our payment gateway is disabled, we do not have to enqueue JS too
        if ('no' === $this->enabled) {
            return;
        }

        wp_enqueue_script('payfurl', 'https://assets.payfurl.com/v4.6.16.742/js/payfurl.js', [], 'v4.6.16.742', ['in_footer' => false]);
    }

    public function get_providers_info($amount, $currency)
    {
        return WC_Payfurl_SDK::get_instance()->get_providers_info($amount, $currency);
    }

    private function init_payfurl_sdk()
    {
        WC_Payfurl_SDK::get_instance()->init($this->get_private_key(), $this->environment, $this->debug === 'on');
    }

    protected function finish_payment($order, $transactionId): array
    {
        $order->update_meta_data('PayfurlTransactionId', $transactionId);
        $order->payment_complete();
        $order->reduce_order_stock();
        WC()->cart->empty_cart();
        return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }

    protected function return_error($order, $error_message)
    {
        $logger = wc_get_logger();
        $logger->error($error_message, ['source' => $this->id]);

        wc_add_notice($error_message, 'error');
        $order->update_status('failed', $error_message);
        return ['result' => 'failure', 'message' => $error_message];
    }
}
