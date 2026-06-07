<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Build extends Model
{
    protected $fillable = [
        'product_id',
        'platform',
        'version',
        'file_path',
        'checksum_sha256',
        'file_size',
        'is_latest',
        'uploaded_by',
    ];

    protected $casts = [
        'is_latest' => 'boolean',
        'file_size' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
