<?php
namespace App\Swagger\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="ProductVariantCombination",
 *   required={"id","product_id","sku","price","stock","options"},
 *   @OA\Property(property="id", type="integer", format="int64"),
 *   @OA\Property(property="product_id", type="integer", format="int64"),
 *   @OA\Property(property="sku", type="string"),
 *   @OA\Property(property="price", type="number", format="float"),
 *   @OA\Property(property="stock", type="integer", format="int32"),
 *   @OA\Property(property="options", type="array", @OA\Items(type="string"))
 * )
 */
class ProductVariantCombination
{
    // Marker for ProductVariantCombination schema
}
