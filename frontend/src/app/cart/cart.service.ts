import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class CartService {
  private cart$ = new BehaviorSubject<any[]>([]);
  // CSRF token removed: backend no longer provides /api/csrf. We rely on session cookies.
  private apiBase = 'http://localhost:8000/api';

  constructor() {
  }

  public getCartObservable() {
    return this.cart$.asObservable();
  }

  // CSRF fetching removed. Session cookies are used directly.

  public async add(productId: number, quantity = 1, options: Record<string, any> = {}): Promise<any> {

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
      this.cart$.next(cart);
      return cart;
    }

    const err = await res.text();
    throw new Error(`Add to cart failed: ${res.status} ${err}`);
  }

  public async fetch(): Promise<any> {
    const res = await fetch(`${this.apiBase}/cart`, {
      credentials: 'include',
    });
    if (res.ok) {
      const cart = await res.json();
      this.cart$.next(cart);
      return cart;
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
