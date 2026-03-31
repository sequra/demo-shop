import { LitElement, html } from 'lit';

export class SeQuraNotification extends LitElement {
  createRenderRoot() {
    return this;
  }

  render() {
    return html`<div id="notification" class="notification"></div>`;
  }

  show(message, type = 'success', duration = 3000) {
    const notification = this.querySelector('#notification');
    if (!notification) return;

    notification.textContent = message;
    notification.className = `notification ${type}`;

    requestAnimationFrame(() => {
      notification.classList.add('show');
    });

    setTimeout(() => {
      notification.classList.remove('show');
    }, duration);
  }
}

customElements.define('sequra-notification', SeQuraNotification);
