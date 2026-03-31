export class SeQuraService {
  constructor() {
    this._sequraScriptLoaded = false;
    this._scriptUri = null;
    this._productCodes = [];
    this._pollingCancelled = false;
  }

  _getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  _normalizePaymentMethods(methods) {
    if (!Array.isArray(methods)) return [];

    return methods.map(method => ({
      id: method.product + (method.campaign ? `-${method.campaign}` : ''),
      product: method.product,
      campaign: method.campaign || null,
      name: method.title || '',
      longTitle: method.long_title || '',
      description: method.claim || method.description || '',
      costDescription: method.cost_description || '',
      icon: method.icon || '',
      cost: method.cost || null,
      minAmount: method.min_amount || 0,
      maxAmount: method.max_amount || Infinity,
      startsAt: method.starts_at || null,
      endsAt: method.ends_at || null
    }));
  }

  async startSolicitation(payload) {
    const response = await fetch('/api/checkout/solicitation', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-Token': this._getCsrfToken()
      },
      body: JSON.stringify(payload)
    });

    if (!response.ok) {
      const text = await response.text();
      throw new Error(`${response.status}: ${text || response.statusText}`);
    }

    const data = await response.json();

    if (data.scriptUri) this._scriptUri = data.scriptUri;
    if (data.merchantRef) this._merchantRef = data.merchantRef;

    return {
      cartId: data.cartId,
      orderRef: data.orderRef,
      assetKey: data.assetKey,
      paymentMethods: this._normalizePaymentMethods(data.paymentMethods)
    };
  }

  async fetchIdentificationForm({ cartId, productCode, containerEl, onApproved, onRejected, onClose, onFormReady }) {
    window.__sequraApproved = onApproved;
    window.__sequraRejected = onRejected;

    const url = `/api/checkout/form?cartId=${encodeURIComponent(cartId)}&product=${encodeURIComponent(productCode)}`;
    const response = await fetch(url, {
      headers: {
        'Accept': 'text/html',
        'X-CSRF-Token': this._getCsrfToken()
      }
    });

    if (!response.ok) {
      const errorBody = await response.text();
      throw new Error(`${response.status} - ${errorBody}`);
    }

    const formHtml = await response.text();

    if (containerEl) {
      containerEl.innerHTML = formHtml;

      // Execute scripts from injected HTML
      const scripts = containerEl.querySelectorAll('script');
      scripts.forEach(oldScript => {
        const newScript = document.createElement('script');
        if (oldScript.src) {
          newScript.src = oldScript.src;
          newScript.async = true;
        } else {
          newScript.textContent = oldScript.textContent;
        }
        oldScript.parentNode.replaceChild(newScript, oldScript);
      });
    }

    // Wait for SequraFormInstance to be available, then show it
    return new Promise((resolve, reject) => {
      let attempts = 0;
      const checkInterval = setInterval(() => {
        attempts++;
        if (window.SequraFormInstance) {
          clearInterval(checkInterval);
          window.SequraFormInstance.setCloseCallback(onClose);
          window.SequraFormInstance.show();
          if (onFormReady) onFormReady();
          resolve();
        }
        if (attempts > 100) {
          clearInterval(checkInterval);
          reject(new Error('Timed out waiting for SeQura form'));
        }
      }, 100);
    });
  }

  loadScript({ assetKey, products, scriptUri, decimalSeparator, thousandSeparator, locale, currency }) {
    if (this._sequraScriptLoaded) return;
    if (!this._merchantRef || !assetKey || !products || products.length === 0) return;

    this._productCodes = products;

    const config = {
      merchant: this._merchantRef,
      assetKey,
      products,
      scriptUri: scriptUri || this._scriptUri || 'https://sandbox.sequracdn.com/assets/sequra-checkout.min.js',
      decimalSeparator,
      thousandSeparator,
      locale,
      currency
    };

    window.SequraConfiguration = config;
    window.SequraOnLoad = [];
    window.Sequra = {};
    window.Sequra.onLoad = function(callback) {
      window.SequraOnLoad.push(callback);
    };

    const existingScript = document.querySelector('script[src*="sequra-checkout"]');
    if (existingScript) {
      existingScript.remove();
    }

    const script = document.createElement('script');
    script.async = true;
    script.src = config.scriptUri;
    script.onload = () => {
      this._sequraScriptLoaded = true;
      if (window.Sequra && window.Sequra.refreshComponents) {
        window.Sequra.refreshComponents();
      }
    };
    document.head.appendChild(script);
  }

  reloadScript(config) {
    this._sequraScriptLoaded = false;
    this.loadScript(config);
  }

  async pollOrderStatus(orderId, { onConfirmed, onHold, onTimeout, isApproved, maxAttempts = 30 }) {
    this._pollingCancelled = false;
    for (let i = 0; i < maxAttempts; i++) {
      if (this._pollingCancelled) return;
      await new Promise(r => setTimeout(r, 2000));
      try {
        const res = await fetch(`/api/orders/${encodeURIComponent(orderId)}/status`, {
          headers: { 'X-CSRF-Token': this._getCsrfToken() }
        });
        if (!res.ok) continue;
        const data = await res.json();
        if (data.ipnReceived && data.status === 'confirmed') {
          onConfirmed();
          return;
        }
        if (data.ipnReceived && data.status === 'on_hold') {
          onHold();
          return;
        }
      } catch { /* retry */ }
    }
    if (this._pollingCancelled) return;
    // User hasn't submitted the form yet — keep polling silently
    if (!isApproved()) {
      console.log('[SeQura] Polling timeout but form not yet submitted, restarting...');
      this.pollOrderStatus(orderId, { onConfirmed, onHold, onTimeout, isApproved, maxAttempts });
      return;
    }
    // Form was submitted but IPN didn't arrive
    onTimeout();
  }

  cancelPolling() {
    this._pollingCancelled = true;
  }

  async checkStatus(orderId) {
    const res = await fetch(`/api/orders/${encodeURIComponent(orderId)}/status`, {
      headers: { 'X-CSRF-Token': this._getCsrfToken() }
    });
    if (!res.ok) throw new Error(`Status check failed: ${res.status}`);
    return res.json();
  }
}
