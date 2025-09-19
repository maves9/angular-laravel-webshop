import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  standalone: true,
  selector: 'app-footer',
  imports: [CommonModule],
  template: `
    <footer class="bg-gray-100 text-gray-800 py-4 mt-8" role="contentinfo" aria-label="Site footer">
      <div class="max-w-4xl mx-auto px-4">
        <small class="text-sm">© {{ year }} Webshop</small>
      </div>
    </footer>
  `,
})
export class FooterComponent {
  year = new Date().getFullYear();
}
