import { LitElement, html } from 'lit';

export class SeQuraDiscountInput extends LitElement {
  createRenderRoot() {
    return this;
  }

  static properties = {
    discountCode: { type: String },
    discountAmount: { type: Number },
    subtotal: { type: Number },
    i18n: { type: Object }
  };

  render() {
    return html`
      <div class="discount-section">
        <div class="discount-input-wrapper">
          <input type="text" placeholder="${this.i18n.t('discount.placeholder')}"
                 .value="${this.discountCode}"
                 @input="${(e) => this.dispatchEvent(new CustomEvent('discount-code-changed', { detail: { code: e.target.value }, bubbles: true }))}">
          <span class="discount-tooltip">${this.i18n.t('discount.tooltip')}</span>
        </div>
        <button class="apply-discount-btn" @click="${this._apply}">${this.i18n.t('discount.apply')}</button>
        ${this.discountAmount > 0 ? html`
          <div class="discount-applied">
            ${this.i18n.t('discount.applied', { amount: this.i18n.formatPrice(this.discountAmount), percent: ((this.discountAmount / this.subtotal) * 100).toFixed(0) })}
            <button class="remove-discount-btn" @click="${this._remove}">${this.i18n.t('discount.remove')}</button>
          </div>
        ` : ''}
      </div>
    `;
  }

  _apply() {
    this.dispatchEvent(new CustomEvent('discount-apply', { detail: { code: this.discountCode }, bubbles: true }));
  }

  _remove() {
    this.dispatchEvent(new CustomEvent('discount-remove', { bubbles: true }));
  }
}

customElements.define('sequra-discount-input', SeQuraDiscountInput);
