<?php

namespace Tests\Unit\Jobs;

use App\Jobs\StoreBestSellersJob;
use App\Services\NytService;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class StoreBestSellersJobTest extends TestCase
{
    /**
     * Test that the job is dispatched correctly.
     */
    public function test_it_dispatches_correctly()
    {
        Bus::fake();

        $testData = ['results' => [
            [
                'title'         => 'Test Book',
                'author'        => 'Test Author',
                'publisher'     => 'Test Publisher',
                'price'         => '19.99',
                'isbns'         => [['isbn13' => '9781234567890']],
                'ranks_history' => [
                    [
                        'list_name' => 'Fiction',
                        'published_date' => '2023-01-01',
                        'rank' => 1,
                        'weeks_on_list' => 10
                    ]
                ]
            ]
        ]];

        // Dispatch job
        StoreBestSellersJob::dispatch($testData);

        // Assert the job was dispatched
        Bus::assertDispatched(StoreBestSellersJob::class, function ($job) use ($testData) {
            return $job->data === $testData;
        });
    }

    /**
     * Test that the job processes and calls storeBestSellers correctly.
     */
    public function test_it_handles_store_best_sellers()
    {
        $nytServiceMock = $this->createMock(NytService::class);

        $testData = ['results' => [
            [
                'title'         => 'Test Book',
                'author'        => 'Test Author',
                'publisher'     => 'Test Publisher',
                'price'         => '19.99',
                'isbns'         => [['isbn13' => '9781234567890']],
                'ranks_history' => [
                    [
                        'list_name'      => 'Fiction',
                        'published_date' => '2023-01-01',
                        'rank'           => 1,
                        'weeks_on_list'  => 10
                    ]
                ]
            ]
        ]];

        // Expect storeBestSellers to be called with test data
        $nytServiceMock->expects($this->once())
            ->method('storeBestSellers')
            ->with($testData);

        // Execute job with the mocked service
        $job = new StoreBestSellersJob($testData);
        $job->handle($nytServiceMock);
    }
}
