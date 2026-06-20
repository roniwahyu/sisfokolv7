<?php

namespace Tests\Unit\Models\Traits;

use App\Models\Traits\TracksAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TracksAuditColumnsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Schema::create('audit_stub', function ($t) {
            $t->id();
            $t->string('name');
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });
    }

    protected function tearDown(): void
    {
        \Schema::dropIfExists('audit_stub');
        parent::tearDown();
    }

    public function test_create_sets_created_by_from_auth(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $model = AuditStub::create(['name' => 'X']);
        $this->assertSame($user->id, $model->created_by);
        $this->assertSame($user->id, $model->updated_by);
    }
}

class AuditStub extends Model
{
    use TracksAuditColumns;
    protected $table = 'audit_stub';
    protected $fillable = ['name'];
}
