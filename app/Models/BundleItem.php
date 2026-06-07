<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BundleItem extends Model
{
    protected $fillable = ['bundle_product_id', 'item_product_id'];

    public function itemProduct()
    {
        return $this->belongsTo(Product::class, 'item_product_id');
    }

    public function bundleProduct()
    {
        return $this->belongsTo(Product::class, 'bundle_product_id');
    }
}
