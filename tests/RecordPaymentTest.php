<?php

class RecordPaymentTest extends TestCase
{
    public function testRecordPaymentRequiresParams()
    {
        $response = $this->post('/api/record-payment', []);
        $this->assertEquals(422, $response->status());
    }
}
