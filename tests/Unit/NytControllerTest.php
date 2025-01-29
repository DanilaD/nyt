<?php

namespace Tests\Feature;

use App\Services\NytService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NytControllerTest extends TestCase
{
    private string $urlAPI = '/api/v1/nyt/best-sellers';

    /**
     * Test when there is no internet connection.
     */
    public function test_it_returns_error_when_no_internet_connection()
    {
        // Mock NytService to simulate no internet connection
        $mockService = $this->getMockBuilder(NytService::class)
            ->onlyMethods(['hasInternetConnection'])
            ->getMock();

        // Ensure that `hasInternetConnection()` returns `false`
        $mockService->method('hasInternetConnection')->willReturn(false);

        // Replace the actual instance with the mocked one
        $this->app->instance(NytService::class, $mockService);

        // Send request
        $response = $this->getJson($this->urlAPI);

        // Expect a 503 Service Unavailable response
        $response->assertStatus(503)
            ->assertJson([
                'error'  => 'No internet connection. Please check your network.',
                'status' => 503,
            ]);
    }

    /**
     * Test when the service returns a rate limit error.
     */
    public function test_it_handles_rate_limit_exceeded()
    {
        // Mock the NytService to simulate a rate limit error
        $mockService = $this->createMock(NytService::class);
        $mockService->method('hasInternetConnection')->willReturn(true);
        $mockService->method('fetchBestSellers')->willReturn([
            'error'  => 'Rate limit exceeded. Please try again later.',
            'status' => 429,
        ]);

        $this->app->instance(NytService::class, $mockService);

        $response = $this->getJson($this->urlAPI);

        $response->assertStatus(429)
            ->assertJson([
                'error'  => 'Rate limit exceeded. Please try again later.',
                'status' => 429,
            ]);
    }

    /**
     * Test when the service returns an invalid API key error.
     */
    public function test_it_handles_invalid_api_key()
    {
        // Mock the NytService to simulate an invalid API key error
        $mockService = $this->createMock(NytService::class);
        $mockService->method('hasInternetConnection')->willReturn(true);
        $mockService->method('fetchBestSellers')->willReturn([
            'error'  => 'Invalid ApiKey',
            'status' => 401,
        ]);

        $this->app->instance(NytService::class, $mockService);

        $response = $this->getJson($this->urlAPI);

        $response->assertStatus(401)
            ->assertJson([
                'error'  => 'Invalid ApiKey',
                'status' => 401,
            ]);
    }

    /**
     * Test fetching best sellers successfully.
     */
    public function test_it_fetches_best_sellers_successfully()
    {
        // Mock the NytService to simulate a successful API response
        $mockService = $this->createMock(NytService::class);
        $mockService->method('hasInternetConnection')->willReturn(true);
        $mockService->method('fetchBestSellers')->willReturn([
            'results' => [
                [
                    'title'  => 'Test Book',
                    'author' => 'Test Author',
                    'isbn'   => ['1234567890123'],
                ],
            ],
        ]);

        $this->app->instance(NytService::class, $mockService);

        $response = $this->getJson($this->urlAPI);

        $response->assertStatus(200)
            ->assertJson([
                'results' => [
                    [
                        'title'  => 'Test Book',
                        'author' => 'Test Author',
                        'isbn'   => ['1234567890123'],
                    ],
                ],
            ]);
    }
}
