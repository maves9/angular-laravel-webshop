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
