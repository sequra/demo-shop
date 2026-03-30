import { Product } from './Product.js';

export class CartItem {
  constructor(product, quantity = 1) {
    this.product = product;
    this.quantity = quantity;
  }

  get total() {
    return this.product.price * this.quantity;
  }

  toJSON() {
    return {
      product: this.product.toJSON ? this.product.toJSON() : this.product,
      quantity: this.quantity
    };
  }

  static fromJSON(json) {
    // Create Product instance from JSON data
    const product = json.product instanceof Product ? json.product : Product.fromJSON(json.product);
    return new CartItem(product, json.quantity);
  }
}