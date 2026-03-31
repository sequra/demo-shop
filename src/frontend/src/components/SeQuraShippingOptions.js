import { LitElement, html } from 'lit';

export class SeQuraShippingOptions extends LitElement {
  createRenderRoot() {
    return this;
  }

  static properties = {
    selectedShipping: { type: String },
    shippingCost: { type: Number },
    solicitationLoading: { type: Boolean },
    i18n: { type: Object }
  };

  render() {
    return html`
      <div class="checkout-section">
        <h3>${this.i18n.t('shipping.title')}</h3>
        <div class="shipping-options">
          <label class="shipping-option">
            <input type="radio" name="shipping" value="standard"
                   ?checked="${this.selectedShipping === 'standard'}"
                   @change="${() => this._select('standard', 5.99)}">
            <div class="shipping-details">
              <div class="shipping-name">${this.i18n.t('shipping.standard')}</div>
              <div class="shipping-time">${this.i18n.t('shipping.standardTime')}</div>
            </div>
            <div class="shipping-price">${this.i18n.formatPrice(5.99)}</div>
          </label>

          <label class="shipping-option">
            <input type="radio" name="shipping" value="express"
                   ?checked="${this.selectedShipping === 'express'}"
                   @change="${() => this._select('express', 12.99)}">
            <div class="shipping-details">
              <div class="shipping-name">${this.i18n.t('shipping.express')}</div>
              <div class="shipping-time">${this.i18n.t('shipping.expressTime')}</div>
            </div>
            <div class="shipping-price">${this.i18n.formatPrice(12.99)}</div>
          </label>

          <label class="shipping-option">
            <input type="radio" name="shipping" value="overnight"
                   ?checked="${this.selectedShipping === 'overnight'}"
                   @change="${() => this._select('overnight', 24.99)}">
            <div class="shipping-details">
              <div class="shipping-name">${this.i18n.t('shipping.overnight')}</div>
              <div class="shipping-time">${this.i18n.t('shipping.overnightTime')}</div>
            </div>
            <div class="shipping-price">${this.i18n.formatPrice(24.99)}</div>
          </label>
        </div>
        <button class="save-address-btn" ?disabled="${this.solicitationLoading}" @click="${this._confirm}">
          ${this.solicitationLoading ? this.i18n.t('checkout.processing') : this.i18n.t('shipping.continue')}
        </button>
      </div>
    `;
  }

  _select(method, cost) {
    this.dispatchEvent(new CustomEvent('shipping-selected', { detail: { method, cost }, bubbles: true }));
  }

  _confirm() {
    this.dispatchEvent(new CustomEvent('shipping-confirmed', { bubbles: true }));
  }
}

customElements.define('sequra-shipping-options', SeQuraShippingOptions);
