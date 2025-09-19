import type { Product, ProductVariantCombination } from './api-types-exports';

// Product responses include additional properties beyond the minimal generated Product schema
// (e.g., combinations, variantTypes, and dynamic lists like 'colors'). We augment the generated
// types with optional fields and a permissive index signature for those dynamic properties.

export type ProductVariantCombinationWithOptions = Partial<
  Omit<ProductVariantCombination, 'options'>
> & {
  // Backend uses a JSON field for options which is an object mapping variantName->value.
  options?: Record<string, string>;
};

export type ProductWithExtras = Product & {
  combinations?: ProductVariantCombinationWithOptions[];
  variantTypes?: string[];
  description?: string;
  // allow indexing dynamic plural lists like `colors`, `sizes` etc.
  [key: string]: unknown;
};

// A combination lookup result can either be a combination or an object with an error field.
// We model this so templates can access both `error` and combination fields safely.
export type CombinationResult = ProductVariantCombinationWithOptions & { error?: string };
