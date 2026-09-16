<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_landing_page_presents_the_phase_one_portal_foundation(): void
    {
        $this->withoutVite();

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('मराठी आणि गणित अध्ययन पोर्टल')
            ->assertSee('Phase 1 foundation');
    }
}
