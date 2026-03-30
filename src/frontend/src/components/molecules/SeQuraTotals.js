import { LitElement, html } from 'lit';

export class SeQuraTotals extends LitElement {
  createRenderRoot() {
    return this;
  }

  static properties = {
    subtotal: { type: Number },
    discountAmount: { type: Number },
    shippingCost: { type: Number },
    total: { type: Number },
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
      <div class="order-totals${compact ? ' order-totals--compact' : ''}">
        <div class="order-totals-row">
          <span>${this.i18n.t('checkout.subtotal')}</span>
          <span>${this.i18n.formatPrice(this.subtotal)}</span>
        </div>
        ${this.discountAmount > 0 ? html`
          <div class="order-totals-row discount">
            <span>${this.i18n.t('checkout.discount')}</span>
            <span>-${this.i18n.formatPrice(this.discountAmount)}</span>
          </div>
        ` : ''}
        <div class="order-totals-row">
          <span>${this.i18n.t('checkout.shipping')}</span>
          <span>${this.shippingCost === 0 ? this.i18n.t('general.free') : this.i18n.formatPrice(this.shippingCost)}</span>
        </div>
        <div class="order-totals-row final">
          <span>${this.i18n.t('checkout.total')}</span>
          <span>${this.i18n.formatPrice(this.total)}</span>
        </div>
      </div>
    `;
  }
}

customElements.define('sequra-totals', SeQuraTotals);
