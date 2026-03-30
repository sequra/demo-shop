export class DiscountService {
  applyDiscount(code, subtotal) {
    const normalized = code.toLowerCase();

    switch (normalized) {
      case 'sequrademodiscount':
      case 'save10': {
        const amount = subtotal * 0.1;
        return { valid: true, discountAmount: amount, freeShipping: false, messageKey: 'discount.off', messageParams: { percent: '10' }, type: 'success' };
      }
      case 'save20': {
        const amount = subtotal * 0.2;
        return { valid: true, discountAmount: amount, freeShipping: false, messageKey: 'discount.off', messageParams: { percent: '20' }, type: 'success' };
      }
      case 'freeship':
        return { valid: true, discountAmount: 0, freeShipping: true, messageKey: 'discount.freeShipping', messageParams: {}, type: 'success' };
      case 'welcome':
        return { valid: true, discountAmount: 15, freeShipping: false, messageKey: 'discount.welcome', messageParams: {}, type: 'success' };
      default:
        return { valid: false, discountAmount: 0, freeShipping: false, messageKey: 'discount.invalid', messageParams: {}, type: 'error' };
    }
  }
}
