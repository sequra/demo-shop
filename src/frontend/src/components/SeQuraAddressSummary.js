import { LitElement, html } from 'lit';
import { stepSummaryTemplate } from './molecules/stepSummaryTemplate.js';

export class SeQuraAddressSummary extends LitElement {
  createRenderRoot() {
    return this;
  }

  static properties = {
    shippingAddress: { type: Object },
    i18n: { type: Object }
  };

  render() {
    const addr = this.shippingAddress;
    return stepSummaryTemplate({
      title: this.i18n.t('address.title'),
      editLabel: this.i18n.t('address.edit'),
      onEdit: () => this._edit(),
      content: html`
        <div class="address-summary-name">${addr.firstName} ${addr.lastName}</div>
        <div class="address-summary-detail">${addr.email}</div>
        <div class="address-summary-detail">${addr.street}</div>
        <div class="address-summary-detail">${addr.city}, ${addr.postalCode}</div>
        <div class="address-summary-detail">${addr.country}</div>
      `
    });
  }

  _edit() {
    this.dispatchEvent(new CustomEvent('edit-address', { bubbles: true }));
  }
}

customElements.define('sequra-address-summary', SeQuraAddressSummary);
