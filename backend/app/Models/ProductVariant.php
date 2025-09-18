<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_type_id',
        'value',
    ];

    public function variantType()
    {
        return $this->belongsTo(VariantType::class, 'variant_type_id');
    }
}
