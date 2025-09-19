import { Routes } from '@angular/router';
import { ProductPage } from './product/product-page.component';
import { ProductList } from './product/product-list.component';

export const routes: Routes = [
  { path: '', component: ProductList },
  { path: 'products/:id', component: ProductPage },
];
