<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use payFURL\Sdk as PayFurlSDK;

class WC_Payfurl_SDK
{
    private static $instance;

    public static function get_instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init($private_key, $environment, $debug)
    {
        PayFurlSDK\Config::initialise($private_key, $environment, $debug);
    }

    public function get_providers_info($amount, $currency)
    {
        $svc = new PayFurlSDK\Info();

        try {
            return $svc->Providers([
                                       'amount' => $amount,
                                       'currency' => $currency
                                   ]);
        } catch (PayFurlSDK\ResponseException $exception) {
            return [
                'errorMessage' => $exception->getMessage(),
                'errorCode' => $exception->getCode()
            ];
        }

    }

    public function charge_by_token($data)
    {
        $svc = new PayFurlSDK\Charge();
        try {
            return $svc->CreateWithToken($data);
        } catch (PayFurlSDK\ResponseException $exception) {
            return [
                'errorMessage' => $exception->getMessage(),
                'errorCode' => $exception->getCode()
            ];
        }
    }

    public function charge_by_payment_method($data)
    {
        $svc = new PayFurlSDK\Charge();
        try {
            return $svc->CreateWithPaymentMethod($data);
        } catch (PayFurlSDK\ResponseException $exception) {
            return [
                'errorMessage' => $exception->getMessage(),
                'errorCode' => $exception->getCode()
            ];
        }
    }

    public function create_payment_method($token)
    {
        $svc = new PayFurlSDK\PaymentMethod();
        try {
            return $svc->CreateWithToken(['token' => $token]);
        } catch (PayFurlSDK\ResponseException $exception) {
            return [
                'errorMessage' => $exception->getMessage(),
                'errorCode' => $exception->getCode()
            ];
        }
    }
}
