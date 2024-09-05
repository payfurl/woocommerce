<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/TestConfiguration.php');
require_once(__DIR__ . '/../src/Config.php');
require_once(__DIR__ . '/../src/PaymentMethod.php');
require_once(__DIR__ . '/../src/Customer.php');
require_once(__DIR__ . '/TestBase.php');
require_once(__DIR__ . '/../src/ResponseException.php');

use payFURL\Sdk\Config;
use payFURL\Sdk\PaymentMethod;
use payFURL\Sdk\Customer;
use payFURL\Sdk\ResponseException;

final class PaymentMethodTest extends TestBase
{
    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testSearch(): void
    {
        $customerSvc = new Customer();

        $customerResult = $customerSvc->CreateWithCard([
                                                           'ProviderId' => TestConfiguration::getProviderId(),
                                                           'PaymentInformation' => [
                                                               'CardNumber' => '4111111111111111',
                                                               'ExpiryDate' => '10/30',
                                                               'Ccv' => '123',
                                                               'Cardholder' => 'Test Cardholder'],
                                                           "Metadata" => [
                                                               "merchant_id" => "12345"]
                                                       ]);

        $svc = new PaymentMethod();

        $result = $svc->Search(['customerId' => $customerResult['customerId']]);

        $this->assertEquals(1, $result['count']);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testSingle(): void
    {
        $customerSvc = new Customer();

        $customerResult = $customerSvc->CreateWithCard([
                                                           'ProviderId' => TestConfiguration::getProviderId(),
                                                           'PaymentInformation' => [
                                                               'CardNumber' => '4111111111111111',
                                                               'ExpiryDate' => '10/30',
                                                               'Ccv' => '123',
                                                               'Cardholder' => 'Test Cardholder']]);

        $svc = new PaymentMethod();

        $result = $svc->Single(['PaymentMethodId' => $customerResult['defaultPaymentMethod']['paymentMethodId']]);

        $this->assertIsString($result['paymentMethodId']);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testCreatePaymentMethodWithCard(): void
    {
        $paymentMethodSvc = new PaymentMethod();

        $paymentMethodResult = $paymentMethodSvc->CreatePaymentMethodWithCard([
                                                                                  'ProviderId' => TestConfiguration::getProviderId(),
                                                                                  'PaymentInformation' => [
                                                                                      'CardNumber' => '4111111111111111',
                                                                                      'ExpiryDate' => '10/30',
                                                                                      'Ccv' => '123',
                                                                                      'Cardholder' => 'Test Cardholder']]);

        $this->assertIsString($paymentMethodResult['paymentMethodId']);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testCreatePaymentMethodWithVault(): void
    {
        $paymentMethodSvc = new PaymentMethod();

        $paymentMethodResult = $paymentMethodSvc->CreatePaymentMethodWithCard([
                                                                                  'ProviderId' => TestConfiguration::getProviderId(),
                                                                                  'PaymentInformation' => [
                                                                                      'CardNumber' => '4111111111111111',
                                                                                      'ExpiryDate' => '10/30',
                                                                                      'Ccv' => '123',
                                                                                      'Cardholder' => 'Test Cardholder'],
                                                                                  'VaultCard' => true]);

        $result = $paymentMethodSvc->CreatePaymentMethodWithVault([
                                                                      'ProviderId' => TestConfiguration::getProviderId(),
                                                                      'VaultId' => $paymentMethodResult['vaultId'],
                                                                      'PaymentMethodId' => $paymentMethodResult['paymentMethodId']]);

        $this->assertIsString($paymentMethodResult['paymentMethodId']);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testCreatePaymentMethodWithPayTo(): void
    {
        $paymentMethodSvc = new PaymentMethod();

        $paymentMethodResult = $paymentMethodSvc->CreateWithPayTo([
                                                                      'ProviderId' => TestConfiguration::getProviderId(),
                                                                      'PayerName' => 'This is a name',
                                                                      'Description' => 'This is a description',
                                                                      'MaximumAmount' => 500,
                                                                      'PayerPayIdDetails' => [
                                                                          'PayId' => 'david_jones@email.com',
                                                                          'PayIdType' => 'EMAIL'
                                                                      ]]);

        $this->assertIsString($paymentMethodResult['paymentMethodId']);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testCreateWithProviderToken(): void
    {
        $paymentMethodSvc = new PaymentMethod();

        $paymentMethodResult = $paymentMethodSvc->CreateWithProviderToken([
                                                                              'ProviderId' => TestConfiguration::getProviderId(),
                                                                              'ProviderToken' => 'test',
                                                                          ]);

        $this->assertIsString($paymentMethodResult['paymentMethodId']);
        $this->assertSame("CARD", $paymentMethodResult['type']);
        $this->assertNull($paymentMethodResult['customerId']);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testCreatePaymentMethodWithBankAccount(): void
    {
        $paymentMethodSvc = new PaymentMethod();

        $paymentMethodResult = $paymentMethodSvc->CreatePaymentMethodWithBankAccount([
                                                  'ProviderId' => TestConfiguration::getProviderId(),
                                                  'BankPaymentInformation' => [
                                                    'BankCode' => '123-456',
                                                    'AccountNumber' => '123456',
                                                    'AccountName' => 'Bank Account'
                                                  ]]);

        $this->assertIsString($paymentMethodResult['paymentMethodId']);
    }
}
