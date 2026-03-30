import { LitElement, html } from 'lit';
import { stepSummaryTemplate } from './molecules/stepSummaryTemplate.js';

export class SeQuraPaymentSummary extends LitElement {
  createRenderRoot() {
    return this;
  }

  static properties = {
    selectedPaymentName: { type: String },
    i18n: { type: Object }
  };

  render() {
    return stepSummaryTemplate({
      title: this.i18n.t('payment.title'),
      editLabel: this.i18n.t('address.edit'),
      onEdit: () => this._edit(),
      content: html`
        <div class="address-summary-name">${this.selectedPaymentName || ''}</div>
      `
    });
  }

  _edit() {
    this.dispatchEvent(new CustomEvent('edit-payment', { bubbles: true }));
  }
}

customElements.define('sequra-payment-summary', SeQuraPaymentSummary);
