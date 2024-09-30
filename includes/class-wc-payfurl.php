<?php
if (!defined('ABSPATH')) {
    exit;
}

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
                'title' => __('Enable/Disable', 'woocommerce-payfurl'),
                'label' => __('Enable Payfurl Gateway', 'woocommerce-payfurl'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'enable_applepay' => array(
                'title' => __('Apple Pay', 'woocommerce-payfurl'),
                'label' => __('Enable Apple Pay', 'woocommerce-payfurl'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'enable_googlepay' => array(
                'title' => __('Google Pay', 'woocommerce-payfurl'),
                'label' => __('Enable Google Pay', 'woocommerce-payfurl'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce-payfurl'),
                'type' => 'text',
                'default' => __('Debit / Credit Card', 'woocommerce-payfurl'),
            ),
            'environment' => array(
                'title' => __('Environment', 'woocommerce-payfurl'),
                'type' => 'select',
                'default' => 'local',
                'options' => array(
                    'local' => __('Local', 'woocommerce-payfurl'),
                    'development' => __('Development', 'woocommerce-payfurl'),
                    'sandbox' => __('Sandbox', 'woocommerce-payfurl'),
                    'production' => __('Production', 'woocommerce-payfurl'),
                ),
                'onchange' => 'document.querySelectorAll(\'[meta=\\\'key\\\']\').forEach(function(el){el.parentElement.parentElement.parentElement.style.display=(event.target.value==el.getAttribute(\'environment\')?\'\':\'none\')});',
            ),
            'local_public_key' => array(
                'title' => 'Local Public Key',
                'type' => 'password',
                'custom_attributes' => array(
                    'meta' => 'key',
                    'environment' => 'local'
                ),
            ),
            'local_private_key' => array(
                'title' => 'Local Private Key',
                'type' => 'password',
                'custom_attributes' => array(
                    'meta' => 'key',
                    'environment' => 'local'
                ),
            ),
            'development_public_key' => array(
                'title' => 'Development Public Key',
                'type' => 'password',
                'custom_attributes' => array(
                    'meta' => 'key',
                    'environment' => 'development'
                ),
            ),
            'development_private_key' => array(
                'title' => 'Development Private Key',
                'type' => 'password',
                'custom_attributes' => array(
                    'meta' => 'key',
                    'environment' => 'development'
                ),
            ),
            'sandbox_public_key' => array(
                'title' => 'Sandbox Public Key',
                'type' => 'password',
                'custom_attributes' => array(
                    'meta' => 'key',
                    'environment' => 'sandbox'
                ),
            ),
            'sandbox_private_key' => array(
                'title' => 'Sandbox Private Key',
                'type' => 'password',
                'custom_attributes' => array(
                    'meta' => 'key',
                    'environment' => 'sandbox'
                ),
            ),
            'production_public_key' => array(
                'title' => 'Production Public Key',
                'type' => 'password',
                'custom_attributes' => array(
                    'meta' => 'key',
                    'environment' => 'production'
                ),
            ),
            'production_private_key' => array(
                'title' => 'Production Private Key',
                'type' => 'password',
                'custom_attributes' => array(
                    'meta' => 'key',
                    'environment' => 'production'
                ),
            ),
            'debug' => array(
                'title' => __('Debug', 'woocommerce-payfurl'),
                'label' => __('Enable Debug Mode', 'woocommerce-payfurl'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
        );
    }


    public function admin_options()
    {
        echo '<table class="form-table">' . $this->generate_settings_html($this->get_form_fields(), false) . '</table>';
        echo '<script>';
        echo 'function payfurl_environment_change() {';
        echo 'var environment = document.getElementById("woocommerce_payfurl_environment").value;';
        echo 'console.log(environment);';
        echo 'document.querySelectorAll(\'[meta=\\\'key\\\']\').forEach(function(el){el.parentElement.parentElement.parentElement.style.display=(environment==el.getAttribute(\'environment\')?\'\':\'none\')});';
        echo '}';
        echo 'setTimeout(() => {';
        echo 'document.getElementById("woocommerce_payfurl_environment").addEventListener("change", payfurl_environment_change);';
        echo 'payfurl_environment_change();';
        echo '}, 1000);';
        echo '</script>';
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
        if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
            return;
        }

        // if our payment gateway is disabled, we do not have to enqueue JS too
        if ('no' === $this->enabled) {
            return;
        }

        wp_enqueue_script('payfurl', 'https://assets.payfurl.com/v4.6.16.742/js/payfurl.js');
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
