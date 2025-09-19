import { Component, OnInit, computed, signal, inject, WritableSignal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { CartService } from '../cart/cart.service';
import type { CartItem } from '../cart/cart-types';

@Component({
  standalone: true,
  selector: 'app-header',
  imports: [CommonModule, RouterModule],
  template: `
    <header class="bg-slate-900 text-white py-3">
      <div class="max-w-screen-lg mx-auto px-4 flex items-center justify-between">
        <a routerLink="/" class="font-bold text-white no-underline">Webshop</a>

        <nav class="hidden md:block">
          <a routerLink="/" class="text-slate-300 hover:text-white ml-4 no-underline">Products</a>
        </nav>

        <div class="relative ml-4" (click)="toggleDropdown($event)">
          <button
            class="flex items-center gap-2 text-white bg-transparent border-0"
            aria-label="Open cart"
          >
            <span class="text-xl">🛒</span>
            <span class="inline-block bg-red-600 text-white rounded-full px-2 text-sm">{{
              totalCount()
            }}</span>
          </button>

          <div
            class="absolute right-0 mt-2 w-64 bg-white text-slate-900 rounded-lg shadow-lg p-2 z-40"
            [class.hidden]="!dropdownOpen"
          >
            <div *ngIf="!cart() || cart().length === 0" class="p-3 text-slate-500">
              Your cart is empty
            </div>
            <ul *ngIf="cart() && cart().length" class="max-h-56 overflow-auto">
              <li
                *ngFor="let item of cart()"
                class="flex justify-between py-2 border-b last:border-b-0"
              >
                <span class="truncate">{{ item.name || item.product_name || 'Product' }}</span>
                <span class="text-slate-600">×{{ item.quantity || item.qty || 1 }}</span>
              </li>
            </ul>
            <div *ngIf="cart() && cart().length" class="pt-2 border-t mt-2 text-right">
              <span class="text-sm text-slate-600 mr-2">Total:</span>
              <strong>{{ totalPriceDisplay() }}</strong>
            </div>
            <div class="pt-3 text-right">
              <a
                routerLink="/cart"
                class="inline-block bg-slate-900 text-white px-3 py-1 rounded text-sm"
                >View cart</a
              >
            </div>
          </div>
        </div>
      </div>
    </header>
  `,
})
export class HeaderComponent implements OnInit {
  private _cartRaw: WritableSignal<
    CartItem[] | { cart: CartItem[] } | Record<string, CartItem> | null
  > = signal(null);

  readonly cart = computed(() => this.normalizeCart(this._cartRaw()));

  readonly totalCount = computed(() =>
    this.cart().reduce((acc, it) => acc + Number(it?.quantity ?? it?.qty ?? 0), 0),
  );
  readonly totalPriceDisplay = computed(() => {
    const total = this.cart().reduce(
      (acc, it) => acc + Number(it?.price ?? 0) * Number(it?.quantity ?? it?.qty ?? 0),
      0,
    );
    return this.formatPrice(total);
  });

  dropdownOpen = false;

  private cartService = inject(CartService);

  constructor() {}

  ngOnInit(): void {
    const sub = this.cartService.getCartObservable().subscribe((raw) => this._cartRaw.set(raw));
    if (typeof window !== 'undefined') {
      window.addEventListener('unload', () => sub.unsubscribe());
    }

    this.cartService.fetch().catch(() => {});
  }

  toggleDropdown(event: Event) {
    event.stopPropagation();
    this.dropdownOpen = !this.dropdownOpen;
  }

  private formatPrice(n: number) {
    if (!n) return '€0.00';
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'EUR' }).format(n);
  }

  /**
   * Normalize possible backend cart payload shapes into a CartItem array.
   * Supports: [] | { cart: [] } | numeric-keyed object -> array
   */
  private normalizeCart(
    raw: CartItem[] | { cart: CartItem[] } | Record<string, CartItem> | null,
  ): CartItem[] {
    if (!raw) return [];
    if (Array.isArray(raw)) return raw;
    if (typeof raw === 'object') {
      // handle { cart: [...] }
      const asObj = raw as unknown as Record<string, unknown>;
      if ('cart' in asObj && Array.isArray(asObj['cart']))
        return asObj['cart'] as unknown as CartItem[];

      const maybeArray = Object.keys(asObj)
        .filter((k) => !Number.isNaN(Number(k)))
        .sort((a, b) => Number(a) - Number(b))
        .map((k) => asObj[k] as unknown as CartItem);
      if (maybeArray.length) return maybeArray;
    }

    return [];
  }
}
