<?php

class StudentCrudTest extends TestCase
{
    public function testCreateRequiresFirstAndLastName()
    {
        $response = $this->post('/api/students', []);
        $this->assertEquals(422, $response->status());
    }

    public function testIndexReturns200()
    {
        $response = $this->get('/api/students');
        $this->assertEquals(200, $response->status());
    }
}
