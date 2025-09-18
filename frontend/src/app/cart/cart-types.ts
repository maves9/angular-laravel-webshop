import type { Product, ProductVariantCombination } from '../api-types-exports';

export interface CartItem {
  id?: number | string;
  product_id?: number;
  name?: string;
  product_name?: string;
  quantity?: number;
  qty?: number;
  price?: number;
  options?: Record<string, string> | Record<string, unknown>;
  combination_id?: number | string;
  // allow embedding a resolved product or combination when present
  product?: Product;
  combination?: ProductVariantCombination;
}
