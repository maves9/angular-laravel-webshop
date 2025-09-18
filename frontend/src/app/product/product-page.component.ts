import { Component } from '@angular/core';
import type { Product } from '../api-types-exports';
import type { ProductWithExtras, ProductVariantCombinationWithOptions, CombinationResult } from '../api-types-extensions';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { VariantButtonComponent } from './variant-button.component';
import { ButtonComponent } from '../ui/button.component';
import { CartService } from '../cart/cart.service';

@Component({
  standalone: true,
  selector: 'app-product-page',
  imports: [CommonModule, RouterModule, FormsModule, VariantButtonComponent, ButtonComponent],
  templateUrl: './product-page.component.html',
})
export class ProductPage {
  product: ProductWithExtras | null = null;
  selected: Record<string, string> = {};
  finalCombination: CombinationResult | null = null;
  quantity = 1;

  constructor(private route: ActivatedRoute, private cartService: CartService) {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.loadProduct(id);
    }
  }

  async addToCart() {
    if (!this.product) return;

  const combination = this.finalCombination && !('error' in this.finalCombination) ? (this.finalCombination as ProductVariantCombinationWithOptions) : null;

    const qty = this.quantity || 1;

    const options = { ...(this.selected || {}), ...(combination?.options || {}) } as Record<string, unknown>;

    if (combination && 'id' in combination) {
      (options as Record<string, unknown>)['combination_id'] = combination.id;
    }

    try {
      await this.cartService.add(this.product.id, qty, options);
      alert('Added to cart');
    } catch (e: unknown) {
      const msg = e && typeof e === 'object' && 'message' in e ? (e as any).message : String(e);
      alert(`Failed to add to cart: ${msg}`);
    }
  }

  selectVariant(variantName: string, value: string) {
    this.selected[variantName] = value;
    this.tryFindCombination();
  }

  public getVariantTypes(): string[] {
    if (this.product?.variantTypes && Array.isArray(this.product.variantTypes)) {
      return this.product.variantTypes;
    }
    const combos = (this.product?.combinations as ProductVariantCombinationWithOptions[] | undefined) || undefined;
    if (!combos || combos.length === 0) return [];
    const keys = new Set<string>();
    for (const c of combos) {
      const opts = (c.options as Record<string, string> | undefined) || {};
      for (const k of Object.keys(opts)) keys.add(k);
    }
    return Array.from(keys);
  }

  isOptionAvailable(variantName: string, value: string): boolean {
    if (!this.product || !this.product.combinations) return true;

    const combos = this.product.combinations as ProductVariantCombinationWithOptions[];

    for (const c of combos) {
  const opts = (c.options as Record<string, string> | undefined) || {};

  if (opts[variantName] !== value) continue;

      let ok = true;
      for (const [otherName, otherValue] of Object.entries(this.selected)) {
        if (otherName === variantName) continue;
        if (otherValue && opts[otherName] !== otherValue) {
          ok = false;
          break;
        }
      }
      if (!ok) continue;

      return true;
    }

    return false;
  }

  getOptionsForVariant(variantName: string): string[] {
    if (!this.product) return [];

    const plural = `${variantName}s`;
    const arr = this.product ? (this.product[plural] as string[] | undefined) : undefined;
    if (Array.isArray(arr)) return arr;

    const combos = (this.product?.combinations as ProductVariantCombinationWithOptions[] | undefined) || undefined;
    if (!combos || combos.length === 0) return [];
    const set = new Set<string>();
    for (const c of combos) {
      const opts = (c.options as Record<string, string> | undefined) || {};
      if (opts[variantName]) set.add(opts[variantName]);
    }
    return Array.from(set);
  }

  async loadProduct(id: string) {
    const res = await fetch(`http://localhost:8000/api/products/${id}`, { credentials: 'include' });
    if (res.ok) {
      this.product = await res.json() as ProductWithExtras;
      this.selected = {};
      this.finalCombination = null;
    }
  }

  async tryFindCombination() {
    this.finalCombination = null;
  const variantTypes = this.getVariantTypes();

    for (const vt of variantTypes) {
      if (!this.selected[vt]) return;
    }

    const qs = new URLSearchParams(this.selected as Record<string, string>).toString();
    const res = await fetch(`http://localhost:8000/api/products/${this.product!.id}/combinations/find?${qs}`, { credentials: 'include' });
    if (res.ok) {
      this.finalCombination = await res.json() as ProductVariantCombinationWithOptions;
    } else {
      this.finalCombination = { error: 'Combination not available' };
    }
  }
}
