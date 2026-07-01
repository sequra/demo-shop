import { LitElement, html } from 'lit';

export class SeQuraAddressForm extends LitElement {
  createRenderRoot() {
    return this;
  }

  static properties = {
    shippingAddress: { type: Object },
    supportedCountries: { type: Array },
    i18n: { type: Object }
  };

  render() {
    const addr = this.shippingAddress;
    return html`
      <div class="checkout-section">
        <h3>${this.i18n.t('address.title')}</h3>
        <div class="address-form">
          <div class="form-row">
            <div class="form-field">
              <label>${this.i18n.t('address.firstName')}</label>
              <input type="text" .value="${addr.firstName}" @input="${(e) => this._updateField('firstName', e.target.value)}">
            </div>
            <div class="form-field">
              <label>${this.i18n.t('address.lastName')}</label>
              <input type="text" .value="${addr.lastName}" @input="${(e) => this._updateField('lastName', e.target.value)}">
            </div>
          </div>
          <div class="form-field">
            <label>${this.i18n.t('address.email')}</label>
            <input type="email" .value="${addr.email}" @input="${(e) => this._updateField('email', e.target.value)}">
          </div>
          <div class="form-field">
            <label>${this.i18n.t('address.street')}</label>
            <input type="text" .value="${addr.street}" @input="${(e) => this._updateField('street', e.target.value)}">
          </div>
          <div class="form-row">
            <div class="form-field">
              <label>${this.i18n.t('address.city')}</label>
              <input type="text" .value="${addr.city}" @input="${(e) => this._updateField('city', e.target.value)}">
            </div>
            <div class="form-field">
              <label>${this.i18n.t('address.postalCode')}</label>
              <input type="text" .value="${addr.postalCode}" @input="${(e) => this._updateField('postalCode', e.target.value)}">
            </div>
          </div>
          <div class="form-field">
            <label>${this.i18n.t('address.country')}</label>
            <select .value="${addr.country}" @change="${(e) => this._updateField('country', e.target.value)}">
              ${this.supportedCountries.length
                ? this.supportedCountries.map(code => html`
                    <option value="${code}">${this.i18n.countryName(code)}</option>`)
                : html`<option value="" disabled selected>—</option>`}
            </select>
          </div>
          <button class="save-address-btn" @click="${this._saveAddress}">${this.i18n.t('shipping.continue')}</button>
        </div>
      </div>
    `;
  }

  _updateField(field, value) {
    this.dispatchEvent(new CustomEvent('address-field-changed', { detail: { field, value }, bubbles: true }));
  }

  _saveAddress() {
    const { firstName, lastName, email, street, city, postalCode } = this.shippingAddress;
    if (!firstName.trim() || !lastName.trim() || !email.trim() || !street.trim() || !city.trim() || !postalCode.trim()) {
      this.dispatchEvent(new CustomEvent('address-validation-error', { bubbles: true }));
      return;
    }
    this.dispatchEvent(new CustomEvent('address-saved', { detail: { address: this.shippingAddress }, bubbles: true }));
  }
}

customElements.define('sequra-address-form', SeQuraAddressForm);
