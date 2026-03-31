import { html } from 'lit';

export function stepSummaryTemplate({ title, editLabel, onEdit, content }) {
  return html`
    <div class="checkout-section">
      <div class="section-header">
        <h3>${title}</h3>
        <button class="edit-address-btn" @click="${onEdit}">${editLabel}</button>
      </div>
      <div class="address-summary">${content}</div>
    </div>
  `;
}
