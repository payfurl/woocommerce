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
class Subscription
{
    private array $validSearchKeys = [
        'AmountGreaterThan', 'AmountLessThan', 'AddedAfter', 'AddedBefore', 'Currency', 'Status', 'SortBy', 'Limit', 'Skip',
    ];

    /**
     * @throws ResponseException
     */
    public function CreateSubscription($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['PaymentMethodId', 'Amount', 'Currency', 'Interval', 'Retry']);

        $data = $this->BuildCreateSubscriptionJson($params);
        if (isset($params['Webhook'])) {
            $data['Webhook'] = $this->BuildWebhookConfiguration($params['Webhook'] ?? []);
        }
        if (isset($params['EndAfter'])) {
            $data['EndAfter'] = $this->BuildEndAfter($params['EndAfter'] ?? []);
        }
        if (isset($params['Retry'])) {
            $data['Retry'] = $this->BuildRetry($params['Retry'] ?? []);
        }
        if (isset($params['Frequency'])) {
            $data['Frequency'] = $params['Frequency'];
        }
        if (isset($params['StartDate'])) {
            $data['StartDate'] = $params['StartDate'];
        }

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/subscription/payment_method', 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function GetSubscription($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['SubscriptionId']);

        $url = '/subscription/' . urlencode($params['SubscriptionId']);

        return HttpWrapper::CallApi($url, 'GET', '');
    }

    /**
     * @throws ResponseException
     */
    public function DeleteSubscription($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['SubscriptionId']);

        $url = '/subscription/' . urlencode($params['SubscriptionId']);

        return HttpWrapper::CallApi($url, 'DELETE', '');
    }

    /**
     * @throws ResponseException
     */
    public function Search($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        try {
            $url = '/subscription' . UrlTools::CreateQueryString($params, $this->validSearchKeys);
        } catch (Exception $ex) {
            throw new ResponseException($ex->getMessage(), 0, 0, false);
        }

        return HttpWrapper::CallApi($url, 'GET', '');
    }

    private function BuildCreateSubscriptionJson($params): array
    {
        $sourceParams = 
        ['PaymentMethodId' => 1,
        'Amount' => 1,
        'Currency' => 1,
        'Interval' => 1
        ];
        return array_intersect_key($params, $sourceParams);
    }

    private function BuildWebhookConfiguration($params): array
    {
        $sourceParams = ['Url' => 1, 'Authorization' => 1];
        return array_intersect_key($params, $sourceParams);
    }

    private function BuildEndAfter($params): array
    {
        $sourceParams = ['Amount' => 1, 'Count' => 1, 'Date' => 1];
        return array_intersect_key($params, $sourceParams);
    }

    private function BuildRetry($params): array
    {
        $sourceParams = ['Maximum' => 1, 'Interval' => 1, 'Frequency' => 1];
        return array_intersect_key($params, $sourceParams);
    }

}
