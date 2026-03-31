import { LitElement, html } from 'lit';
import './SeQuraDiscountInput.js';
import './molecules/SeQuraItemList.js';
import './molecules/SeQuraTotals.js';

export class SeQuraOrderSummary extends LitElement {
  createRenderRoot() {
    return this;
  }

  static properties = {
    items: { type: Array },
    subtotal: { type: Number },
    discountAmount: { type: Number },
    discountCode: { type: String },
    shippingCost: { type: Number },
    total: { type: Number },
    currentStep: { type: Number },
    solicitationLoading: { type: Boolean },
    i18n: { type: Object }
  };

  render() {
    return html`
      <div class="order-summary">
        <h3>${this.i18n.t('checkout.orderSummary')}</h3>

        <sequra-item-list .items="${this.items}" .i18n="${this.i18n}"></sequra-item-list>

        <sequra-discount-input
          .discountCode="${this.discountCode}"
          .discountAmount="${this.discountAmount}"
          .subtotal="${this.subtotal}"
          .i18n="${this.i18n}">
        </sequra-discount-input>

        <sequra-totals
          .subtotal="${this.subtotal}"
          .discountAmount="${this.discountAmount}"
          .shippingCost="${this.shippingCost}"
          .total="${this.total}"
          .i18n="${this.i18n}">
        </sequra-totals>

        <button class="complete-order-btn" ?disabled="${this.currentStep < 4 || this.solicitationLoading}" @click="${this._completeOrder}">
          ${this.solicitationLoading
            ? html`<span class="btn-spinner"></span>`
            : this.i18n.t('checkout.completeOrder')}
        </button>
      </div>
    `;
  }

  _completeOrder() {
    this.dispatchEvent(new CustomEvent('complete-order', { bubbles: true }));
  }
}

customElements.define('sequra-order-summary', SeQuraOrderSummary);
