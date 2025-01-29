<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookRanking extends Model
{
    use HasFactory;

    protected $fillable = ['book_id', 'list_name', 'rank', 'published_date', 'weeks_on_list'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
