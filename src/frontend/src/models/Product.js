export class Product {
  constructor({ id, name, price, image, description = '', quantity = 0, formattedPrice = '' }) {
    this.id = id;
    this.name = name;
    this.price = price;
    this.image = image;
    this.description = description;
    this.quantity = quantity;
    this.formattedPrice = formattedPrice;
  }

  toJSON() {
    return {
      id: this.id,
      name: this.name,
      price: this.price,
      image: this.image,
      description: this.description
    };
  }

  static fromJSON(json) {
    return new Product(json);
  }
}