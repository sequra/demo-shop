import { LitElement, html } from 'lit';
import { repeat } from 'lit/directives/repeat.js';

const MAX_QUANTITY = 10;

export class SeQuraItemList extends LitElement {
  createRenderRoot() {
    return this;
  }

  static properties = {
    items: { type: Array },
    i18n: { type: Object },
    variant: { type: String },
    editable: { type: Boolean }
  };

  constructor() {
    super();
    this.variant = 'default';
    this.editable = false;
  }

  render() {
    const compact = this.variant === 'compact';
    return html`
      <div class="item-list${compact ? ' item-list--compact' : ''}" tabindex="-1">
        ${repeat(this.items, item => item.product.id, item => html`
          <div class="item-list-entry">
            ${item.product.image ? html`<img src="${item.product.image}" alt="${item.product.name}" @error="${e => e.target.style.display = 'none'}">` : ''}
            <div class="item-list-info">
              <span class="item-list-name">${item.product.name}</span>
              ${this.editable ? this._renderQuantityControls(item) : html`
                <span class="item-list-qty">${this.i18n.t('checkout.quantity', { qty: item.quantity })}</span>
              `}
            </div>
            <span class="item-list-price">${this.i18n.formatPrice(item.total)}</span>
          </div>
        `)}
      </div>
    `;
  }

  _renderQuantityControls(item) {
    const name = item.product.name;
    const canDecrease = item.quantity > 1;
    const canIncrease = item.quantity < MAX_QUANTITY;
    return html`
      <div class="item-list-qty-controls">
        <button class="item-list-qty-btn" aria-disabled="${!canDecrease}"
                aria-label="${this.i18n.t('checkout.decreaseQuantity', { name })}"
                @click="${() => canDecrease && this._changeQuantity(item, item.quantity - 1)}">&minus;</button>
        <span class="item-list-qty-value" aria-live="polite">${item.quantity}</span>
        <button class="item-list-qty-btn" aria-disabled="${!canIncrease}"
                aria-label="${this.i18n.t('checkout.increaseQuantity', { name })}"
                @click="${() => canIncrease && this._changeQuantity(item, item.quantity + 1)}">+</button>
        <button class="item-list-remove-btn" ?disabled="${this.items.length <= 1}"
                aria-label="${this.i18n.t('checkout.removeItem', { name })}"
                @click="${e => this._removeItem(e, item)}">${this.i18n.t('cart.remove')}</button>
      </div>
    `;
  }

  async _removeItem(e, item) {
    if (e.detail > 1) return;
    this._changeQuantity(item, 0);
    await this.updateComplete;
    this.querySelector('.item-list')?.focus();
  }

  _changeQuantity(item, quantity) {
    this.dispatchEvent(new CustomEvent('item-quantity-changed', {
      detail: { productId: item.product.id, quantity },
      bubbles: true
    }));
  }
}

customElements.define('sequra-item-list', SeQuraItemList);
