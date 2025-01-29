<?php

namespace Tests\Unit;

use App\Http\Requests\NytRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class NytRequestTest extends TestCase
{
    /**
     * Helper function to create a validator instance for testing.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function getValidator(array $data)
    {
        $request = new NytRequest();
        return Validator::make($data, $request->rules());
    }

    /**
     * Test that valid data passes validation.
     */
    public function test_valid_data_passes_validation()
    {
        $data = [
            'author' => 'Test Author',
            'isbn'   => '9780747532190;1265476543123;1121593821;006285769X',
            'title'  => 'Test Title',
            'offset' => 20,
        ];

        $validator = $this->getValidator($data);
        $this->assertTrue($validator->passes());
    }

    /**
     * Test missing optional fields passes validation.
     */
    public function test_missing_optional_fields_pass_validation()
    {
        $data = [];
        $validator = $this->getValidator($data);
        $this->assertTrue($validator->passes());
    }

    /**
     * Test invalid author fails validation.
     */
    public function test_invalid_author_fails_validation()
    {
        $data = ['author' => str_repeat('a', 256)];
        $validator = $this->getValidator($data);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('author', $validator->errors()->toArray());
    }

    /**
     * Test invalid title fails validation.
     */
    public function test_invalid_title_fails_validation()
    {
        $data = ['title' => str_repeat('b', 256)];
        $validator = $this->getValidator($data);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    /**
     * Test invalid ISBN format fails validation.
     */
    public function test_invalid_isbn_fails_validation()
    {
        $data = ['isbn' => 'incorrect_isbn;123;9780439139601X'];
        $validator = $this->getValidator($data);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('isbn', $validator->errors()->toArray());
    }

    /**
     * Test valid ISBN values pass validation.
     */
    public function test_valid_isbn_values_pass_validation()
    {
        $data = ['isbn' => '9780747532743;9780439139601;006285769X'];
        $validator = $this->getValidator($data);
        $this->assertTrue($validator->passes());
    }

    /**
     * Test valid ISBN with lowercase "x" passes validation.
     */
    public function test_valid_isbn_with_lowercase_x_passes_validation()
    {
        $data = ['isbn' => '006285769x;9780439139601'];
        $validator = $this->getValidator($data);
        $this->assertTrue($validator->passes());
    }

    /**
     * Test invalid offset fails validation.
     */
    public function test_invalid_offset_fails_validation()
    {
        $data = ['offset' => 15];
        $validator = $this->getValidator($data);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('offset', $validator->errors()->toArray());
    }

    /**
     * Test valid offset passes validation.
     */
    public function test_valid_offset_passes_validation()
    {
        $data = ['offset' => 40];
        $validator = $this->getValidator($data);
        $this->assertTrue($validator->passes());
    }
}

