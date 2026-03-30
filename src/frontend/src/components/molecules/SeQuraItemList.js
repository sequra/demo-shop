import { LitElement, html } from 'lit';

export class SeQuraItemList extends LitElement {
  createRenderRoot() {
    return this;
  }

  static properties = {
    items: { type: Array },
    i18n: { type: Object },
    variant: { type: String }
  };

  constructor() {
    super();
    this.variant = 'default';
  }

  render() {
    const compact = this.variant === 'compact';
    return html`
      <div class="item-list${compact ? ' item-list--compact' : ''}">
        ${this.items.map(item => html`
          <div class="item-list-entry">
            ${item.product.image ? html`<img src="${item.product.image}" alt="${item.product.name}" @error="${e => e.target.style.display = 'none'}">` : ''}
            <div class="item-list-info">
              <span class="item-list-name">${item.product.name}</span>
              <span class="item-list-qty">${this.i18n.t('checkout.quantity', { qty: item.quantity })}</span>
            </div>
            <span class="item-list-price">${this.i18n.formatPrice(item.product.price)}</span>
          </div>
        `)}
      </div>
    `;
  }
}

customElements.define('sequra-item-list', SeQuraItemList);
