<?php

class ImportTriggerTest extends TestCase
{
    public function testImportTriggerRequiresName()
    {
        $response = $this->post('/api/trigger-import', []);
        $this->assertEquals(422, $response->status());
    }
}
