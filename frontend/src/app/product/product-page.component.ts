import { Component } from '@angular/core';
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
  product: any = null;
  selected: Record<string, string> = {};
  finalCombination: any = null;
  quantity = 1;

  constructor(private route: ActivatedRoute, private cartService: CartService) {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.loadProduct(id);
    }
  }

  async addToCart() {
    if (!this.product) return;

    const combination = this.finalCombination && !this.finalCombination.error ? this.finalCombination : null;

    const qty = this.quantity || 1;

    const options = { ...(this.selected || {}) , ...(combination?.options || {}) };

    if (combination && combination.id) {
      (options as any).combination_id = combination.id;
    }

    try {
      await this.cartService.add(this.product.id, qty, options);
      alert('Added to cart');
    } catch (e: any) {
      alert(`Failed to add to cart: ${e.message}`);
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
    const combos = this.product?.combinations as any[] | undefined;
    if (!combos || combos.length === 0) return [];
    const keys = new Set<string>();
    for (const c of combos) {
      const opts = c.options || {};
      for (const k of Object.keys(opts)) keys.add(k);
    }
    return Array.from(keys);
  }

  isOptionAvailable(variantName: string, value: string): boolean {
    if (!this.product || !this.product.combinations) return true;

    const combos = this.product.combinations as any[];

    for (const c of combos) {
      const opts = c.options || {};

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
    const arr = this.product[plural];
    if (Array.isArray(arr)) return arr;

    const combos = this.product.combinations as any[] | undefined;
    if (!combos || combos.length === 0) return [];
    const set = new Set<string>();
    for (const c of combos) {
      const opts = c.options || {};
      if (opts[variantName]) set.add(opts[variantName]);
    }
    return Array.from(set);
  }

  async loadProduct(id: string) {
    const res = await fetch(`http://localhost:8000/api/products/${id}`, { credentials: 'include' });
    if (res.ok) {
      this.product = await res.json();
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

    const qs = new URLSearchParams(this.selected as any).toString();
    const res = await fetch(`http://localhost:8000/api/products/${this.product.id}/combinations/find?${qs}`, { credentials: 'include' });
    if (res.ok) {
      this.finalCombination = await res.json();
    } else {
      this.finalCombination = { error: 'Combination not available' };
    }
  }
}
