import { LitElement, html } from 'lit';
import { ProductService } from '../services/ProductService.js';
import { I18nService } from '../services/I18nService.js';
import { CartItem } from '../models/CartItem.js';
import { OrderBuilderService } from '../services/OrderBuilderService.js';
import { SeQuraService } from '../services/SeQuraService.js';
import { DiscountService } from '../services/DiscountService.js';
import './SeQuraNotification.js';
import './SeQuraStepIndicator.js';
import './SeQuraSettingsPanel.js';
import './SeQuraAddressForm.js';
import './SeQuraAddressSummary.js';
import './SeQuraShippingOptions.js';
import './SeQuraShippingSummary.js';
import './SeQuraPaymentMethods.js';
import './SeQuraPaymentSummary.js';
import './SeQuraOrderSummary.js';
import './SeQuraOrderCompleted.js';
import './SeQuraOrderPending.js';
import checkoutStyles from '../styles/sequra-checkout.scss?inline';

// Inject checkout styles into document head
const _checkoutStyle = document.createElement('style');
_checkoutStyle.textContent = checkoutStyles;
document.head.appendChild(_checkoutStyle);

export class SeQuraCheckout extends LitElement {
    createRenderRoot() {
        return this;
    }

    static properties = {
        items: { type: Array },
        paymentMethods: { type: Array },
        paymentMethodsLoading: { type: Boolean },
        paymentMethodsError: { type: String },
        shippingCost: { type: Number },
        selectedShipping: { type: String },
        discountCode: { type: String },
        discountAmount: { type: Number },
        shippingAddress: { type: Object },
        orderCompleted: { type: Boolean },
        language: { type: String },
        currency: { type: String },
        showSettingsPanel: { type: Boolean },
        addressSaved: { type: Boolean },
        currentStep: { type: Number },
        solicitationLoading: { type: Boolean },
        orderPending: { type: Boolean },
        i18n: { type: Object }
    };

    constructor() {
        super();

        this.assetKey = this.getAttribute('asset-key') || '';
        this.i18n = new I18nService();
        this.orderBuilder = new OrderBuilderService();
        this.sequraService = new SeQuraService();
        this.discountService = new DiscountService();

        this.language = this.i18n.currentLanguage;
        this.currency = this.i18n.currentCurrency;
        this._prevCurrency = this.i18n.currentCurrency;

        this.productService = new ProductService(this.i18n);
        this.items = this.productService.getAllProducts().map(p => new CartItem(p, p.quantity));

        this.i18n.addListener(() => {
            const currencyChanged = this._prevCurrency !== this.i18n.currentCurrency;
            this._prevCurrency = this.i18n.currentCurrency;
            this.language = this.i18n.currentLanguage;
            this.currency = this.i18n.currentCurrency;
            this.requestUpdate('i18n');
            this.items = this.productService.getAllProducts().map(p => new CartItem(p, p.quantity));
            this._reloadSequraScript();
            if (currencyChanged) {
                this._refreshOrderIfNeeded();
            }
        });

        this.paymentMethods = [];
        this.paymentMethodsLoading = false;
        this.paymentMethodsError = '';
        this.shippingCost = 5.99;
        this.selectedShipping = 'standard';
        this.discountCode = '';
        this.discountAmount = 0;
        this.shippingAddress = {
            firstName: '', lastName: '', email: '', street: '', city: '', postalCode: '', country: 'Spain'
        };
        this.orderCompleted = false;
        this.orderPending = false;
        this.showSettingsPanel = false;
        this.addressSaved = false;
        this.currentStep = 1;
        this.solicitationLoading = false;
        this._cartId = null;
        this._orderRef = null;
        this._selectedProductCode = null;
        this._selectedPaymentName = null;
        this._completedMethod = '';
        this._currentOrderId = null;
        this._checkingStatus = false;
        this._approvedCallbackFired = false;
    }

    get _subtotal() {
        return this.items.reduce((sum, item) => sum + item.total, 0);
    }

    get _total() {
        return this._subtotal - this.discountAmount + this.shippingCost;
    }

    // --- Render ---

