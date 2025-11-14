<?php

use Laravel\Lumen\Testing\TestCase;

class RecordPaymentTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }

    public function testRecordPaymentRequiresParams()
    {
        $response = $this->post('/api/record-payment', []);
        $this->assertEquals(422, $response->status());
    }
}
