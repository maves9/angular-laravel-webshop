import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  standalone: true,
  selector: 'app-button',
  imports: [CommonModule],
  template: `
    <button
      [type]="type"
      [disabled]="disabled"
      [attr.aria-disabled]="disabled ? 'true' : null"
      (click)="onClick($event)"
      [ngClass]="classes()">
      <ng-content></ng-content>
    </button>
  `,
})
export class ButtonComponent {
  @Input() type: 'button' | 'submit' | 'reset' = 'button';
  @Input() disabled = false;
  @Input() variant: 'primary' | 'ghost' = 'primary';
  @Input() size: 'sm' | 'md' = 'md';

  onClick(ev: Event) {
    if (this.disabled) {
      ev.preventDefault();
      ev.stopImmediatePropagation();
      return;
    }
    // noop - consumers can bind (click) on this component if they want
  }

  classes() {
    const base = 'rounded focus:outline-none transition inline-flex items-center justify-center';
    const sizeCls = this.size === 'sm' ? 'px-3 py-1 text-sm' : 'px-4 py-2 text-base';
    if (this.disabled) {
      return `${base} ${sizeCls} bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed`;
    }
    if (this.variant === 'ghost') {
      return `${base} ${sizeCls} bg-white text-sky-700 hover:bg-slate-50 border border-slate-200`;
    }
    // primary
    return `${base} ${sizeCls} bg-sky-600 text-white hover:bg-sky-700`;
  }
}
