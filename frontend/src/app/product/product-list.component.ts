import { Component } from '@angular/core';
import type { ProductWithExtras } from '../api-types-extensions';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';

@Component({
  standalone: true,
  selector: 'app-product-list',
  imports: [CommonModule, RouterModule],
  templateUrl: './product-list.component.html',
})
export class ProductList {
  products: ProductWithExtras[] = [];

  constructor() {
    this.loadProducts();
  }

  async loadProducts() {
    const res = await fetch('http://localhost:8000/api/products', { credentials: 'include' });
    if (res.ok) {
      this.products = (await res.json()) as ProductWithExtras[];
    }
  }
}
