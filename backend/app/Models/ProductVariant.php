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
        'price',
        'stock',
        'descriptions', // JSON translations keyed by locale, e.g. {"en":"...","da":"..."}
    ];

    protected $casts = [
        'descriptions' => 'array',
    ];

    public function variantType()
    {
        return $this->belongsTo(VariantType::class, 'variant_type_id');
    }

    /**
     * Get description for a given locale with fallback to English then any available.
     */
    public function description(string $locale = 'en')
    {
        $descs = $this->descriptions ?? [];
        if (is_array($descs)) {
            if (!empty($descs[$locale])) return $descs[$locale];
            if (!empty($descs['en'])) return $descs['en'];
            // return first available
            foreach ($descs as $d) {
                if (!empty($d)) return $d;
            }
        }
        return null;
    }
}
