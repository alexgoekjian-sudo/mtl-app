<?php

use Laravel\Lumen\Testing\TestCase;

class ImportTriggerTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }

    public function testImportTriggerRequiresName()
    {
        $response = $this->post('/api/trigger-import', []);
        $this->assertEquals(422, $response->status());
    }
}
