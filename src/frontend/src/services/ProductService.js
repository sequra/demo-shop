import { Product } from '../models/Product.js';
import { products } from '../data/products.js';

export class ProductService {
  constructor(i18n) {
    this.i18n = i18n;
  }

  getAllProducts() {
    return products.map(data => new Product({
      id: data.id,
      name: this.i18n.t(data.nameKey),
      price: data.price,
      image: data.image,
      description: this.i18n.t(data.descKey),

      quantity: data.quantity,
      formattedPrice: this.i18n.formatPrice(data.price)
    }));
  }
}
