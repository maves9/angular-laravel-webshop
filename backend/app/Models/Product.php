<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
    ];

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function combinations()
    {
        return $this->hasMany(ProductVariantCombination::class);
    }
}
