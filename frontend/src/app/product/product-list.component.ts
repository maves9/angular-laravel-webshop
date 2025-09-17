import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';

@Component({
  standalone: true,
  selector: 'app-product-list',
  imports: [CommonModule, RouterModule],
  templateUrl: './product-list.component.html',
  styleUrls: ['./product-list.component.css']
})
export class ProductList {
  products: any[] = [];

  constructor() {
    this.loadProducts();
  }

  async loadProducts() {
    const res = await fetch('http://localhost:8000/api/products');
    if (res.ok) {
      this.products = await res.json();
    }
  }
}
