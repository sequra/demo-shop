export class OrderBuilderService {
  static COUNTRY_MAP = { Spain: 'ES', France: 'FR', Germany: 'DE', Italy: 'IT', Portugal: 'PT' };
  static LANGUAGE_MAP = { en: 'en-GB', es: 'es-ES', fr: 'fr-FR', de: 'de-DE' };

  getCountryCode(country) {
    return OrderBuilderService.COUNTRY_MAP[country] || 'ES';
  }

  getLanguageCode(langKey) {
    return OrderBuilderService.LANGUAGE_MAP[langKey] || 'es-ES';
  }

  buildCartItems({ items, selectedShipping, shippingCost, discountAmount, discountCode }) {
    const cartItems = items.map(item => ({
      type: 'product',
      reference: String(item.product.id),
      name: item.product.name,
      price_with_tax: Math.round(item.product.price * 100),
      quantity: item.quantity,
      total_with_tax: Math.round(item.product.price * item.quantity * 100),
      downloadable: false
    }));

    if (shippingCost > 0) {
      cartItems.push({
        type: 'handling',
        reference: selectedShipping,
        name: selectedShipping,
        total_with_tax: Math.round(shippingCost * 100)
      });
    }

    if (discountAmount > 0) {
      cartItems.push({
        type: 'discount',
        reference: discountCode || 'discount',
        name: `Discount: ${discountCode}`,
        total_with_tax: -Math.round(discountAmount * 100)
      });
    }

    return cartItems;
  }

  buildPayload({ items, shippingAddress, selectedShipping, shippingCost, discountAmount, discountCode, total, i18n, cartId }) {
    const addr = shippingAddress;
    const countryCode = this.getCountryCode(addr.country);

    return {
      order: {
        cart: {
          ...(cartId ? { cart_ref: cartId } : {}),
          currency: i18n.currentCurrency,
          gift: false,
          order_total_with_tax: Math.round(total * 100),
          items: this.buildCartItems({ items, selectedShipping, shippingCost, discountAmount, discountCode })
        },
        delivery_method: {
          name: selectedShipping,
          home_delivery: true
        },
        delivery_address: {
          given_names: addr.firstName,
          surnames: addr.lastName,
          company: '',
          address_line_1: addr.street,
          address_line_2: '',
          postal_code: addr.postalCode,
          city: addr.city,
          country_code: countryCode
        },
        invoice_address: {
          given_names: addr.firstName,
          surnames: addr.lastName,
          company: '',
          address_line_1: addr.street,
          address_line_2: '',
          postal_code: addr.postalCode,
          city: addr.city,
          country_code: countryCode
        },
        customer: {
          given_names: addr.firstName,
          surnames: addr.lastName,
          email: addr.email,
          logged_in: false,
          language_code: this.getLanguageCode(i18n.currentLanguage),
          ip_number: '127.0.0.1',
          user_agent: navigator.userAgent
        },
        gui: {
          layout: window.innerWidth <= 768 ? 'smartphone' : 'desktop'
        },
        platform: {
          name: 'SeQura Demo WebShop',
          version: '1.0.0',
          uname: navigator.platform || 'Web Browser',
          db_name: 'none',
          db_version: '0'
        }
      }
    };
  }
}
