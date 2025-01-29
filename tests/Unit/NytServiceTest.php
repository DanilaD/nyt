<?php

namespace Tests\Unit;

use App\Services\NytService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NytServiceTest extends TestCase
{
    private NytService $nytService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->nytService = new NytService();
        Cache::flush(); // Clear cache before each test
    }

    /**
     * Test internet connectivity check.
     */
    public function test_it_checks_internet_connection()
    {
        $this->assertIsBool($this->nytService->hasInternetConnection());
    }

    /**
     * Test fetching data from the API successfully.
     */
    public function test_it_fetches_best_sellers_successfully()
    {
        // Mock a valid API response
        Http::fake([
            'https://api.nytimes.com/*' => Http::response([
                'status'  => 'OK',
                'results' => [
                    [
                        'title'  => 'Test Book',
                        'author' => 'Test Author',
                        'isbns'  => [['isbn13' => '9781234567890']],
                    ],
                ],
            ], 200),
        ]);

        $query = ['author' => 'Test Author'];
        $result = $this->nytService->fetchBestSellers($query);

        $this->assertArrayHasKey('results', $result);
        $this->assertEquals('Test Book', $result['results'][0]['title']);
    }

    /**
     * Test caching of API results.
     */
    public function test_it_caches_results()
    {
        // Mock a valid API response
        Http::fake([
            'https://api.nytimes.com/*' => Http::response([
                'status'  => 'OK',
                'results' => [
                    [
                        'title'  => 'Cached Book',
                        'author' => 'Cached Author',
                        'isbns'  => [['isbn13' => '9781234567890']],
                    ],
                ],
            ], 200),
        ]);

        $query = ['author' => 'Cached Author'];

        // First call: fetch and cache
        $this->nytService->fetchBestSellers($query);

        // Clear HTTP fakes to ensure the next call comes from cache
        Http::fake();

        // Second call: fetch from cache
        $result = $this->nytService->fetchBestSellers($query);

        $this->assertArrayHasKey('results', $result);
        $this->assertEquals('Cached Book', $result['results'][0]['title']);
    }

    /**
     * Test handling of rate limit error.
     */
    public function test_it_handles_rate_limit_error()
    {
        // Mock the API response
        Http::fake([
            'https://api.nytimes.com/*' => Http::response([
                'error' => 'Rate limit exceeded. Please try again later.',
                'status' => 429,
            ], 429),
        ]);

        $query = ['author' => 'Rate Limit Test'];
        $result = $this->nytService->fetchBestSellers($query);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(429, $result['status']);
        $this->assertEquals('Rate limit exceeded. Please try again later.', $result['error']);
    }

    /**
     * Test handling of invalid API key error.
     */
    public function test_it_handles_invalid_api_key_error()
    {
        // Mock the API response
        Http::fake([
            'https://api.nytimes.com/*' => Http::response([
                'error' => 'Invalid ApiKey',
                'status' => 401,
            ], 401),
        ]);

        $query = ['author' => 'Invalid API Key Test'];
        $result = $this->nytService->fetchBestSellers($query);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(401, $result['status']);
        $this->assertEquals('Invalid ApiKey', $result['error']);
    }

    /**
     * Test handling of API failure.
     */
    public function test_it_handles_api_failure()
    {
        // Mock the API response
        Http::fake([
            'https://api.nytimes.com/*' => Http::response([], 500),
        ]);

        $query = ['author' => 'API Failure Test'];
        $result = $this->nytService->fetchBestSellers($query);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(500, $result['status']);
        $this->assertEquals('API request failed', $result['error']);
    }

    /**
     * Test cache key generation for unique queries.
     */
    public function test_it_generates_unique_cache_key()
    {
        $reflection = new \ReflectionClass($this->nytService);
        $method = $reflection->getMethod('getCacheKey');
        $method->setAccessible(true);

        $query1 = ['author' => 'Author1', 'title' => 'Title1'];
        $query2 = ['author' => 'Author2', 'title' => 'Title2'];

        $key1 = $method->invoke($this->nytService, $query1);
        $key2 = $method->invoke($this->nytService, $query2);

        $this->assertNotEquals($key1, $key2);
        $this->assertStringContainsString('nyt_best_sellers_', $key1);
    }

    /**
     * Test handling of invalid response when "status" is not "OK".
     */
    public function test_it_handles_invalid_api_response()
    {
        // Mock the API response with incorrect status
        Http::fake([
            'https://api.nytimes.com/*' => Http::response([
                'status'  => 'ERROR', // Incorrect status
                'results' => [],
            ], 200),
        ]);

        $query = ['author' => 'Invalid Response Test'];
        $result = $this->nytService->fetchBestSellers($query);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(500, $result['status']);
        $this->assertEquals('Invalid response from NYT API', $result['error']);
    }

    /**
     * Test valid API response with "status": "OK".
     */
    public function test_it_accepts_valid_api_response()
    {
        // Mock a valid API response
        Http::fake([
            'https://api.nytimes.com/*' => Http::response([
                'status'  => 'OK',
                'results' => [
                    [
                        'title'  => 'Valid Book',
                        'author' => 'Valid Author',
                        'isbns'  => [['isbn13' => '9781234567890']],
                    ],
                ],
            ], 200),
        ]);

        $query = ['author' => 'Valid Response Test'];
        $result = $this->nytService->fetchBestSellers($query);

        $this->assertArrayHasKey('results', $result);
        $this->assertEquals('Valid Book', $result['results'][0]['title']);
    }
}
