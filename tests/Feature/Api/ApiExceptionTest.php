<?php

namespace Tests\Feature\Api;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiExceptionTest extends TestCase
{
    public function test_api_exception_is_rendered_as_consistent_json(): void
    {
        Route::get('/api/test-error', function () {
            throw ApiException::make('Custom test error.', 422, [
                'field' => ['The field is invalid.'],
            ]);
        });

        $this->getJson('/api/test-error')
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Custom test error.',
                'errors' => [
                    'field' => ['The field is invalid.'],
                ],
            ]);
    }
}
