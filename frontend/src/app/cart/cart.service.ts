import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import type { CartItem } from './cart-types';

@Injectable({ providedIn: 'root' })
export class CartService {
  private cart$ = new BehaviorSubject<CartItem[]>([]);
  // CSRF token removed: backend no longer provides /api/csrf. We rely on session cookies.
  private apiBase = 'http://localhost:8000/api';

  constructor() {
  }

  public getCartObservable() {
    return this.cart$.asObservable();
  }

  // CSRF fetching removed. Session cookies are used directly.

  public async add(productId: number, quantity = 1, options: Record<string, unknown> = {}): Promise<CartItem[] | { cart: CartItem[] } | Record<string, CartItem>> {

    const payload = {
      product_id: productId,
      quantity,
      options,
    };

    const res = await fetch(`${this.apiBase}/cart/add`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    if (res.status === 201 || res.ok) {
      const cart = await res.json();
      this.cart$.next(cart as CartItem[]);
      return cart as CartItem[];
    }

    const err = await res.text();
    throw new Error(`Add to cart failed: ${res.status} ${err}`);
  }

  public async fetch(): Promise<CartItem[] | { cart: CartItem[] } | Record<string, CartItem>> {
    const res = await fetch(`${this.apiBase}/cart`, {
      credentials: 'include',
    });
    if (res.ok) {
      const cart = await res.json();
      this.cart$.next(cart as CartItem[]);
      return cart as CartItem[];
    }
    return [];
  }

  public async clear(): Promise<void> {
    // No CSRF: directly call clear endpoint; session cookie is used.
    await fetch(`${this.apiBase}/cart/clear`, {
      method: 'DELETE',
      credentials: 'include',
      headers: {
        // no CSRF header
      },
    });
    this.cart$.next([]);
  }
}
