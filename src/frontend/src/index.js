// Import base styles as string and inject into document
import baseStyles from './styles/base.scss?inline';
const style = document.createElement('style');
style.textContent = baseStyles;
document.head.appendChild(style);

// Import and register all components
import { SeQuraCheckout } from './components/SeQuraCheckout.js';

// Import models and services for external use
import { Product } from './models/Product.js';
import { CartItem } from './models/CartItem.js';
import { Order } from './models/Order.js';
import { ProductService } from './services/ProductService.js';

// Define custom elements
customElements.define('sequra-checkout', SeQuraCheckout);

// Export everything for external use
export {
  SeQuraCheckout,
  Product,
  CartItem,
  Order,
  ProductService
};

// Global API for easy embedding
window.SeQura = {
  Components: {
    Checkout: SeQuraCheckout
  },
  Models: {
    Product,
    CartItem,
    Order
  },
  Services: {
    ProductService
  }
};
