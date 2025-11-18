<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test that homepage redirects to admin panel.
     */
    public function test_the_application_redirects_to_admin(): void
    {
        $response = $this->get('/');

        // Should redirect to /admin
        $response->assertRedirect('/admin');
    }
}
