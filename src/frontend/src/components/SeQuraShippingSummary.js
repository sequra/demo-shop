import { LitElement, html } from 'lit';
import { stepSummaryTemplate } from './molecules/stepSummaryTemplate.js';

export class SeQuraShippingSummary extends LitElement {
  createRenderRoot() {
    return this;
  }

  static properties = {
    selectedShipping: { type: String },
    shippingCost: { type: Number },
    i18n: { type: Object }
  };

  render() {
    const shippingNames = {
      standard: this.i18n.t('shipping.standard'),
      express: this.i18n.t('shipping.express'),
      overnight: this.i18n.t('shipping.overnight'),
      free: this.i18n.t('general.free')
    };
    return stepSummaryTemplate({
      title: this.i18n.t('shipping.title'),
      editLabel: this.i18n.t('address.edit'),
      onEdit: () => this._edit(),
      content: html`
        <div class="address-summary-name">${shippingNames[this.selectedShipping] || this.selectedShipping}</div>
        <div class="address-summary-detail">${this.shippingCost === 0 ? this.i18n.t('general.free') : this.i18n.formatPrice(this.shippingCost)}</div>
      `
    });
  }

  _edit() {
    this.dispatchEvent(new CustomEvent('edit-shipping', { bubbles: true }));
  }
}

customElements.define('sequra-shipping-summary', SeQuraShippingSummary);
