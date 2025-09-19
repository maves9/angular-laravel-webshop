import { Component, inject } from '@angular/core'
import type {
  ProductWithExtras,
  ProductVariantCombinationWithOptions,
  CombinationResult,
} from '../api-types-extensions'
import { CommonModule } from '@angular/common'
import { ActivatedRoute, RouterModule } from '@angular/router'
import { FormsModule } from '@angular/forms'
import { VariantButtonComponent } from './variant-button.component'
import { ButtonComponent } from '../ui/button.component'
import { CartService } from '../cart/cart.service'

@Component({
  standalone: true,
  selector: 'app-product-page',
  imports: [CommonModule, RouterModule, FormsModule, VariantButtonComponent, ButtonComponent],
  templateUrl: './product-page.component.html',
})
export class ProductPage {
  product: ProductWithExtras | null = null
  selected: Record<string, string> = {}
  finalCombination: CombinationResult | null = null
  quantity = 1;

  private route = inject(ActivatedRoute)
  private cartService = inject(CartService)

  constructor() {
    const id = this.route.snapshot.paramMap.get('id')
    if (id) {
      this.loadProduct(id)
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
      (options as Record<string, unknown>)['combination_id'] = combination.id
    }

    try {
      await this.cartService.add(this.product.id, qty, options)
      alert('Added to cart')
    } catch (e: unknown) {
      let message = String(e);
      if (e && typeof e === 'object' && 'message' in e) {
        const obj = e as Record<string, unknown>

        if (typeof obj['message'] === 'string') message = obj['message']

        else if (obj['message'] && typeof obj['message'] === 'object')
          message = JSON.stringify(obj['message']);
      }
      alert(`Failed to add to cart: ${message}`)
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
        : null

    if (combination && (combination as unknown as { description?: unknown }).description)
      return (combination as unknown as { description?: string }).description as string

    // 2) aggregate selected variant descriptions
    const variantOptions = this.product
      ? (this.product['variant_options'] as Record<string, Array<unknown>> | undefined)
      : undefined

    if (!this.product || !this.selected) return this.product?.description ?? null

    type VariantEntry = { value?: string; descriptions?: Record<string, string> }
    const isVariantEntry = (v: unknown): v is VariantEntry => typeof v === 'object' && v !== null

    const parts = Object.entries(this.selected).flatMap(([variantName, value]) => {
      const arr = variantOptions
        ? (variantOptions[variantName] as unknown[] | undefined)
        : undefined;
      if (Array.isArray(arr)) {
        const entry = arr.find((a) => isVariantEntry(a) && (a.value === value || a === value)) as
          | VariantEntry
          | undefined

        if (!entry || !entry.descriptions) return []

        const descs = entry.descriptions as Record<string, string>
        const d = (descs[locale] || descs['en'] || Object.values(descs)[0]) as string | undefined

        return typeof d === 'string' && d.length > 0 ? [d] : []
      }

      const combos =
        (this.product?.combinations as ProductVariantCombinationWithOptions[] | undefined) ||
        undefined

      if (!combos) return [];
      const found = combos.find(
        (combo) =>
          ((combo.options as Record<string, string> | undefined) || {})[variantName] === value &&
          (combo as unknown as { description?: unknown }).description,
      )
      return found ? [(found as unknown as { description: string }).description] : []
    });

    if (parts.length > 0) return parts.join(' — ')

    return this.product?.description ?? null
  }

  /** Return the description string for the finalized combination if present */
  public getFinalCombinationDescription(): string | null {
    const combination =
      this.finalCombination && !('error' in this.finalCombination)
        ? (this.finalCombination as ProductVariantCombinationWithOptions)
        : null;
    if (!combination || !('description' in (combination as unknown as Record<string, unknown>)))
      return null;
    const d = (combination as unknown as Record<string, unknown>)['description'];
    return typeof d === 'string' ? d : String(d);
  }

  selectVariant(variantName: string, value: string) {
    this.selected[variantName] = value;
    this.tryFindCombination();
  }

  public getVariantTypes(): string[] {
    if (this.product?.variantTypes && Array.isArray(this.product.variantTypes)) {
      return this.product.variantTypes
    }

    const variantOptions = this.product
      ? (this.product['variant_options'] as Record<string, Array<unknown>> | undefined)
      : undefined

    if (variantOptions) return Object.keys(variantOptions)
    const combos =
      (this.product?.combinations as ProductVariantCombinationWithOptions[] | undefined) ||
      undefined

    if (!combos || combos.length === 0) return []
  
    return Array.from(
      new Set(
        combos.flatMap((c) => Object.keys((c.options as Record<string, string> | undefined) || {})),
      ),
    );
  }

  isOptionAvailable(variantName: string, value: string): boolean {
    if (!this.product || !this.product.combinations) return true;

    const combos = this.product.combinations as ProductVariantCombinationWithOptions[];

    return combos.some((combo) => {
      const opts = (combo.options as Record<string, string> | undefined) || {};
      if (opts[variantName] !== value) return false;

      // Ensure all other selected variant values (if present) match this combination's options
      return Object.entries(this.selected).every(([otherName, otherValue]) =>
        otherName === variantName ? true : !otherValue || opts[otherName] === otherValue,
      )
    })
  }

  getOptionsForVariant(variantName: string): string[] {
    if (!this.product) return [];

    const variantOptions = this.product['variant_options'] as
      | Record<string, Array<unknown>>
      | undefined

    const arr = variantOptions ? (variantOptions[variantName] as unknown[] | undefined) : undefined;
    if (Array.isArray(arr))
      return arr.map((a) => {
        if (a && typeof a === 'object' && 'value' in (a as Record<string, unknown>)) {
          return String((a as Record<string, unknown>)['value']);
        }
        return String(a);
      });

    const combos =
      (this.product?.combinations as ProductVariantCombinationWithOptions[] | undefined) ||
      undefined;
    if (!combos || combos.length === 0) return [];

    const values = combos
      .map((c) => ((c.options as Record<string, string> | undefined) || {})[variantName])
      .filter(Boolean) as string[];

    return Array.from(new Set(values));
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
