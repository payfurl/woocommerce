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
class Customer
{
    private array $ValidSearchKeys = ['Reference', 'Email', 'AddedAfter', 'AddedBefore', 'Search', 'Limit', 'Skip'];

    /**
     * @throws ResponseException
     */
    public function CreateWithCard($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['ProviderId', 'PaymentInformation' => ['CardNumber', 'ExpiryDate', 'Ccv']]);

        $data = $this->BuildCreateCustomerJson($params);
        $data = array_merge($data, $this->BuildVaultInformationJson($params));
        $data = array_merge($data, $this->BuildIpInformationJson($params));
        $data['PaymentInformation'] = $this->BuildPaymentInformationJson($params['PaymentInformation'] ?? []);
        $data['ProviderId'] = $params['ProviderId'];
        if (array_key_exists('SkipExpiryDateValidation', $params)) {
            $data['SkipExpiryDateValidation'] = $params['SkipExpiryDateValidation'];
        }

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/customer/card', 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function CreateWithToken($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['Token']);

        $data = $this->BuildCreateCustomerJson($params);
        $data = array_merge($data, $this->BuildVaultInformationJson($params));
        $data = array_merge($data, $this->BuildIpInformationJson($params));
        $data['Token'] = $params['Token'];

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/customer/token', 'POST', json_encode($data));
    }


    /**
     * @throws ResponseException
     */
    public function CreateWithSingleUseToken($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['ProviderId', 'ProviderToken']);

        $data = [];
        $data['ProviderId'] = $params['ProviderId'];
        $data['ProviderToken'] = $params['ProviderToken'];
        if (array_key_exists("ProviderTokenData", $params)) {
            $data['ProviderTokenData'] = $params['ProviderTokenData'];
        }
        if (array_key_exists("Metadata", $params)) {
            $data['Metadata'] = $params['Metadata'];
        }

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/customer/provider_single_use_token', 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function CreatePaymentMethodWithCard($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['CustomerId', 'ProviderId', 'PaymentInformation' => ['CardNumber', 'ExpiryDate', 'Ccv']]);

        $data = [];
        $data = array_merge($data, $this->BuildVaultInformationJson($params));
        $data = array_merge($data, $this->BuildIpInformationJson($params));
        $data['PaymentInformation'] = $this->BuildPaymentInformationJson($params['PaymentInformation'] ?? []);
        $data['ProviderId'] = $params['ProviderId'];
        if (array_key_exists("SetDefault", $params)) {
            $data['SetDefault'] = $params['SetDefault'];
        }
        if (array_key_exists('SkipExpiryDateValidation', $params)) {
            $data['SkipExpiryDateValidation'] = $params['SkipExpiryDateValidation'];
        }

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/customer/' . urlencode($params['CustomerId']) . '/payment_method/card', 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function CreatePaymentMethodWithToken($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['CustomerId', 'Token']);

        $data = [];
        $data = array_merge($data, $this->BuildIpInformationJson($params));
        $data['Token'] = $params['Token'];
        $data['SetDefault'] = $params['SetDefault'];

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/customer/' . urlencode($params['CustomerId']) . '/payment_method/token', 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function CreateWithProviderToken($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['ProviderId', 'ProviderToken']);

        $data = $this->BuildCreateCustomerJson($params);
        $data = array_merge($data, $this->BuildIpInformationJson($params));
        $data['ProviderId'] = $params['ProviderId'];
        $data['ProviderToken'] = $params['ProviderToken'];
        $data['Verify'] = $params['Verify'] ?? false;
        if (array_key_exists("Metadata", $params)) {
            $data['Metadata'] = $params['Metadata'];
        }

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/customer/provider_token', 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function Single($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['CustomerId']);

        $url = '/customer/' . urlencode($params['CustomerId']);

        return HttpWrapper::CallApi($url, 'GET', '');
    }

    /**
     * @throws ResponseException
     */
    public function Search($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        try {
            $url = '/customer' . UrlTools::CreateQueryString($params, $this->ValidSearchKeys);
        } catch (Exception $ex) {
            throw new ResponseException($ex->getMessage(), 0, 0, false);
        }

        return HttpWrapper::CallApi($url, 'GET', '');
    }

    /**
     * @throws ResponseException
     */
    public function CustomerPaymentMethods($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['CustomerId']);

        $url = '/customer/' . urlencode($params['CustomerId']) . '/payment_method';

        return HttpWrapper::CallApi($url, 'GET', '');
    }

    /**
     * @throws ResponseException
     */
    public function CreateWithPayTo($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['PayToAgreement']);

        $data = $this->BuildCreateCustomerJson($params);
        $data['PayToAgreement'] = $this->BuildPayToAgreementJson($params);

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/customer/payto', 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function CreatePaymentMethodWithPayTo($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['CustomerId', 'PayerName', 'PayerPayIdDetails' => ['PayId', 'PayIdType'], 'Description', 'MaximumAmount', 'ProviderId']);

        $data = $this->BuildPayToAgreementJson($params);

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/customer/' . urlencode($params['CustomerId']) . '/payment_method/payto', 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function RemoveCustomer($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['CustomerId']);

        $url = '/customer/' . urlencode($params['CustomerId']);

        return HttpWrapper::CallApi($url, 'DELETE', '');
    }

    /**
     * @throws ResponseException
     */
    public function UpdateCustomer($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['CustomerId', 'Email']);

        $data = $this->BuildCreateCustomerJson($params);

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/customer/' . urlencode($params['CustomerId']), 'PUT', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function CreateWithBankAccount($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['ProviderId', 'BankPaymentInformation' => ['BankCode', 'AccountNumber', 'AccountName']]);

        $data = $this->BuildCreateCustomerJson($params);
        $data = array_merge($data, $this->BuildIpInformationJson($params));
        $data['BankPaymentInformation'] = $this->BuildBankPaymentInformationJson($params['BankPaymentInformation'] ?? []);
        $data['ProviderId'] = $params['ProviderId'];

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/customer/bank_account', 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function CreatePaymentMethodWithBankAccount($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['CustomerId', 'ProviderId', 'BankPaymentInformation' => ['BankCode', 'AccountNumber', 'AccountName']]);

        $data = $this->BuildCreateCustomerJson($params);
        $data = array_merge($data, $this->BuildIpInformationJson($params));
        $data['BankPaymentInformation'] = $this->BuildBankPaymentInformationJson($params['BankPaymentInformation'] ?? []);
        $data['ProviderId'] = $params['ProviderId'];
        if (array_key_exists("SetDefault", $params)) {
            $data['SetDefault'] = $params['SetDefault'];
        }

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/customer/' . urlencode($params['CustomerId']) . '/bank_account', 'POST', json_encode($data));
    }

    private function BuildCreateCustomerJson($params): array
    {
        $sourceParams = [
            'Reference' => 1,
            'FirstName' => 1,
            'LastName' => 1,
            'Email' => 1,
            'Phone' => 1,
            'DefaultPaymentMethodId' => 1,
        ];
        $data = array_intersect_key($params, $sourceParams);

        if (array_key_exists('Address', $params)) {
            $sourceParams = ['Line1' => 1, 'Line2' => 1, 'City' => 1, 'Country' => 1, 'PostalCode' => 1, 'State' => 1];
            $data['Address'] = array_intersect_key($params, $sourceParams);
        }

        return $data;
    }

    private function BuildPaymentInformationJson($params): array
    {
        $sourceParams = ['CardNumber' => 1, 'ExpiryDate' => 1, 'Ccv' => 1, 'Cardholder' => 1];
        return array_intersect_key($params, $sourceParams);
    }

    private function BuildBankPaymentInformationJson($params): array
    {
        $sourceParams = ['BankCode' => 1, 'AccountNumber' => 1, 'AccountName' => 1];
        return array_intersect_key($params, $sourceParams);
    }

    private function BuildIpInformationJson($params): array
    {
        $sourceParams = ['Ip' => 1];
        return array_intersect_key($params, $sourceParams);
    }

    private function BuildVaultInformationJson($params): array
    {
        $sourceParams = ['VaultCard' => 1, 'VaultExpireDate' => 1, 'VaultExpireSeconds' => 1];
        return array_intersect_key($params, $sourceParams);
    }

    private function BuildPayToAgreementJson($params)
    {
        $sourceParams = ['PayerName' => 1, 'Description' => 1, 'MaximumAmount' => 1, 'ProviderId' => 1, 'Ip' => 1, 'SetDefault' => 1];
        $data = array_intersect_key($params, $sourceParams);
        if (isset($params['PayerPayIdDetails'])) {
            $detailsParams = ['PayId' => 1, 'PayIdType' => 1];
            $data['PayerPayIdDetails'] = array_intersect_key($params['PayerPayIdDetails'], $detailsParams);
        }
        return $data;
    }
}
