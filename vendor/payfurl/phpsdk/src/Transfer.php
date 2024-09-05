<?php

namespace payFURL\Sdk;

require_once(__DIR__ . '/tools/HttpWrapper.php');
require_once(__DIR__ . '/tools/ArrayTools.php');
require_once(__DIR__ . '/tools/UrlTools.php');
require_once(__DIR__ . '/tools/CaseConverter.php');

/**
 * @copyright PayFURL
 */
class Transfer
{
    private array $validSearchKeys = ['Reference', 'ProviderId', 'Status', 'AddedAfter', 'AddedBefore', 'Limit', 'SortBy', 'Skip'];

    /**
     * @throws ResponseException
     */
    public function Create($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['GroupReference', 'ProviderId', 'ChargeId']);

        $sourceParams = ['GroupReference' => 1, 'ProviderId' => 1, 'ChargeId' => 1];
        $data = array_intersect_key($params, $sourceParams);
        if (isset($params['Transfers'])) {
            $data['Transfers'] = array_map(fn($value) => [
                'Account' => $value['Account'] ?? null,
                'Amount' => $value['Amount'] ?? null,
                'Currency' => $value['Currency'] ?? null,
                'Message' => $value['Message'] ?? null,
                'Reference' => $value['Reference'] ?? null,
            ], $params['Transfers']);
        }

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/transfer', 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function Single($transferId)
    {
        $url = '/transfer/' . urlencode($transferId);

        return HttpWrapper::CallApi($url, 'GET', '');
    }

    /**
     * @throws ResponseException
     */
    public function Search($parameters)
    {
        $params = CaseConverter::convertKeysToPascalCase($parameters);
        try {
            $url = '/transfer' . UrlTools::CreateQueryString($parameters, $this->validSearchKeys);
        } catch (\Exception $ex) {
            throw new ResponseException($ex->getMessage(), 0, 0, false);
        }

        return HttpWrapper::CallApi($url, 'GET', '');
    }
}
