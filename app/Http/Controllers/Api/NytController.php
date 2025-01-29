<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NytRequest;
use App\Jobs\StoreBestSellersJob;
use App\Services\NytService;
use Illuminate\Http\JsonResponse;

class NytController extends Controller
{
    private NytService $nytService;

    public function __construct(NytService $nytService)
    {
        $this->nytService = $nytService;
    }

    public function index(NytRequest $request): JsonResponse
    {
        // Validate the request parameters
        $query = $request->validated();

        // Fetch data from NYT API
        $data = $this->nytService->fetchBestSellers($query);

        // If there was an error (e.g., API failure, rate limiting, invalid API key), return it immediately
        if (isset($data['error'])) {
            return response()->json($data, $data['status']);
        }

        // Dispatch a job to store the data in the database
        StoreBestSellersJob::dispatch($data);

        // Return the fetched data immediately to the client
        return response()->json($data);
    }
}
