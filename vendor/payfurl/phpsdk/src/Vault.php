<?php
namespace payFURL\Sdk;

require_once(__DIR__ . '/tools/HttpWrapper.php');
require_once(__DIR__ . '/tools/ArrayTools.php');
require_once(__DIR__ . '/tools/UrlTools.php');
require_once(__DIR__ . '/tools/CaseConverter.php');

/**
 * @copyright PayFURL
 */
class Vault
{
    /**
     * @throws ResponseException
     */
    public function Create($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, array('CardNumber'));

        $sourceParams = ['CardNumber' => 1, 'Ccv' => 1, 'ExpireDate' => 1, 'ExpireSeconds' => 1];
        $data = array_intersect_key($params, $sourceParams);

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/vault', 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function Single($vaultId)
    {
        $url = '/vault/' . urlencode($vaultId);

        return HttpWrapper::CallApi($url, 'GET', '');
    }

    /**
     * @throws ResponseException
     */
    public function Delete($vaultId)
    {
        $url = '/vault/' . urlencode($vaultId);

        return HttpWrapper::CallApi($url, 'DELETE', '');
    }

}
