<?php
namespace App\Swagger\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="ProductVariant",
 *   required={"id","product_id","variant_type_id","value"},
 *   @OA\Property(property="id", type="integer", format="int64"),
 *   @OA\Property(property="product_id", type="integer", format="int64"),
 *   @OA\Property(property="variant_type_id", type="integer", format="int64"),
 *   @OA\Property(property="value", type="string")
 * )
 */
class ProductVariant
{
    // Marker for ProductVariant schema
}
