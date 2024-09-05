<?php

namespace payFURL\Sdk;

use Exception;

require_once(__DIR__ . '/tools/HttpWrapper.php');
require_once(__DIR__ . '/tools/ArrayTools.php');
require_once(__DIR__ . '/tools/UrlTools.php');
require_once(__DIR__ . '/tools/CaseConverter.php');

/**
 * @copyright PayFURL
 */
class Charge
{
    private array $validSearchKeys = [
        'Reference', 'ProviderId', 'AmountGreaterThan', 'AmountLessThan', 'Currency',
        'CustomerId', 'Status', 'AddedAfter', 'AddedBefore', 'PaymentMethodId', 'PaymentType',
        'SortBy', 'Limit', 'Skip',
    ];

    /**
     * @throws ResponseException
     */
    public function CreateWithCard($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['Amount', 'ProviderId', 'PaymentInformation' => ['CardNumber', 'ExpiryDate', 'Ccv']]);

        $data = $this->BuildCreateChargeJson($params);

        $data['ProviderId'] = $params['ProviderId'];
        $data['PaymentInformation'] = $this->BuildPaymentInformationJson($params['PaymentInformation'] ?? []);
        if (isset($params['Webhook'])) {
            $data['Webhook'] = $this->BuildWebhookConfiguration($params['Webhook'] ?? []);
        }

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/charge/card', 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function CreateWithCardLeastCost($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['Amount', 'PaymentInformation' => ['CardNumber', 'ExpiryDate', 'Ccv']]);

        $data = $this->BuildCreateChargeJson($params);
        $data['PaymentInformation'] = $this->BuildPaymentInformationJson($params['PaymentInformation'] ?? []);
        if (isset($params['Webhook'])) {
            $data['Webhook'] = $this->BuildWebhookConfiguration($params['Webhook'] ?? []);
        }

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/charge/card/least_cost', 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function CreateWithCustomer($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['Amount', 'CustomerId']);

        $data = $this->BuildCreateChargeJson($params);
        $data['CustomerId'] = $params['CustomerId'];
        if (isset($params['Webhook'])) {
            $data['Webhook'] = $this->BuildWebhookConfiguration($params['Webhook'] ?? []);
        }

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/charge/customer', 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function CreateWithPaymentMethod($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['Amount', 'PaymentMethodId']);

        $data = $this->BuildCreateChargeJson($params);
        $data['PaymentMethodId'] = $params['PaymentMethodId'];
        if (isset($params['Webhook'])) {
            $data['Webhook'] = $this->BuildWebhookConfiguration($params['Webhook'] ?? []);
        }

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/charge/payment_method', 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function CreateWithToken($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['Token']);

        $data = $this->BuildCreateChargeJson($params);
        $data['Token'] = $params['Token'];
        if (isset($params['Webhook'])) {
            $data['Webhook'] = $this->BuildWebhookConfiguration($params['Webhook'] ?? []);
        }

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/charge/token', 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function Single($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['ChargeId']);

        $url = '/charge/' . urlencode($params['ChargeId']);

        return HttpWrapper::CallApi($url, 'GET', '');
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function Refund($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['ChargeId']);

        $queryParams = [];
        if ($params['Amount'] > 0) {
            $queryParams['Amount'] = $params['Amount'];
        }
        if (isset($params['Comment'])) {
            $queryParams['Comment'] = $params['Comment'];
        }

        $url = '/charge/' . urlencode($params['ChargeId']) . UrlTools::CreateQueryString($queryParams);