    render() {
        if (this.orderCompleted) {
            return html`
                <sequra-order-completed
                        .items="${this.items}"
                        .shippingAddress="${this.shippingAddress}"
                        .selectedShipping="${this.selectedShipping}"
                        .shippingCost="${this.shippingCost}"
                        .discountAmount="${this.discountAmount}"
                        .subtotal="${this._subtotal}"
                        .total="${this._total}"
                        .completedMethod="${this._completedMethod}"
                        .currentOrderId="${this._currentOrderId}"
                        .i18n="${this.i18n}"
                        @reset-order="${this._resetOrder}">
                </sequra-order-completed>
                <sequra-notification></sequra-notification>
            `;
        }

        if (this.orderPending) {
            return html`
                <sequra-order-pending
                        .items="${this.items}"
                        .shippingAddress="${this.shippingAddress}"
                        .selectedShipping="${this.selectedShipping}"
                        .shippingCost="${this.shippingCost}"
                        .discountAmount="${this.discountAmount}"
                        .subtotal="${this._subtotal}"
                        .total="${this._total}"
                        .completedMethod="${this._completedMethod}"
                        .currentOrderId="${this._currentOrderId}"
                        .checkingStatus="${this._checkingStatus}"
                        .i18n="${this.i18n}"
                        @check-status="${this._manualCheckStatus}"
                        @reset-order="${this._resetOrder}">
                </sequra-order-pending>
                <sequra-notification></sequra-notification>
            `;
        }

        return html`
            <div class="floating-settings-container">
                <button class="settings-toggle-btn"
                        @click="${() => this.showSettingsPanel = !this.showSettingsPanel}"
                        title="${this.i18n.t('settings.title')}">
                    &#9881;&#65039;
                </button>
                ${this.showSettingsPanel ? html`
                    <sequra-settings-panel
                            .language="${this.language}"
                            .currency="${this.currency}"
                            .i18n="${this.i18n}"
                            @settings-close="${() => this.showSettingsPanel = false}"
                            @language-changed="${(e) => this.i18n.setLanguage(e.detail.language)}"
                            @currency-changed="${(e) => this.i18n.setCurrency(e.detail.currency)}">
                    </sequra-settings-panel>
                ` : ''}
            </div>

            <div class="simple-checkout">
                <div class="sq-container">
                    <div class="checkout-header">
                        <h2>${this.i18n.t('checkout.title')}</h2>
                    </div>

                    <div class="checkout-content">
                        <div class="checkout-left">
                            <sequra-step-indicator .currentStep="${this.currentStep}" .i18n="${this.i18n}"></sequra-step-indicator>

                            ${this.currentStep === 1
                                    ? html`<sequra-address-form
                                            .shippingAddress="${this.shippingAddress}"
                                            .i18n="${this.i18n}"
                                            @address-field-changed="${this._onAddressFieldChanged}"
                                            @address-saved="${this._onAddressSaved}"
                                            @address-validation-error="${() => this.showNotification(this.i18n.t('address.fillAll'), 'error')}">
                                    </sequra-address-form>`
                                    : this.addressSaved
                                            ? html`<sequra-address-summary
                                                    .shippingAddress="${this.shippingAddress}"
                                                    .i18n="${this.i18n}"
                                                    @edit-address="${this._onEditAddress}">
                                            </sequra-address-summary>`
                                            : ''}

                            ${this.currentStep === 2
                                    ? html`<sequra-shipping-options
                                            .selectedShipping="${this.selectedShipping}"
                                            .shippingCost="${this.shippingCost}"
                                            .solicitationLoading="${this.solicitationLoading}"
                                            .i18n="${this.i18n}"
                                            @shipping-selected="${this._onShippingSelected}"
                                            @shipping-confirmed="${this._onShippingConfirmed}">
                                    </sequra-shipping-options>`
                                    : this.currentStep > 2
                                            ? html`<sequra-shipping-summary
                                                    .selectedShipping="${this.selectedShipping}"
                                                    .shippingCost="${this.shippingCost}"
                                                    .i18n="${this.i18n}"
                                                    @edit-shipping="${this._onEditShipping}">
                                            </sequra-shipping-summary>`
                                            : ''}

                            ${this.currentStep === 3
                                    ? html`<sequra-payment-methods
                                            .paymentMethods="${this.paymentMethods}"
                                            .paymentMethodsLoading="${this.paymentMethodsLoading}"
                                            .paymentMethodsError="${this.paymentMethodsError}"
                                            .total="${this._total}"
                                            .i18n="${this.i18n}"
                                            @payment-confirmed="${this._onPaymentConfirmed}"
                                            @retry-solicitation="${this._onRetrySolicitation}">
                                    </sequra-payment-methods>`
                                    : this.currentStep > 3
                                            ? html`<sequra-payment-summary
                                                    .selectedPaymentName="${this._selectedPaymentName}"
                                                    .i18n="${this.i18n}"
                                                    @edit-payment="${this._onEditPayment}">
                                            </sequra-payment-summary>`
                                            : ''}
                        </div>

                        <div class="checkout-right">
                            <sequra-order-summary
                                    .items="${this.items}"
                                    .subtotal="${this._subtotal}"
                                    .discountAmount="${this.discountAmount}"
                                    .discountCode="${this.discountCode}"
                                    .shippingCost="${this.shippingCost}"
                                    .total="${this._total}"
                                    .currentStep="${this.currentStep}"
                                    .solicitationLoading="${this.solicitationLoading}"
                                    .i18n="${this.i18n}"
                                    @discount-apply="${this._onDiscountApply}"
                                    @discount-remove="${this._onDiscountRemove}"
                                    @discount-code-changed="${(e) => this.discountCode = e.detail.code}"
                                    @complete-order="${this._completeOrder}">
                            </sequra-order-summary>
                        </div>
                    </div>
                </div>
            </div>

            <div id="sequra-form-container"></div>
            <sequra-notification></sequra-notification>
        `;
    }

