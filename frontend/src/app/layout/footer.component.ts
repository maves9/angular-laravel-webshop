import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  standalone: true,
  selector: 'app-footer',
  imports: [CommonModule],
  template: `
    <footer class="site-footer">
      <div class="container">
        <small>© {{ year }} Webshop</small>
      </div>
    </footer>
  `,
  styles: [
    `:host { display:block; }
     .site-footer { background:#f5f5f5; color:#333; padding:1rem 0; margin-top:2rem; }
     .site-footer .container { max-width:1100px; margin:0 auto; padding:0 1rem; }
    `
  ]
})
export class FooterComponent {
  year = new Date().getFullYear();
}