        return HttpWrapper::CallApi($url, 'DELETE', '');
    }

    /**
     * @throws ResponseException
     */
    public function Search($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        try {
            $url = '/charge' . UrlTools::CreateQueryString($params, $this->validSearchKeys);
        } catch (Exception $ex) {
            throw new ResponseException($ex->getMessage(), 0, 0, false);
        }

        return HttpWrapper::CallApi($url, 'GET', '');
    }

    /**
     * @throws ResponseException
     */
    public function Capture($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['ChargeId']);

        $url = '/charge/' . urlencode($params['ChargeId']);

        $data = [];
        if ($params['Amount'] > 0) {
            $data['Amount'] = $params['Amount'];
        }
        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi($url, 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function Void($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['ChargeId']);

        $url = '/charge/' . urlencode($params['ChargeId']);

        return HttpWrapper::CallApi($url, 'DELETE', '');
    }

    /**
     * @throws ResponseException
     */
    public function CreateWithBankAccount($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['Amount', 'ProviderId', 'BankPaymentInformation' => ['BankCode', 'AccountNumber', 'AccountName']]);

        $data = $this->BuildCreateChargeJson($params);

        $data['ProviderId'] = $params['ProviderId'];
        $data['BankPaymentInformation'] = $this->BuildBankPaymentInformationJson($params['BankPaymentInformation'] ?? []);
        if (isset($params['Webhook'])) {
            $data['Webhook'] = $this->BuildWebhookConfiguration($params['Webhook'] ?? []);
        }

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/charge/bank_account', 'POST', json_encode($data));
    }

    private function BuildCreateChargeJson($params): array
    {
        $sourceParams = ['Amount' => 1, 'Currency' => 1, 'Reference' => 1, 'Capture' => 1, 'Ip' => 1];
        $data = array_intersect_key($params, $sourceParams);

        if (array_key_exists('Address', $params)) {
            $sourceParams = ['Line1' => 1, 'Line2' => 1, 'City' => 1, 'Country' => 1, 'PostalCode' => 1, 'State' => 1];
            $data['Address'] = array_intersect_key($params['Address'], $sourceParams);
        }

        if (array_key_exists('Order', $params)) {
            $sourceParams = ['OrderNumber' => 1, 'FreightAmount' => 1, 'DutyAmount' => 1, 'Country' => 1, 'PostalCode' => 1, 'State' => 1];
            $data['Order'] = array_intersect_key($params['Order'], $sourceParams);
            if (isset($params['Order']['Items'])) {
                $data['Order']['Items'] = array_map(fn($value) => [
                    'ProductCode' => $value['ProductCode'] ?? null,
                    'CommodityCode' => $value['CommodityCode'] ?? null,
                    'Description' => $value['Description'] ?? null,
                    'Quantity' => $value['Quantity'] ?? null,
                    'UnitOfMeasure' => $value['UnitOfMeasure'] ?? null,
                    'Amount' => $value['Amount'] ?? null,
                    'TaxAmount' => $value['TaxAmount'] ?? null,
                ], $params['Order']['Items']);

            }
        }
        if (array_key_exists('CustomerCode', $params)) {
            $data['CustomerCode'] = $params['CustomerCode'];
        }
        if (array_key_exists('InvoiceNumber', $params)) {
            $data['InvoiceNumber'] = $params['InvoiceNumber'];
        }

        if (array_key_exists('Descriptor', $params)) {
            $data['Descriptor'] = $params['Descriptor'];
        }

        if (array_key_exists('ThreeDSNotificationUrl', $params)) {
            $data['ThreeDSNotificationUrl'] = $params['ThreeDSNotificationUrl'];
        }

        if (array_key_exists('FirstName', $params)) {
                $data['FirstName'] = $params['FirstName'];
        }

        if (array_key_exists('LastName', $params)) {
                    $data['LastName'] = $params['LastName'];
        }

        return $data;
    }

    private function BuildPaymentInformationJson($params): array
    {
        $sourceParams = ['CardNumber' => 1, 'ExpiryDate' => 1, 'Ccv' => 1, 'Cardholder' => 1];
        return array_intersect_key($params, $sourceParams);
    }

    private function BuildWebhookConfiguration($params): array
    {
        $sourceParams = ['Url' => 1, 'Authorization' => 1];
        return array_intersect_key($params, $sourceParams);
    }

    private function BuildBankPaymentInformationJson($params): array
    {
        $sourceParams = ['BankCode' => 1, 'AccountNumber' => 1, 'AccountName' => 1];
        return array_intersect_key($params, $sourceParams);
    }
}
