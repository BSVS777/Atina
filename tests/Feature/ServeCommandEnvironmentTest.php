<?php

namespace Tests\Feature;

use Illuminate\Foundation\Console\ServeCommand;
use Tests\TestCase;

class ServeCommandEnvironmentTest extends TestCase
{
    public function test_system_temp_variables_are_forwarded_to_the_local_server(): void
    {
        $this->assertContains('TEMP', ServeCommand::$passthroughVariables);
        $this->assertContains('TMP', ServeCommand::$passthroughVariables);
    }
}
