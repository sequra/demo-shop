import { LitElement, html } from 'lit';

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
      <div class="item-list${compact ? ' item-list--compact' : ''}">
        ${this.items.map(item => html`
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
    return html`
      <div class="item-list-qty-controls">
        <button class="item-list-qty-btn" ?disabled="${item.quantity <= 1}"
                aria-label="${this.i18n.t('checkout.decreaseQuantity')}"
                @click="${() => this._changeQuantity(item, item.quantity - 1)}">&minus;</button>
        <span class="item-list-qty-value">${item.quantity}</span>
        <button class="item-list-qty-btn"
                aria-label="${this.i18n.t('checkout.increaseQuantity')}"
                @click="${() => this._changeQuantity(item, item.quantity + 1)}">+</button>
        <button class="item-list-remove-btn" ?disabled="${this.items.length <= 1}"
                @click="${() => this._changeQuantity(item, 0)}">${this.i18n.t('cart.remove')}</button>
      </div>
    `;
  }

  _changeQuantity(item, quantity) {
    this.dispatchEvent(new CustomEvent('item-quantity-changed', {
      detail: { productId: item.product.id, quantity },
      bubbles: true
    }));
  }
}

customElements.define('sequra-item-list', SeQuraItemList);
