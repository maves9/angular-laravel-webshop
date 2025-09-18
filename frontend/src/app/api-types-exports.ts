// Lightweight wrapper around auto-generated OpenAPI types.
// Import the generated types and re-export convenient aliases.
import type { components } from './api-types';

export type Product = components['schemas']['Product'];
export type ProductVariant = components['schemas']['ProductVariant'];
export type ProductVariantCombination = components['schemas']['ProductVariantCombination'];
export type VariantType = components['schemas']['VariantType'];

// Optionally export the whole components for other needs
export type ApiComponents = components;
