<?php

class CourseCrudTest extends TestCase
{
    public function testCreateRequiresCourseKeyAndName()
    {
        $response = $this->post('/api/course_offerings', []);
        $this->assertEquals(422, $response->status());
    }

    public function testIndexReturns200()
    {
        $response = $this->get('/api/course_offerings');
        $this->assertEquals(200, $response->status());
    }
}
