import { LitElement, html } from 'lit';
import { I18nService } from '../services/I18nService.js';

export class SeQuraSettingsPanel extends LitElement {
  createRenderRoot() {
    return this;
  }

  static properties = {
    language: { type: String },
    currency: { type: String },
    i18n: { type: Object }
  };

  render() {
    return html`
      <div class="credentials-panel">
        <div class="credentials-header">
          <h3>${this.i18n.t('settings.title')}</h3>
          <button class="credentials-close" @click="${this._close}">&#10005;</button>
        </div>
        <div class="credentials-form">
          <div class="credentials-field">
            <label>${this.i18n.t('settings.language')}</label>
            <select .value="${this.language}" @change="${this._onLanguageChange}">
              ${Object.entries(I18nService.LANGUAGES).map(([code, name]) => html`
                <option value="${code}" ?selected="${this.language === code}">${name}</option>
              `)}
            </select>
          </div>
          <div class="credentials-field">
            <label>${this.i18n.t('settings.currency')}</label>
            <select .value="${this.currency}" @change="${this._onCurrencyChange}">
              ${Object.entries(I18nService.CURRENCIES).map(([code, config]) => html`
                <option value="${code}" ?selected="${this.currency === code}">${config.symbol} ${code}</option>
              `)}
            </select>
          </div>
        </div>
      </div>
    `;
  }

  _close() {
    this.dispatchEvent(new CustomEvent('settings-close', { bubbles: true }));
  }

  _onLanguageChange(e) {
    this.dispatchEvent(new CustomEvent('language-changed', { detail: { language: e.target.value }, bubbles: true }));
  }

  _onCurrencyChange(e) {
    this.dispatchEvent(new CustomEvent('currency-changed', { detail: { currency: e.target.value }, bubbles: true }));
  }
}

customElements.define('sequra-settings-panel', SeQuraSettingsPanel);
