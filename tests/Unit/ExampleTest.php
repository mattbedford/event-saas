<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Basic sanity check that tests can run.
     */
    public function test_that_phpunit_works(): void
    {
        $this->assertTrue(true);
        $this->assertEquals(2, 1 + 1);
        $this->assertIsString('hello');
    }
}
