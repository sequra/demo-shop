export class DiscountService {
  applyDiscount(code, subtotal) {
    const normalized = code.toLowerCase().trim();

    const sequraMatch = normalized.match(/^sequra-(\d+)$/);
    if (sequraMatch) {
      const percent = parseInt(sequraMatch[1], 10);
      if (percent < 1 || percent > 99) {
        return { valid: false, discountAmount: 0, freeShipping: false, messageKey: 'discount.invalid', messageParams: {}, type: 'error' };
      }
      const amount = subtotal * (percent / 100);
      return { valid: true, discountAmount: amount, freeShipping: false, messageKey: 'discount.off', messageParams: { percent: String(percent) }, type: 'success' };
    }

    switch (normalized) {
      case 'freeship':
        return { valid: true, discountAmount: 0, freeShipping: true, messageKey: 'discount.freeShipping', messageParams: {}, type: 'success' };
      case 'welcome':
        return { valid: true, discountAmount: 15, freeShipping: false, messageKey: 'discount.welcome', messageParams: {}, type: 'success' };
      default:
        return { valid: false, discountAmount: 0, freeShipping: false, messageKey: 'discount.invalid', messageParams: {}, type: 'error' };
    }
  }
}
