import { TestBed } from '@angular/core/testing'
import { HeaderComponent } from './header.component'
import { provideRouter } from '@angular/router'
import { BehaviorSubject } from 'rxjs'
import { CartService } from '../cart/cart.service'
import { CommonModule } from '@angular/common'
import type { CartItem } from '../cart/cart-types'

describe('HeaderComponent', () => {
  let cartSubject: BehaviorSubject<CartItem[]>
  let mockCartService: Partial<CartService>

  beforeEach(async () => {
    cartSubject = new BehaviorSubject<CartItem[]>([]);
    mockCartService = {
      getCartObservable: () => cartSubject.asObservable(),
      fetch: () => Promise.resolve([]),
    };

    await TestBed.configureTestingModule({
      imports: [CommonModule, HeaderComponent],
      providers: [{ provide: CartService, useValue: mockCartService }, provideRouter([])],
    }).compileComponents()
  })

  it('renders and shows 0 for empty cart', () => {
    const fixture = TestBed.createComponent(HeaderComponent)
    fixture.detectChanges()

    const compiled = fixture.nativeElement as HTMLElement;
    expect(compiled.querySelector('span.inline-block')?.textContent?.trim()).toBe('0')
    expect(compiled.querySelector('a[routerlink="/"]')?.textContent).toContain('Webshop')
  });

  it('updates when cart has items', async () => {
    const fixture = TestBed.createComponent(HeaderComponent)
    fixture.detectChanges();

    cartSubject.next([{ name: 'Test', quantity: 3, price: 1.5 }])
    fixture.detectChanges()

    const compiled = fixture.nativeElement as HTMLElement
    expect(compiled.querySelector('span.inline-block')?.textContent?.trim()).toBe('3')
    expect(compiled.textContent).toContain('€4.50')
  })
})
