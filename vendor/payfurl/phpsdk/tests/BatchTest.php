<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/TestConfiguration.php');
require_once(__DIR__ . '/../src/Config.php');
require_once(__DIR__ . '/../src/Batch.php');
require_once(__DIR__ . '/TestBase.php');
require_once(__DIR__ . '/../src/ResponseException.php');

use payFURL\Sdk\Batch;
use payFURL\Sdk\ResponseException;

final class BatchTest extends TestBase
{
    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testCreateTransactionWithPaymentMethod(): void
    {
        $svc = new Batch();

        $description = bin2hex(random_bytes(16));
        $result = $svc->CreateTransactionWithPaymentMethod($this->getNewTransactionPaymentMethod($description));

        $this->assertSame($description, $result['description']);
        $this->assertSame(1, $result['count']);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testCreateTransactionWithPaymentMethodWithWebhook(): void
    {
        $svc = new Batch();

        $description = bin2hex(random_bytes(16));
        $webhookConfig = [
            'Url' => 'https://example.com/webhook',
            'Authorization' => 'Bearer your_token_here'
        ];
        $result = $svc->CreateTransactionWithPaymentMethod($this->getNewTransactionPaymentMethod($description, $webhookConfig));

        $this->assertSame($description, $result['description']);
        $this->assertSame(1, $result['count']);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testGetBatch(): void
    {
        $svc = new Batch();

        $description = bin2hex(random_bytes(16));
        $batch = $svc->CreateTransactionWithPaymentMethod($this->getNewTransactionPaymentMethod($description));
        $result = $svc->GetBatch(['BatchId' => $batch['batchId']]);

        $this->assertSame($description, $result['description']);
        $this->assertSame(1, $result['count']);
        $this->assertTrue(str_starts_with($result['results'], 'PaymentMethodId,Amount,Currency,Reference,Status,TransactionId,FailureReason'));
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testGetBatchStatus(): void
    {
        $svc = new Batch();

        $description = bin2hex(random_bytes(16));
        $batch = $svc->CreateTransactionWithPaymentMethod($this->getNewTransactionPaymentMethod($description));
        $result = $svc->GetBatchStatus(['BatchId' => $batch['batchId']]);

        $this->assertSame($description, $result['description']);
        $this->assertSame(1, $result['count']);
    }

    /**
     * @throws ResponseException
     * @throws Exception
     */
    public function testSearchBatch(): void
    {
        $svc = new Batch();

        $description = bin2hex(random_bytes(16));

        $svc->CreateTransactionWithPaymentMethod($this->getNewTransactionPaymentMethod($description));
        $result = $svc->Search(['Description' => $description]);

        $this->assertSame($description, $result['batches'][0]['description']);
    }

    private function getNewTransactionPaymentMethod($description, $webhookConfig = null): array
    {
        $transaction = [
            'Count' => 1,
            'Description' => $description,
            'Batch' => "PaymentMethodId,Amount,Currency,Reference\ntest,123.4,AUD,reference"
        ];
    
        if ($webhookConfig !== null) {
            $transaction['Webhook'] = $webhookConfig;
        }
    
        return $transaction;
    }
}
