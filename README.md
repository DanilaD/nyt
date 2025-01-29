# Lendflow Assessment: NYT Best Sellers JSON API

This project is a Laravel-based JSON API that acts as a wrapper for the [New York Times Best Sellers History API](https://developer.nytimes.com/docs/books-product/1/routes/lists/best-sellers/history.json/get). It provides a simple and efficient way to filter best-seller book data based on specific query parameters such as author, ISBN, title, and offset. The system also utilizes caching to improve performance and reduce redundant API requests.

## Features

- Fetch best-seller books from the NYT API based on query parameters.
- Validate requests using Laravel's FormRequest.
- Use queues to process and store the data asynchronously.
- Implement caching to optimize repeated requests and enhance performance.
- Handle API errors, including rate limits and invalid API keys.

## Requirements

- **PHP:** 7.4 or higher
- **Laravel:** 8.83.29
- **Composer:** Latest version
- **New York Times API Key:** You need to generate your own API key from [here](https://developer.nytimes.com/accounts/create).
- **Redis or File-based Cache:** Configured in Laravel to store API responses for efficiency.

## Installation

1. Clone the repository:

   ```bash
   git clone <repository_url>
   cd <repository_directory>
   ```

2. Install dependencies:

   ```bash
   composer install
   ```

3. Set up your `.env` file:

   ```bash
   cp .env.example .env
   ```

   Then update the following variables:

   ```env
   NYT_API_KEY=your_nyt_api_key_here
   CACHE_DRIVER=redis  # or file, database, etc.
   QUEUE_CONNECTION=database # Ensure queue is configured properly
   ```

4. Run database migrations:

   ```bash
   php artisan migrate
   ```

5. Start the queue worker to handle job processing:

   ```bash
   php artisan queue:work
   ```

## API Endpoints

### Fetch Best Sellers

**GET** `/api/v1/nyt/best-sellers`

#### Query Parameters:

- `author` (string, optional) - Filter results by author name.
- `title` (string, optional) - Filter results by book title.
- `isbn` (string, optional) - Filter results by ISBN (comma-separated list of 10 or 13-digit ISBNs).
- `offset` (integer, optional) - Must be a multiple of 20 (e.g., 0, 20, 40, etc.).

### Example Request:

```bash
curl -X GET "http://localhost:8000/api/v1/nyt/best-sellers?author=J.K.%20Rowling&offset=20"
```

## Caching Strategy

- API responses are cached for **10 minutes** to reduce redundant requests.
- Cache keys are generated based on request parameters.
- If cached data exists, it is returned instead of making an external API request.

## Queueing Strategy

- When a request is made, data is **fetched immediately** from the NYT API.
- A background job (`StoreBestSellersJob`) is dispatched to process and **store data asynchronously** in the database.
- This ensures the system remains responsive while processing large datasets.

## Running Tests

The application includes feature and unit tests for validation, API responses, and data storage.

Run tests using:

```bash
php artisan test
```

## Conclusion

This API provides an efficient way to fetch, cache, and store NYT best-seller book data while ensuring performance optimization through caching and queue-based processing.

