<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/TestConfiguration.php');
require_once(__DIR__ . '/../src/Config.php');
require_once(__DIR__ . '/../src/Charge.php');
require_once(__DIR__ . '/TestBase.php');
require_once(__DIR__ . '/../src/ResponseException.php');

use payFURL\Sdk\Config;
use payFURL\Sdk\Charge;
use payFURL\Sdk\ResponseException;

final class ChargeTest extends TestBase
{
    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testChargeWithCard(): void
    {
        $svc = new Charge();

        $result = $svc->CreateWithCard([
                                           'Amount' => 15.5,
                                           'Currency' => 'AUD',
                                           'Reference' => '123',
                                           'ProviderId' => TestConfiguration::getProviderId(),
                                           'PaymentInformation' => [
                                               'CardNumber' => '4111111111111111',
                                               'ExpiryDate' => '10/30',
                                               'Ccv' => '123',
                                               'Cardholder' => 'Test Cardholder'
                                           ]]);

        $this->assertSame('SUCCESS', $result['status']);
    }

    /**
     * @throws ResponseException
     */
    public function testCreateWithCardLeastCost(): void
    {
        $svc = new Charge();

        $result = $svc->CreateWithCardLeastCost([
                                                    'Amount' => 15.5,
                                                    'Currency' => 'AUD',
                                                    'Reference' => '123',
                                                    'PaymentInformation' => [
                                                        'CardNumber' => '4111111111111111',
                                                        'ExpiryDate' => '10/30',
                                                        'Ccv' => '123',
                                                        'Cardholder' => 'Test Cardholder']]);

        $this->assertSame('SUCCESS', $result['status']);
    }

    /**
     * @throws ResponseException
     */
    public function testWithInvalidProvider(): void
    {
        $svc = new Charge();

        $this->expectException(ResponseException::class);

        $result = $svc->CreateWithCard([
                                           'Amount' => 15.5,
                                           'Currency' => 'AUD',
                                           'Reference' => '123',
                                           'ProviderId' => 'invalid_provider',
                                           'PaymentInformation' => [
                                               'CardNumber' => '4111111111111111',
                                               'ExpiryDate' => '10/30',
                                               'Ccv' => '123',
                                               'Cardholder' => 'Test Cardholder']]);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testWithShortTimeout(): void
    {
        $svc = new Charge();
        $this->expectException(ResponseException::class);
        $this->expectExceptionCode(408);

        $Timeout = Config::$TimeoutMilliseconds = 10;

        Config::$TimeoutMilliseconds = $Timeout;

        $result = $svc->CreateWithCard([
                                           'Amount' => 15.5,
                                           'Currency' => 'AUD',
                                           'Reference' => '123',
                                           'ProviderId' => TestConfiguration::getProviderId(),
                                           'PaymentInformation' => [
                                               'CardNumber' => '4111111111111111',
                                               'ExpiryDate' => '10/30',
                                               'Ccv' => '123',
                                               'Cardholder' => 'Test Cardholder']]);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testSingle(): void
    {
        $svc = new Charge();

        $chargeResult = $svc->CreateWithCard([
                                                 'Amount' => 15.5,
                                                 'Currency' => 'AUD',
                                                 'Reference' => '123',
                                                 'ProviderId' => TestConfiguration::getProviderId(),
                                                 'PaymentInformation' => [
                                                     'CardNumber' => '4111111111111111',
                                                     'ExpiryDate' => '10/30',
                                                     'Ccv' => '123',
                                                     'Cardholder' => 'Test Cardholder']]);

        $singleResult = $svc->Single(['ChargeId' => $chargeResult['chargeId']]);

        $this->assertSame($chargeResult['chargeId'], $singleResult['chargeId']);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testRefund(): void
    {
        $svc = new Charge();

        $chargeResult = $svc->CreateWithCard([
                                                 'Amount' => 15.5,
                                                 'Currency' => 'AUD',
                                                 'Reference' => '123',
                                                 'ProviderId' => TestConfiguration::getProviderId(),
                                                 'PaymentInformation' => [
                                                     'CardNumber' => '4111111111111111',
                                                     'ExpiryDate' => '10/30',
                                                     'Ccv' => '123',
                                                     'Cardholder' => 'Test Cardholder']]);

        $refundResult = $svc->Refund(['ChargeId' => $chargeResult['chargeId'], 'Amount' => 5]);

        $this->assertSame(5, $refundResult['refundedAmount']);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testSearch(): void
    {
        $svc = new Charge();

        $Reference = bin2hex(random_bytes(16));
        $result = $svc->CreateWithCard([
                                           'Amount' => 15.5,
                                           'Currency' => 'AUD',
                                           'Reference' => $Reference,
                                           'ProviderId' => TestConfiguration::getProviderId(),
                                           'PaymentInformation' => [
                                               'CardNumber' => '4111111111111111',
                                               'ExpiryDate' => '10/30',
                                               'Ccv' => '123',
                                               'Cardholder' => 'Test Cardholder']]);

        $searchResult = $svc->Search(array('Reference' => $Reference));

        $this->assertSame(1, $searchResult['count']);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testInvalidParameters(): void
    {
        $svc = new Charge();
        $this->expectException(ResponseException::class);
        // Invalid CCV
        $this->expectExceptionCode('6');

        $result = $svc->CreateWithCard([
            'Amount' => 15.5,
            'Currency' => 'AUD',
            'ProviderId' => TestConfiguration::getProviderId(),
            'PaymentInformation' => [
                'CardNumber' => '4111111111111111',
                'ExpiryDate' => '10/30',
                'Ccv' => 'abcda',
                'Cardholder' => 'Test Cardholder'],
            "Metadata" => [
                "merchant_id" => "12345"]
            ]);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testCreateWithCardWithWebhook(): void
    {
        $svc = new Charge();

        $result = $svc->CreateWithCard([
            'Amount' => 15.5,
            'Currency' => 'AUD',
            'Reference' => '123',
            'ProviderId' => TestConfiguration::getProviderId(),
            'PaymentInformation' => [
                'CardNumber' => '4111111111111111',
                'ExpiryDate' => '10/30',
                'Ccv' => '123',
                'Cardholder' => 'Test Cardholder'
            ],
            'Webhook' => [
                'Url' => 'https://webhook.site/1da8cac9-fef5-47bf-a276-81856f73d7ca',
                'Authorization' => "Basic user:password"
            ]]);

        $this->assertSame('SUCCESS', $result['status']);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testChargeWithBankAccount(): void
    {
        $svc = new Charge();

        $result = $svc->CreateWithBankAccount([
                                           'Amount' => 15.5,
                                           'Currency' => 'AUD',
                                           'Reference' => '123',
                                           'ProviderId' => TestConfiguration::getProviderId(),
                                           'BankPaymentInformation' => [
                                               'BankCode' => '123-456',
                                               'AccountNumber' => '123456',
                                               'AccountName' => 'Bank Account'
                                           ]]);

        $this->assertSame('PENDING', $result['status']);
    }
}
