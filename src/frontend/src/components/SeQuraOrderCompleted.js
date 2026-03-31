import { LitElement, html } from 'lit';
import './molecules/SeQuraItemList.js';
import './molecules/SeQuraTotals.js';
import './molecules/SeQuraOrderDetails.js';

export class SeQuraOrderCompleted extends LitElement {
  createRenderRoot() {
    return this;
  }

  static properties = {
    items: { type: Array },
    shippingAddress: { type: Object },
    selectedShipping: { type: String },
    shippingCost: { type: Number },
    discountAmount: { type: Number },
    subtotal: { type: Number },
    total: { type: Number },
    completedMethod: { type: String },
    currentOrderId: { type: String },
    i18n: { type: Object }
  };

  render() {
    const method = this.completedMethod || '';
    const orderNumber = this.currentOrderId
      ? `SQ-${this.currentOrderId.substring(0, 8).toUpperCase()}`
      : `SQ-${Date.now().toString(36).toUpperCase()}`;
    const addr = this.shippingAddress;

    return html`
      <div class="simple-checkout">
        <div class="sq-container thank-you-page">

          <div class="thank-you-hero">
            <div class="thank-you-check">
              <svg viewBox="0 0 52 52" class="thank-you-check-svg">
                <circle cx="26" cy="26" r="25" fill="none" stroke="currentColor" stroke-width="2"/>
                <path fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M14 27l7 7 16-16"/>
              </svg>
            </div>
            <h2 class="thank-you-title">${this.i18n.t('checkout.thankYouTitle')}</h2>
            <p class="thank-you-subtitle">${this.i18n.t('checkout.thankYouSubtitle', { method })}</p>
          </div>

          <div class="thank-you-content">
            <div class="thank-you-left">

              <div class="thank-you-card">
                <div class="thank-you-order-number">
                  <span class="thank-you-label">${this.i18n.t('checkout.orderNumber')}</span>
                  <span class="thank-you-value">${orderNumber}</span>
                </div>
              </div>

              <div class="thank-you-card">
                <p class="thank-you-email-notice">
                  ${this.i18n.t('checkout.confirmationSent')}
                  <strong>${addr.email}</strong>
                </p>
              </div>

              <sequra-order-details
                .title="${this.i18n.t('checkout.orderDetails')}"
                .shippingAddress="${this.shippingAddress}"
                .selectedShipping="${this.selectedShipping}"
                .completedMethod="${this.completedMethod}"
                .i18n="${this.i18n}">
              </sequra-order-details>
            </div>

            <div class="thank-you-right">
              <div class="thank-you-card thank-you-summary">
                <h4>${this.i18n.t('checkout.orderSummary')}</h4>
                <sequra-item-list .items="${this.items}" .i18n="${this.i18n}" variant="compact"></sequra-item-list>
                <sequra-totals
                  .subtotal="${this.subtotal}"
                  .discountAmount="${this.discountAmount}"
                  .shippingCost="${this.shippingCost}"
                  .total="${this.total}"
                  .i18n="${this.i18n}"
                  variant="compact">
                </sequra-totals>
              </div>
            </div>
          </div>

          <div class="thank-you-actions">
            <button class="thank-you-btn-primary" @click="${this._reset}">
              ${this.i18n.t('checkout.backToShop')}
            </button>
          </div>

        </div>
      </div>
    `;
  }

  _reset() {
    this.dispatchEvent(new CustomEvent('reset-order', { bubbles: true }));
  }
}

customElements.define('sequra-order-completed', SeQuraOrderCompleted);
