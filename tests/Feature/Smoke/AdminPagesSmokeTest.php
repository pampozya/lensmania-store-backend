<?php

namespace Tests\Feature\Smoke;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

class AdminPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_admin_get_pages_load_without_error(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $failures = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (! str_starts_with($uri, 'admin')) continue;
            if (! in_array('GET', $route->methods())) continue;
            if (str_contains($uri, '{')) continue;      // skip params
            if (str_contains($uri, 'logout')) continue;

            try {
                $res = $this->get('/' . $uri);
                $status = $res->status();
                if ($status >= 500) {
                    $failures[] = "$uri => $status";
                }
            } catch (\Throwable $e) {
                $failures[] = "$uri => EXCEPTION: " . $e->getMessage();
            }
        }

        if (! empty($failures)) {
            dump($failures);
        }
        $this->assertEmpty($failures, count($failures) . ' admin page(s) errored');
    }
}
