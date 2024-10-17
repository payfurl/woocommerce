<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

define('Payfurl_VERSION', '4.6.13.676');

/**
 * Class for integrating with WooCommerce Blocks
 */
class Payfurl_Blocks_Integration extends AbstractPaymentMethodType
{
    protected $settings;
    protected $gateway;
    static $scripts_loaded = false;

    public function initialize()
    {
        $this->settings = get_option('woocommerce_payfurl_settings', []);
        $this->gateway = new WC_Payfurl();
    }

    public function get_name()
    {
        return 'payfurl';
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }


    public function get_payment_method_script_handles()
    {
        wp_add_inline_script(
            'payfurl_checkout',
            'window._pf = payfurl?.init("' . esc_html($this->gateway->environment) . '","' . esc_html($this->gateway->get_public_key()) . '",' . ($this->gateway->debug == 'no'? 'false' : 'true') . ');',
            'after'
        );

        $this->register_script_with_dependencies('payfurl_checkout', ['payfurl']);

        return ['payfurl_checkout'];
    }


    public function get_payment_method_data()
    {
        return [
            'title' => esc_html($this->gateway->get_option('title', '')),
            'description' => esc_html($this->gateway->get_option('description', '')),
            'providersInfo' => $this->gateway->get_providers_info(WC()->cart? WC()->cart->get_total('') : 0, get_woocommerce_currency()),
            'enable_googlepay' => $this->gateway->enable_googlepay,
            'enable_applepay' => $this->gateway->enable_applepay,
        ];
    }

    protected function register_script_with_dependencies($handle, $dependencies)
    {
        $script_path = '../build/index.js';
        $style_path = '../build/style-index.css';

        $script_url = plugins_url($script_path, __FILE__);
        $style_url = plugins_url($style_path, __FILE__);

        $script_asset_path = dirname(__FILE__) . '../build/index.asset.php';
        $script_asset = file_exists($script_asset_path)
            ? require $script_asset_path
            : array(
                'dependencies' => $dependencies,
                'version' => $this->get_file_version($script_path),
            );

        wp_enqueue_style(
            $handle,
            $style_url,
            [],
            $this->get_file_version($style_path)
        );

        wp_register_script(
            $handle,
            $script_url,
            array_merge($script_asset['dependencies'], $dependencies),
            $script_asset['version'],
            true
        );
        wp_set_script_translations(
            $handle,
            'payfurl',
            'payfurl',
            dirname(__FILE__) . '/languages'
        );
    }

    protected function get_file_version($file)
    {
        if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && file_exists($file)) {
            return filemtime($file);
        }
        return Payfurl_VERSION;
    }
}
