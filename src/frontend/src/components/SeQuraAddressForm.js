import { LitElement, html } from 'lit';
import { StorageService } from '../services/StorageService.js';

const PROFILES_KEY = 'shopper-profiles';
const TOOLS_KEY = 'tools-enabled';
const PROFILE_FIELDS = ['firstName', 'lastName', 'email', 'street', 'city', 'postalCode', 'country'];

export class SeQuraAddressForm extends LitElement {
  createRenderRoot() {
    return this;
  }

  static properties = {
    shippingAddress: { type: Object },
    i18n: { type: Object },
    _profiles: { state: true },
    _alias: { state: true },
    _selectedAlias: { state: true }
  };

  constructor() {
    super();
    this._storage = new StorageService();
    this._toolsEnabled = this._resolveToolsFlag();
    this._profiles = this._toolsEnabled ? (this._storage.get(PROFILES_KEY) || {}) : {};
    this._alias = '';
    this._selectedAlias = '';
  }

  render() {
    const addr = this.shippingAddress;
    return html`
      <div class="checkout-section">
        <h3>${this.i18n.t('address.title')}</h3>
        ${this._toolsEnabled ? this._renderProfilePicker() : ''}
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
              <option value="Spain">${this.i18n.t('country.spain')}</option>
              <option value="France">${this.i18n.t('country.france')}</option>
              <option value="Germany">${this.i18n.t('country.germany')}</option>
              <option value="Italy">${this.i18n.t('country.italy')}</option>
              <option value="Portugal">${this.i18n.t('country.portugal')}</option>
            </select>
          </div>
          ${this._toolsEnabled ? this._renderProfileSave() : ''}
          <button class="save-address-btn" @click="${this._saveAddress}">${this.i18n.t('shipping.continue')}</button>
        </div>
      </div>
    `;
  }

  _resolveToolsFlag() {
    const param = new URLSearchParams(window.location.search).get('tools');
    if (param === '1') {
      this._storage.set(TOOLS_KEY, true);
      return true;
    }
    if (param === '0') {
      this._storage.remove(TOOLS_KEY);
      return false;
    }
    return this._storage.get(TOOLS_KEY) === true;
  }

  _renderProfilePicker() {
    const aliases = Object.keys(this._profiles).sort((a, b) => a.localeCompare(b));
    if (aliases.length === 0) return '';
    return html`
      <div class="shopper-profiles">
        <div class="shopper-profiles__row">
          <div class="form-field">
            <label>${this.i18n.t('profiles.load')}</label>
            <select .value="${this._selectedAlias}" @change="${(e) => this._selectProfile(e.target.value)}">
              <option value="" ?selected="${!this._selectedAlias}">${this.i18n.t('profiles.choose')}</option>
              ${aliases.map(alias => html`<option value="${alias}" ?selected="${alias === this._selectedAlias}">${alias}</option>`)}
            </select>
          </div>
          <button class="edit-address-btn" ?disabled="${!this._selectedAlias}" @click="${this._deleteProfile}">${this.i18n.t('profiles.delete')}</button>
        </div>
      </div>
    `;
  }

  _renderProfileSave() {
    const exists = Object.prototype.hasOwnProperty.call(this._profiles, this._alias.trim());
    return html`
      <div class="shopper-profiles shopper-profiles--save">
        <h4 class="shopper-profiles__title">${this.i18n.t('profiles.saveTitle')}</h4>
        <p class="shopper-profiles__hint">${this.i18n.t('profiles.saveHint')}</p>
        <div class="shopper-profiles__row">
          <div class="form-field">
            <label>${this.i18n.t('profiles.alias')}</label>
            <input type="text" .value="${this._alias}" placeholder="${this.i18n.t('profiles.aliasPlaceholder')}" @input="${(e) => this._alias = e.target.value}">
          </div>
          <button class="edit-address-btn" ?disabled="${!this._alias.trim()}" @click="${this._saveProfile}">${this.i18n.t(exists ? 'profiles.update' : 'profiles.save')}</button>
        </div>
      </div>
    `;
  }

  _selectProfile(alias) {
    this._selectedAlias = alias;
    const profile = this._profiles[alias];
    if (!profile) return;
    this._alias = alias;
    PROFILE_FIELDS.forEach(field => {
      if (profile[field] !== undefined) this._updateField(field, profile[field]);
    });
  }

  _saveProfile() {
    const alias = this._alias.trim();
    if (!alias) return;
    const profile = Object.fromEntries(PROFILE_FIELDS.map(field => [field, this.shippingAddress[field]]));
    this._persistProfiles({ ...this._profiles, [alias]: profile }, 'profiles.saved_ok', { alias });
    this._selectedAlias = alias;
  }

  _deleteProfile() {
    const alias = this._selectedAlias;
    if (!alias) return;
    const { [alias]: _removed, ...rest } = this._profiles;
    this._persistProfiles(rest, 'profiles.deleted', { alias });
    this._selectedAlias = '';
    this._alias = '';
  }

  _persistProfiles(profiles, messageKey, params) {
    this._profiles = profiles;
    const ok = this._storage.set(PROFILES_KEY, profiles);
    this._notify(ok ? this.i18n.t(messageKey, params) : this.i18n.t('profiles.storageError'), ok ? 'success' : 'error');
  }

  _notify(message, type) {
    this.closest('sequra-checkout')?.showNotification(message, type);
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
