<?php

namespace payFURL\Sdk;

require_once(__DIR__ . '/tools/HttpWrapper.php');
require_once(__DIR__ . '/tools/ArrayTools.php');
require_once(__DIR__ . '/tools/UrlTools.php');
require_once(__DIR__ . '/tools/CaseConverter.php');

/**
 * @copyright PayFURL
 */
class Token
{
    private array $validSearchKeys = ['ProviderId', 'Status', 'AddedAfter', 'AddedBefore', 'Limit', 'SortBy', 'Skip'];

    /**
     * @throws ResponseException
     */
    public function TokeniseCard($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['ProviderId', 'PaymentInformation' => ['CardNumber', 'ExpiryDate', 'Ccv']]);

        $data = [];
        $data['PaymentInformation'] = $this->BuildPaymentInformationJson($params['PaymentInformation'] ?? []);
        $data = array_merge($data, $this->BuildIpInformationJson($params));
        $data = array_merge($data, $this->BuildVaultInformationJson($params));

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/token/card', 'POST', json_encode($data), ['ProviderId' => $params['ProviderId']]);
    }

    public function TokeniseCardLeastCost($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        $sourceParams = ['Amount' => 1, 'Currency' => 1, 'PaymentInformation' => ['CardNumber', 'ExpiryDate', 'Ccv']];
        $data = array_intersect_key($params, $sourceParams);

        $data['PaymentInformation'] = $this->BuildPaymentInformationJson($params['PaymentInformation'] ?? []);
        $data = array_merge($data, $this->BuildIpInformationJson($params));
        $data = array_merge($data, $this->BuildVaultInformationJson($params));

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/token/card/least_cost', 'POST', json_encode($data));
    }


    public function TokenisePayTo($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['PayerName', 'PayerPayIdDetails' => ['PayId', 'PayIdType'], 'Description', 'MaximumAmount', 'ProviderId']);

        $data = $this->BuildPayToAgreementJson($params);

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/token/payto', 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function Single($tokenId)
    {
        $url = '/token/' . urlencode($tokenId);

        return HttpWrapper::CallApi($url, 'GET', '');
    }

    /**
     * @throws ResponseException
     */
    public function Search($parameters)
    {
        $params = CaseConverter::convertKeysToPascalCase($parameters);
        try {
            $url = '/token' . UrlTools::CreateQueryString($parameters, $this->validSearchKeys);
        } catch (\Exception $ex) {
            throw new ResponseException($ex->getMessage(), 0, 0, false);
        }

        return HttpWrapper::CallApi($url, 'GET', '');
    }

    private function BuildPaymentInformationJson($params): array
    {
        $sourceParams = ['CardNumber' => 1, 'ExpiryDate' => 1, 'Ccv' => 1, 'Cardholder' => 1];
        return array_intersect_key($params, $sourceParams);
    }

    private function BuildVaultInformationJson($params): array
    {
        $sourceParams = ['VaultCard' => 1, 'VaultExpireDate' => 1, 'VaultExpireSeconds' => 1];
        return array_intersect_key($params, $sourceParams);
    }

    private function BuildIpInformationJson($params): array
    {
        $sourceParams = ['Ip' => 1];
        return array_intersect_key($params, $sourceParams);
    }

    private function BuildPayToAgreementJson($params)
    {
        $sourceParams = ['PayerName' => 1, 'Description' => 1, 'MaximumAmount' => 1, 'ProviderId' => 1, 'Ip' => 1];
        $data = array_intersect_key($params, $sourceParams);
        if (isset($params['PayerPayIdDetails'])) {
            $detailsParams = ['PayId' => 1, 'PayIdType' => 1];
            $data['PayerPayIdDetails'] = array_intersect_key($params['PayerPayIdDetails'], $detailsParams);
        }
        return $data;
    }
}
