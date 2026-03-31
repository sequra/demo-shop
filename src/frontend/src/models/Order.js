export class Order {
  constructor() {
    this.items = [];
    this.shippingCost = 0;
    this.discount = 0;
    this.discountCode = null;
    this.shippingAddress = null;
    this.paymentMethod = null;
  }

  get subtotal() {
    return this.items.reduce((sum, item) => sum + item.total, 0);
  }

  get total() {
    return this.subtotal + this.shippingCost - this.discount;
  }

  setShippingAddress(address) {
    this.shippingAddress = address;
  }

  setPaymentMethod(method) {
    this.paymentMethod = method;
  }

  toJSON() {
    return {
      items: this.items.map(item => item.toJSON()),
      shippingCost: this.shippingCost,
      discount: this.discount,
      discountCode: this.discountCode,
      shippingAddress: this.shippingAddress,
      paymentMethod: this.paymentMethod
    };
  }
}