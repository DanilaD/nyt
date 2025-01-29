<?php

namespace App\Services;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookRanking;
use App\Models\Isbn;
use App\Models\Publisher;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class NytService
{
    private string $urlHistory = 'https://api.nytimes.com/svc/books/v3/lists/best-sellers/history.json';
    private int $cacheDuration = 600;

    /**
     * Fetch best sellers from NYT API with caching.
     */
    public function fetchBestSellers(array $query): array
    {
        if (!$this->hasInternetConnection()) {
            return $this->errorResponse('No internet connection. Please check your network.', 503);
        }

        $cacheKey = $this->getCacheKey($query);

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($query) {
            return $this->makeApiRequest($query);
        });
    }

    /**
     * Store best sellers in the database.
     */
    public function storeBestSellers(array $data): void
    {
        if (empty($data['results'])) {
            return;
        }

        foreach ($data['results'] as $bookData) {
            $author = Author::firstOrCreate(['name' => $bookData['author'] ?? 'Unknown Author']);
            $publisher = Publisher::firstOrCreate(['name' => $bookData['publisher'] ?? 'Unknown Publisher']);

            $book = Book::updateOrCreate(
                ['title' => $bookData['title']],
                [
                    'description'  => $bookData['description'] ?? null,
                    'author_id'    => $author->id,
                    'publisher_id' => $publisher->id,
                    'price'        => $bookData['price'] ?? null,
                ]
            );

            $this->storeIsbns($book, $bookData['isbns'] ?? []);
            $this->storeBookRankings($book, $bookData['ranks_history'] ?? []);
        }
    }

    /**
     * Store book ISBNs in the database.
     */
    private function storeIsbns(Book $book, array $isbns): void
    {
        if (empty($isbns)) {
            return;
        }

        foreach (array_unique(array_column($isbns, 'isbn13')) as $isbn) {
            Isbn::updateOrCreate(['isbn' => $isbn], ['book_id' => $book->id]);
        }
    }

    /**
     * Store book rankings in the database.
     */
    private function storeBookRankings(Book $book, array $ranksHistory): void
    {
        if (empty($ranksHistory)) {
            return;
        }

        foreach ($ranksHistory as $rank) {
            BookRanking::updateOrCreate(
                [
                    'book_id'        => $book->id,
                    'list_name'      => $rank['list_name'],
                    'published_date' => $rank['published_date'],
                ],
                [
                    'rank'          => $rank['rank'],
                    'weeks_on_list' => $rank['weeks_on_list'],
                ]
            );
        }
    }

    /**
     * Make API request to NYT and handle errors.
     */
    private function makeApiRequest(array $query): array
    {
        $query['api-key'] = config('services.nyt.api_key');
        $response = Http::get($this->urlHistory, $query);

        if ($response->status() === 429) {
            return $this->errorResponse('Rate limit exceeded. Please try again later.', 429);
        }

        if ($response->status() === 401) {
            return $this->errorResponse('Invalid ApiKey', 401);
        }

        if ($response->failed()) {
            return $this->errorResponse('API request failed', $response->status());
        }

        // Get JSON response
        $data = $response->json();

        // Ensure response has "status" key and it is "OK"
        if (!isset($data['status']) || $data['status'] !== 'OK') {
            return $this->errorResponse('Invalid response from NYT API', 500);
        }

        return $data;
    }

    /**
     * Generate an error response.
     */
    private function errorResponse(string $message, int $status): array
    {
        return ['error' => $message, 'status' => $status];
    }

    /**
     * Check internet connectivity before making an API request.
     */
    public function hasInternetConnection(): bool
    {
        return (bool) @fsockopen("www.google.com", 80);
    }

    /**
     * Generate a cache key for API requests.
     */
    private function getCacheKey(array $query): string
    {
        return 'nyt_best_sellers_' . md5(json_encode($query));
    }
}
