import { LitElement, html } from 'lit';

export class SeQuraOrderDetails extends LitElement {
  createRenderRoot() {
    return this;
  }

  static properties = {
    title: { type: String },
    shippingAddress: { type: Object },
    selectedShipping: { type: String },
    completedMethod: { type: String },
    i18n: { type: Object }
  };

  render() {
    const addr = this.shippingAddress;
    return html`
      <div class="thank-you-card thank-you-details">
        <h4>${this.title}</h4>
        <div class="thank-you-detail-row">
          <span class="thank-you-label">${this.i18n.t('payment.title')}</span>
          <span>${this.completedMethod || ''}</span>
        </div>
        <div class="thank-you-detail-row">
          <span class="thank-you-label">${this.i18n.t('shipping.title')}</span>
          <span>${this.i18n.t(`shipping.${this.selectedShipping}`) || this.selectedShipping}</span>
        </div>
        <div class="thank-you-detail-row">
          <span class="thank-you-label">${this.i18n.t('address.title')}</span>
          <span>${addr.firstName} ${addr.lastName}, ${addr.city}</span>
        </div>
      </div>
    `;
  }
}

customElements.define('sequra-order-details', SeQuraOrderDetails);
