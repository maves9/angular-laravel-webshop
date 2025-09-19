import { TestBed } from '@angular/core/testing';
import { FooterComponent } from './footer.component';
import { CommonModule } from '@angular/common';

describe('FooterComponent', () => {
  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CommonModule, FooterComponent],
    }).compileComponents();
  });

  it('renders current year and webshop text', () => {
    const fixture = TestBed.createComponent(FooterComponent);
    fixture.detectChanges();

    const compiled = fixture.nativeElement as HTMLElement;
    const year = new Date().getFullYear().toString();
    expect(compiled.textContent).toContain(year);
    expect(compiled.textContent).toContain('Webshop');
  });
});
