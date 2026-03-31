import { LitElement, html } from 'lit';

export class SeQuraStepIndicator extends LitElement {
  createRenderRoot() {
    return this;
  }

  static properties = {
    currentStep: { type: Number },
    i18n: { type: Object }
  };

  render() {
    const steps = [
      { num: 1, label: this.i18n.t('steps.address') },
      { num: 2, label: this.i18n.t('steps.shipping') },
      { num: 3, label: this.i18n.t('steps.payment') }
    ];
    return html`
      <div class="step-indicator">
        ${steps.map((step, i) => html`
          ${i > 0 ? html`<div class="step-connector ${this.currentStep > step.num ? 'completed' : this.currentStep >= step.num ? 'active' : ''}"></div>` : ''}
          <div class="step ${this.currentStep > step.num ? 'step-completed' : this.currentStep === step.num ? 'step-active' : ''}">
            <div class="step-number">${this.currentStep > step.num ? '\u2713' : step.num}</div>
            <div class="step-label">${step.label}</div>
          </div>
        `)}
      </div>
    `;
  }
}

customElements.define('sequra-step-indicator', SeQuraStepIndicator);
