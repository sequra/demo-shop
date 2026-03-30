import { LitElement, html } from 'lit';
import './molecules/SeQuraItemList.js';
import './molecules/SeQuraTotals.js';
import './molecules/SeQuraOrderDetails.js';

export class SeQuraOrderPending extends LitElement {
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
    checkingStatus: { type: Boolean },
    i18n: { type: Object }
  };

  render() {
    const orderNumber = this.currentOrderId
      ? `SQ-${this.currentOrderId.substring(0, 8).toUpperCase()}`
      : `SQ-${Date.now().toString(36).toUpperCase()}`;

    return html`
      <div class="simple-checkout">
        <div class="sq-container thank-you-page">

          <div class="pending-hero">
            <div class="pending-icon">
              <svg viewBox="0 0 52 52" class="pending-icon-svg">
                <circle cx="26" cy="26" r="25" fill="none" stroke="currentColor" stroke-width="2"/>
                <circle cx="26" cy="26" r="3" fill="currentColor"/>
                <line x1="26" y1="26" x2="26" y2="14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                <line x1="26" y1="26" x2="34" y2="26" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
              </svg>
            </div>
            <h2 class="thank-you-title">${this.i18n.t('checkout.pendingTitle')}</h2>
            <p class="thank-you-subtitle">${this.i18n.t('checkout.pendingSubtitle')}</p>
          </div>

          <div class="thank-you-content">
            <div class="thank-you-left">

              <div class="thank-you-card">
                <div class="thank-you-order-number">
                  <span class="thank-you-label">${this.i18n.t('checkout.orderNumber')}</span>
                  <span class="thank-you-value">${orderNumber}</span>
                </div>
              </div>

              <sequra-order-details
                .title="${this.i18n.t('checkout.orderInfo')}"
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

          <div class="pending-actions">
            <button class="check-status-btn" ?disabled="${this.checkingStatus}" @click="${this._checkStatus}">
              ${this.checkingStatus ? html`<span class="btn-spinner"></span>` : ''}
              ${this.i18n.t('checkout.checkStatus')}
            </button>
            <button class="thank-you-btn-primary pending-secondary-btn" @click="${this._reset}">
              ${this.i18n.t('checkout.startNewCheckout')}
            </button>
          </div>

        </div>
      </div>
    `;
  }

  _checkStatus() {
    this.dispatchEvent(new CustomEvent('check-status', { bubbles: true }));
  }

  _reset() {
    this.dispatchEvent(new CustomEvent('reset-order', { bubbles: true }));
  }
}

customElements.define('sequra-order-pending', SeQuraOrderPending);
