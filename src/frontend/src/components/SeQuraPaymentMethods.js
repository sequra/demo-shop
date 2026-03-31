import { LitElement, html } from 'lit';

export class SeQuraPaymentMethods extends LitElement {
  createRenderRoot() {
    return this;
  }

  static properties = {
    paymentMethods: { type: Array },
    paymentMethodsLoading: { type: Boolean },
    paymentMethodsError: { type: String },
    total: { type: Number },
    i18n: { type: Object }
  };

  render() {
    return html`
      <div class="checkout-section sequra-options">
        <h3>${this.i18n.t('payment.title')}</h3>
        ${this._renderContent()}
        ${this._hasValidMethods() ? html`<button class="save-address-btn" @click="${this._confirm}">${this.i18n.t('shipping.continue')}</button>` : ''}
      </div>
    `;
  }

  _renderContent() {
    if (this.paymentMethodsLoading) {
      return html`<div class="payment-loading"><span class="payment-spinner"></span>${this.i18n.t('payment.loading')}</div>`;
    }

    if (this.paymentMethodsError) {
      return html`
        <div class="payment-error">
          <span>${this.paymentMethodsError}</span>
          <button class="retry-btn" @click="${this._retry}">${this.i18n.t('payment.retry')}</button>
        </div>
      `;
    }

    if (!this.paymentMethods || this.paymentMethods.length === 0) {
      return html`
        <div class="payment-no-methods">
          <span>${this.i18n.t('payment.noMethods')}</span>
        </div>
      `;
    }

    return html`
      <div class="shipping-options">
        ${this.paymentMethods.map((method, index) => html`
          <div class="payment-method-block">
            <label class="shipping-option">
              <input type="radio" name="payment" value="${method.id}" ?checked="${index === 0}">
              ${method.icon ? this._renderIcon(method.icon) : ''}
              <div class="shipping-details">
                <div class="shipping-name">${method.name}</div>
                ${method.costDescription ? html`<div class="shipping-time">${method.costDescription}</div>` : ''}
                ${method.description ? html`<div class="shipping-time">${method.description}</div>` : ''}
                ${method.cost ? html`
                  <div class="shipping-time payment-cost-detail">
                    ${method.cost.instalment_total != null
                      ? this.i18n.formatPrice(method.cost.instalment_total / 100)
                      : ''}
                    ${method.cost.setup_fee ? html` · ${this.i18n.t('payment.setupFee')}: ${this.i18n.formatPrice(method.cost.setup_fee / 100)}` : ''}
                  </div>
                ` : ''}
              </div>
            </label>
            <div class="payment-method-widgets">
              <div class="sequra-promotion-widget"
                   data-amount="${Math.round(this.total * 100)}"
                   data-campaign="YES"
                   data-product="${method.product}"></div>
              <span class="sequra-educational-popup"
                    data-amount="${Math.round(this.total * 100)}"
                    data-product="${method.product}"></span>
            </div>
          </div>
        `)}
      </div>
    `;
  }

  _renderIcon(iconSvg) {
    return html`<span class="svg-icon"><img src="data:image/svg+xml;base64,${btoa(iconSvg)}" alt="payment icon" class="payment-svg-icon"></span>`;
  }

  _confirm() {
    const selected = this.querySelector('input[name="payment"]:checked');
    if (!selected) return;

    const method = this.paymentMethods.find(m => m.id === selected.value);
    if (!method) return;

    this.dispatchEvent(new CustomEvent('payment-confirmed', {
      detail: { productCode: method.product, paymentName: method.name },
      bubbles: true
    }));
  }

  _hasValidMethods() {
    return !this.paymentMethodsLoading && !this.paymentMethodsError && this.paymentMethods?.length > 0;
  }

  _retry() {
    this.dispatchEvent(new CustomEvent('retry-solicitation', { bubbles: true }));
  }
}

customElements.define('sequra-payment-methods', SeQuraPaymentMethods);
