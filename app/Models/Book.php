<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'author_id', 'publisher_id', 'price'];

    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }

    public function isbns()
    {
        return $this->hasMany(Isbn::class);
    }

    public function rankings()
    {
        return $this->hasMany(BookRanking::class);
    }
}
