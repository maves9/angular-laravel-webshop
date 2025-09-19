import { Component } from '@angular/core';
import type {
  ProductWithExtras,
  ProductVariantCombinationWithOptions,
  CombinationResult,
} from '../api-types-extensions';
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

  constructor(
    private route: ActivatedRoute,
    private cartService: CartService,
  ) {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.loadProduct(id);
    }
  }

  async addToCart() {
    if (!this.product) return;

    const combination =
      this.finalCombination && !('error' in this.finalCombination)
        ? (this.finalCombination as ProductVariantCombinationWithOptions)
        : null;

    const qty = this.quantity || 1;

    const options = { ...(this.selected || {}), ...(combination?.options || {}) } as Record<
      string,
      unknown
    >;

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

  /**
   * Return a description to display: prefer finalCombination.description when available,
   * otherwise aggregate selected variant descriptions (prefer 'en'), otherwise product.description.
   */
  public getDisplayDescription(locale = 'en'): string | null {
    // 1) final combination description
    const combination =
      this.finalCombination && !('error' in this.finalCombination)
        ? (this.finalCombination as ProductVariantCombinationWithOptions)
        : null;
    if (combination && (combination as any).description) return (combination as any).description;

    // 2) aggregate selected variant descriptions
    const variantOptions = this.product
      ? (this.product['variant_options'] as Record<string, Array<any>> | undefined)
      : undefined;
    if (this.product && this.selected) {
      const parts = Object.entries(this.selected).flatMap(([variantName, value]) => {
        const arr = variantOptions ? (variantOptions[variantName] as any[] | undefined) : undefined;
        if (Array.isArray(arr)) {
          const entry = arr.find((a) => a && (a.value === value || a === value));
          if (entry && entry.descriptions) {
            const descs = entry.descriptions as Record<string, string>;
            const d = (descs[locale] || descs['en'] || Object.values(descs)[0]) as
              | string
              | undefined;
            return typeof d === 'string' && d.length > 0 ? [d] : [];
          }
          return [];
        }

        // fallback: try to find in combinations
        const combos =
          (this.product?.combinations as ProductVariantCombinationWithOptions[] | undefined) ||
          undefined;
        if (!combos) return [];
        const found = combos.find(
          (c) =>
            ((c.options as Record<string, string> | undefined) || {})[variantName] === value &&
            (c as any).description,
        );
        return found ? [(found as any).description] : [];
      });

      if (parts.length > 0) return parts.join(' — ');
    }

    // 3) product description fallback
    return this.product?.description ?? null;
  }

  /** Return the description string for the finalized combination if present */
  public getFinalCombinationDescription(): string | null {
    const combination =
      this.finalCombination && !('error' in this.finalCombination)
        ? (this.finalCombination as ProductVariantCombinationWithOptions)
        : null;
    if (combination && (combination as any).description) {
      const d = (combination as any).description;
      return typeof d === 'string' ? d : String(d);
    }
    return null;
  }

  selectVariant(variantName: string, value: string) {
    this.selected[variantName] = value;
    this.tryFindCombination();
  }

  public getVariantTypes(): string[] {
    if (this.product?.variantTypes && Array.isArray(this.product.variantTypes)) {
      return this.product.variantTypes;
    }
    const variantOptions = this.product
      ? (this.product['variant_options'] as Record<string, Array<any>> | undefined)
      : undefined;
    if (variantOptions) return Object.keys(variantOptions);
    const combos =
      (this.product?.combinations as ProductVariantCombinationWithOptions[] | undefined) ||
      undefined;
    if (!combos || combos.length === 0) return [];
    return Array.from(
      new Set(
        combos.flatMap((c) => Object.keys((c.options as Record<string, string> | undefined) || {})),
      ),
    );
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
    const variantOptions = this.product
      ? (this.product['variant_options'] as Record<string, Array<any>> | undefined)
      : undefined;
    const arr = variantOptions ? (variantOptions[variantName] as any[] | undefined) : undefined;
    if (Array.isArray(arr)) return arr.map((a) => (a.value ? a.value : (a as string)));
    const combos =
      (this.product?.combinations as ProductVariantCombinationWithOptions[] | undefined) ||
      undefined;
    if (!combos || combos.length === 0) return [];
    return Array.from(
      new Set(
        combos
          .map((c) => ((c.options as Record<string, string> | undefined) || {})[variantName])
          .filter(Boolean) as string[],
      ),
    );
  }

  async loadProduct(id: string) {
    const res = await fetch(`http://localhost:8000/api/products/${id}`, { credentials: 'include' });
    if (res.ok) {
      this.product = (await res.json()) as ProductWithExtras;
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
    const res = await fetch(
      `http://localhost:8000/api/products/${this.product!.id}/combinations/find?${qs}`,
      { credentials: 'include' },
    );
    if (res.ok) {
      this.finalCombination = (await res.json()) as ProductVariantCombinationWithOptions;
    } else {
      this.finalCombination = { error: 'Combination not available' };
    }
  }
}
