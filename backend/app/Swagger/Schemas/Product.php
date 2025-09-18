<?php
namespace App\Swagger\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="Product",
 *   required={"id","name","price"},
 *   @OA\Property(property="id", type="integer", format="int64"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="price", type="number", format="float"),
 * )
 */
class Product
{
    // Marker class for OpenAPI schema generation
}
