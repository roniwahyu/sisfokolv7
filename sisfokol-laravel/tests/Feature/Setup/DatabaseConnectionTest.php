<?php

namespace Tests\Feature\Setup;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    /** @test */
    public function it_can_connect_to_default_database()
    {
        $result = DB::connection('mysql')->select('SELECT 1');
        $this->assertNotEmpty($result);
    }

    /** @test */
    public function it_can_connect_to_legacy_database()
    {
        $result = DB::connection('legacy_mysql')->select('SELECT 1');
        $this->assertNotEmpty($result);
    }
}