    updated() {
        clearTimeout(this._refreshTimer);
        this._refreshTimer = setTimeout(() => {
            if (window.Sequra && window.Sequra.refreshComponents) {
                window.Sequra.refreshComponents();
            }
        }, 200);
    }

    // --- Event Handlers ---

    _onAddressFieldChanged(e) {
        this.shippingAddress = { ...this.shippingAddress, [e.detail.field]: e.detail.value };
        this.requestUpdate();
    }

    _onAddressSaved() {
        this.addressSaved = true;
        this.currentStep = 2;
    }

    _onEditAddress() {
        this.addressSaved = false;
        this.currentStep = 1;
    }

    _onShippingSelected(e) {
        this.selectedShipping = e.detail.method;
        this.shippingCost = e.detail.cost;
        this.requestUpdate();
    }

    async _onShippingConfirmed() {
        this.solicitationLoading = true;
        this.requestUpdate();
        await this._startSolicitation();
        this.solicitationLoading = false;
        this.currentStep = 3;
    }

    _onEditShipping() {
        this.currentStep = 2;
    }

    _onPaymentConfirmed(e) {
        this._selectedPaymentName = e.detail.paymentName;
        this._selectedProductCode = e.detail.productCode;
        this.currentStep = 4;
    }

    _onEditPayment() {
        this.currentStep = 3;
    }

    async _onRetrySolicitation() {
        this.paymentMethodsLoading = true;
        this.paymentMethodsError = '';
        this.requestUpdate();
        await this._startSolicitation();
    }

    async _onDiscountApply() {
        const result = this.discountService.applyDiscount(this.discountCode, this._subtotal);

        if (!result.valid) {
            this.showNotification(this.i18n.t(result.messageKey), result.type);
            return;
        }

        if (result.freeShipping) {
            this.shippingCost = 0;
            this.selectedShipping = 'free';
        }

        const messageParams = { ...result.messageParams };
        if (result.discountAmount > 0) {
            messageParams.amount = this.i18n.formatPrice(result.discountAmount);
        }

        this.discountAmount = result.discountAmount;
        this.showNotification(this.i18n.t(result.messageKey, messageParams), result.type);
        this.requestUpdate();
        await this._refreshOrderIfNeeded();
    }

    async _onDiscountRemove() {
        this.discountAmount = 0;
        this.discountCode = '';
        this.showNotification(this.i18n.t('discount.removed'), 'success');
        this.requestUpdate();
        await this._refreshOrderIfNeeded();
    }

    async _completeOrder() {
        this.solicitationLoading = true;
        await this._showIdentificationForm(this._selectedProductCode || 'i1');
    }

    _resetOrder() {
        window.location.reload();
    }

    async _manualCheckStatus() {
        this._checkingStatus = true;
        this.requestUpdate();
        try {
            const data = await this.sequraService.checkStatus(this._currentOrderId);
            if (data.ipnReceived && data.status !== 'pending') {
                this.orderPending = false;
                this._handleIpnResult(data);
                return;
            }
            this.showNotification(this.i18n.t('checkout.stillPending'), 'warning');
        } catch {
            this.showNotification(this.i18n.t('checkout.networkError'), 'error');
        } finally {
            this._checkingStatus = false;
            this.requestUpdate();
        }
    }

    // --- SeQura Integration ---

    _getSequraScriptConfig(productCodes) {
        return {
            assetKey: this.assetKey,
            products: productCodes,
            decimalSeparator: this.i18n.getDecimalSeparator(),
            thousandSeparator: this.i18n.getThousandSeparator(),
            locale: this.i18n.getLocale(),
            currency: this.i18n.currentCurrency
        };
    }

    _reloadSequraScript() {
        if (!this.sequraService._productCodes || this.sequraService._productCodes.length === 0) return;
        if (!this.assetKey) return;
        this.sequraService.reloadScript(this._getSequraScriptConfig(this.sequraService._productCodes));
    }

    _loadSequraScript(productCodes) {
        if (!this.assetKey || !productCodes || productCodes.length === 0) return;
        this.sequraService.loadScript(this._getSequraScriptConfig(productCodes));
    }

