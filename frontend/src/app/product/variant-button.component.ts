import { Component, EventEmitter, Input, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ButtonComponent } from '../ui/button.component';

@Component({
  standalone: true,
  selector: 'app-variant-button',
  imports: [CommonModule, ButtonComponent],
  template: `
    <app-button type="button" [disabled]="disabled" [variant]="disabled ? 'ghost' : (selectedValue===value ? 'primary' : 'ghost')" size="sm" (click)="onClick()">
      {{ value }}
    </app-button>
  `
})
export class VariantButtonComponent {
  @Input() variantName = '';
  @Input() value: string = '';
  @Input() selectedValue: string | undefined;
  @Input() disabled = false;
  @Output() select = new EventEmitter<{ variant: string; value: string }>();

  onClick() {
    if (!this.disabled) {
      this.select.emit({ variant: this.variantName, value: this.value });
    }
  }

  // styling delegated to ButtonComponent
  classes() { return ''; }
}
