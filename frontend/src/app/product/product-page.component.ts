import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';

@Component({
  standalone: true,
  selector: 'app-product-page',
  imports: [CommonModule, RouterModule, FormsModule],
  templateUrl: './product-page.component.html',
  styleUrls: ['./product-page.component.css']
})
export class ProductPage {
  product: any = null;
  selected = { size: '', color: '', fabric: '' };
  finalCombination: any = null;

  constructor(private route: ActivatedRoute) {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.loadProduct(id);
    }
  }

  selectVariant(type: 'size'|'color'|'fabric', value: string) {
    (this.selected as any)[type] = value;
    this.tryFindCombination();
  }

  isOptionAvailable(type: 'size'|'color'|'fabric', value: string): boolean {
    if (!this.product || !this.product.combinations) return true;

    // If no other selection is made, the option is available if any combination has this value
    const combos = this.product.combinations as any[];

    for (const c of combos) {
      const opts = c.options || {};

      // The candidate must match the option for the checked type
      if (opts[type] !== value) continue;

      // Other selected types must match if they are set
      if (type !== 'size' && this.selected.size && opts.size !== this.selected.size) continue;
      if (type !== 'color' && this.selected.color && opts.color !== this.selected.color) continue;
      if (type !== 'fabric' && this.selected.fabric && opts.fabric !== this.selected.fabric) continue;

      // If stock exists (or stock is zero but combination exists) consider available
      return true;
    }

    return false;
  }

  async loadProduct(id: string) {
  const res = await fetch(`http://localhost:8000/api/products/${id}`);
    if (res.ok) {
      this.product = await res.json();
    }
  }

  async tryFindCombination() {
    this.finalCombination = null;
    if (this.selected.size && this.selected.color && this.selected.fabric) {
      const qs = new URLSearchParams(this.selected as any).toString();
  const res = await fetch(`http://localhost:8000/api/products/${this.product.id}/combinations/find?${qs}`);
      if (res.ok) {
        this.finalCombination = await res.json();
      } else {
        this.finalCombination = { error: 'Combination not available' };
      }
    }
  }
}