    async _startSolicitation() {
        const payload = this.orderBuilder.buildPayload({
            items: this.items,
            shippingAddress: this.shippingAddress,
            selectedShipping: this.selectedShipping,
            shippingCost: this.shippingCost,
            discountAmount: this.discountAmount,
            discountCode: this.discountCode,
            total: this._total,
            i18n: this.i18n,
            cartId: this._cartId
        });

        try {
            const data = await this.sequraService.startSolicitation(payload);

            this._cartId = data.cartId;
            this._orderRef = data.orderRef;
            this._currentOrderId = data.orderRef;

            if (data.assetKey) this.assetKey = data.assetKey;

            this.paymentMethods = data.paymentMethods;
            this.paymentMethodsLoading = false;
            this.paymentMethodsError = '';

            const productCodes = [...new Set(this.paymentMethods.map(m => m.product))];
            if (productCodes.length > 0) {
                this._loadSequraScript(productCodes);
            }

            return true;
        } catch (error) {
            this.paymentMethodsLoading = false;
            const msg = error.message || '';
            if (msg.includes('No credentials')) {
                this.paymentMethodsError = this.i18n.t('payment.noMethodsForCountry');
            } else if (msg.includes('currency') || msg.includes('Currency')) {
                this.paymentMethodsError = this.i18n.t('payment.noMethodsForCurrency');
            } else {
                this.paymentMethodsError = this.i18n.t('payment.noMethodsForCountry');
            }
            return false;
        }
    }

    async _showIdentificationForm(productCode) {
        if (!this._cartId) {
            this.solicitationLoading = false;
            this.showNotification(this.i18n.t('checkout.noCartId'), 'error');
            return;
        }

        try {
            await this.sequraService.fetchIdentificationForm({
                cartId: this._cartId,
                productCode,
                containerEl: this.querySelector('#sequra-form-container'),
                onApproved: () => this._onSequraApproved(),
                onRejected: () => this._onSequraRejected(),
                onClose: () => this._onSequraFormClose(),
                onFormReady: () => {
                    this.solicitationLoading = false;
                    if (this._currentOrderId) {
                        this._startPolling(this._currentOrderId);
                    }
                }
            });
        } catch (error) {
            this.solicitationLoading = false;
            this.showNotification(error.message, 'error');
        }
    }

    _handleIpnResult(data) {
        if (data.status === 'confirmed') {
            this._completedMethod = this._selectedPaymentName || '';
            this.orderCompleted = true;
            this.showNotification(this.i18n.t('checkout.orderCompleted', { method: this._completedMethod }), 'success', 4000);
        } else if (data.status === 'on_hold') {
            this.showNotification(this.i18n.t('checkout.onHold'), 'warning', 4000);
        } else if (data.status === 'error') {
            this.showNotification(this.i18n.t('checkout.confirmError'), 'error', 4000);
        }
    }

    _onSequraApproved() {
        this._approvedCallbackFired = true;
        this.showNotification(this.i18n.t('checkout.processing'), 'success', 5000);
    }

    _startPolling(orderId) {
        this.sequraService.pollOrderStatus(orderId, {
            onConfirmed: () => this._handleIpnResult({ status: 'confirmed' }),
            onHold: () => this._handleIpnResult({ status: 'on_hold' }),
            onTimeout: () => {
                this._completedMethod = this._selectedPaymentName || '';
                this.orderPending = true;
            },
            isApproved: () => this._approvedCallbackFired
        });
    }

    _onSequraRejected() {
        this.showNotification(this.i18n.t('checkout.rejected'), 'error', 4000);
    }

    _onSequraFormClose() {
        this.sequraService.cancelPolling();
        const container = this.querySelector('#sequra-form-container');
        if (container) {
            const iframe = container.querySelector('iframe');
            if (iframe) iframe.style.display = 'none';
        }
    }

    async _refreshOrderIfNeeded() {
        if (!this._cartId) return;
        if (this.currentStep >= 3) {
            this.paymentMethodsLoading = true;
            this.currentStep = 3;
            this._selectedProductCode = null;
            this._selectedPaymentName = null;
            this.requestUpdate();
        }
        await this._startSolicitation();
        await this.updateComplete;
        this._scheduleWidgetRefresh();
    }

    _scheduleWidgetRefresh() {
        clearTimeout(this._widgetRefreshTimer);
        this._widgetRefreshTimer = setTimeout(() => {
            if (window.Sequra && window.Sequra.refreshComponents) {
                window.Sequra.refreshComponents();
            }
        }, 500);
    }

    // --- Notifications ---

    showNotification(message, type = 'success', duration = 3000) {
        const notificationEl = this.querySelector('sequra-notification');
        if (notificationEl) {
            notificationEl.show(message, type, duration);
        }
    }
}
