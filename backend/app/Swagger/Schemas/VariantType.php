<?php
namespace App\Swagger\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="VariantType",
 *   required={"id","name"},
 *   @OA\Property(property="id", type="integer", format="int64"),
 *   @OA\Property(property="name", type="string")
 * )
 */
class VariantType
{
    // Marker for VariantType schema
}
