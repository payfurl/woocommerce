<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Payment_Token_Payfurl extends WC_Payment_Token
{
    protected $type = 'payfurl';
    protected $extra_data = array(
        'token_type' => 'CARD',
        'cardholder' => '',
        'bin' => '',
        'last4' => '',
        'expiry_year' => '',
        'expiry_month' => '',
        'card_type' => '',
    );

    protected function get_hook_prefix()
    {
        return 'woocommerce_payment_token_payfurl_get_';
    }

    public function validate()
    {
        if (false === parent::validate()) {
            return false;
        }

        return true;
    }

    public function get_display_name($deprecated = '')
    {
        $display = sprintf(
            '%1$s %2$s...%3$s (expires %4$s/%5$s)',
            wc_get_credit_card_type_label($this->get_card_type()),
            $this->get_bin(),
            $this->get_last4(),
            $this->get_expiry_month(),
            $this->get_expiry_year(),
        );
        if ($this->get_token_type() === 'CARD') {
            return $display;
        }

        return $this->get_token_type();
    }

    public function get_token_type($context = 'view')
    {
        return $this->get_prop('token_type', $context);
    }

    public function set_token_type($type)
    {
        $this->set_prop('token_type', $type);
    }

    public function get_card_type($context = 'view')
    {
        return $this->get_prop('card_type', $context);
    }

    public function set_card_type($type)
    {
        $this->set_prop('card_type', $type);
    }

    public function get_expiry_year($context = 'view')
    {
        return $this->get_prop('expiry_year', $context);
    }

    public function set_expiry_year($year)
    {
        $this->set_prop('expiry_year', $year);
    }

    public function get_expiry_month($context = 'view')
    {
        return $this->get_prop('expiry_month', $context);
    }

    public function set_expiry_month($month)
    {
        $this->set_prop('expiry_month', str_pad($month, 2, '0', STR_PAD_LEFT));
    }

    public function get_last4($context = 'view')
    {
        return $this->get_prop('last4', $context);
    }

    public function set_last4($last4)
    {
        $this->set_prop('last4', $last4);
    }

    public function get_bin($context = 'view')
    {
        return $this->get_prop('bin', $context);
    }

    public function set_bin($bin)
    {
        $this->set_prop('bin', $bin);
    }

    public function get_cardholder($context = 'view')
    {
        return $this->get_prop('cardholder', $context);
    }

    public function set_cardholder($cardholder)
    {
        $this->set_prop('cardholder', $cardholder);
    }
}
