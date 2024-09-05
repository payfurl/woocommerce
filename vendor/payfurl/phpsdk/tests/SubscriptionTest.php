<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/TestConfiguration.php');
require_once(__DIR__ . '/../src/Config.php');
require_once(__DIR__ . '/../src/Subscription.php');
require_once(__DIR__ . '/../src/Customer.php');
require_once(__DIR__ . '/TestBase.php');
require_once(__DIR__ . '/../src/ResponseException.php');

use payFURL\Sdk\Subscription;
use payFURL\Sdk\Customer;
use payFURL\Sdk\ResponseException;

final class SubscriptionTest extends TestBase
{
    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testCreateSubscription(): void
    {
        $customerSvc = new Customer();

        $customerResult = $customerSvc->CreateWithCard([
                                                           'ProviderId' => TestConfiguration::getProviderId(),
                                                           'PaymentInformation' => [
                                                               'CardNumber' => '4111111111111111',
                                                               'ExpiryDate' => '10/30',
                                                               'Ccv' => '123',
                                                               'Cardholder' => 'Test Cardholder']]);

        $svc = new Subscription();

        $paymentMethodId = $customerResult['defaultPaymentMethod']['paymentMethodId'];
        $result = $svc->CreateSubscription($this->getNewSubscription($paymentMethodId));

        $this->assertSame($paymentMethodId, $result['paymentMethodId']);
        $this->assertSame('Active', $result['status']);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testGetSubscription(): void
    {
        $customerSvc = new Customer();

        $customerResult = $customerSvc->CreateWithCard([
                                                           'ProviderId' => TestConfiguration::getProviderId(),
                                                           'PaymentInformation' => [
                                                               'CardNumber' => '4111111111111111',
                                                               'ExpiryDate' => '10/30',
                                                               'Ccv' => '123',
                                                               'Cardholder' => 'Test Cardholder']]);

        $svc = new Subscription();

        $paymentMethodId = $customerResult['defaultPaymentMethod']['paymentMethodId'];

        $newSubscription = $svc->CreateSubscription($this->getNewSubscription($paymentMethodId));
        $subscription = $svc->GetSubscription(['SubscriptionId' => $newSubscription['subscriptionId']]);

        $this->assertSame($paymentMethodId, $subscription['paymentMethodId']);
        $this->assertSame('Active', $subscription['status']);
    }


    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testDeleteSubscription(): void
    {
        $customerSvc = new Customer();

        $customerResult = $customerSvc->CreateWithCard([
                                                           'ProviderId' => TestConfiguration::getProviderId(),
                                                           'PaymentInformation' => [
                                                               'CardNumber' => '4111111111111111',
                                                               'ExpiryDate' => '10/30',
                                                               'Ccv' => '123',
                                                               'Cardholder' => 'Test Cardholder']]);

        $svc = new Subscription();

        $paymentMethodId = $customerResult['defaultPaymentMethod']['paymentMethodId'];

        $newSubscription = $svc->CreateSubscription($this->getNewSubscription($paymentMethodId));
        $subscription = $svc->DeleteSubscription(['SubscriptionId' => $newSubscription['subscriptionId']]);

        $this->assertSame($paymentMethodId, $subscription['paymentMethodId']);
        $this->assertSame('Cancelled', $subscription['status']);
    }

    

    private function getNewSubscription($paymentMethodId): array
    {
        return [
            'EndAfter' => ['Count'=>2],
            'Retry' => ['Maximum' => 3,
                        'Frequency' => 1,
                        'Interval' => 'Day'
                        ],
            'Webhook' => [
                            'Url' => 'https://example.com/webhoo',
                            'Authorization' => 'secret'
                        ],
            'PaymentMethodId' => $paymentMethodId,
            'Amount' => 100,
            'Currency' => 'USD',
            'Interval' => 'Month',
            'Frequency' => 1
        ];
    }
}
