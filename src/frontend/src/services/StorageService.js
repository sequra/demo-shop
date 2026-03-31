export class StorageService {
  constructor(storageKey = 'sequra-webshop') {
    this.storageKey = storageKey;
  }

  get(key) {
    try {
      const data = localStorage.getItem(`${this.storageKey}-${key}`);
      return data ? JSON.parse(data) : null;
    } catch (error) {
      console.warn('Failed to get from storage:', error);
      return null;
    }
  }

  set(key, value) {
    try {
      localStorage.setItem(`${this.storageKey}-${key}`, JSON.stringify(value));
      return true;
    } catch (error) {
      console.warn('Failed to save to storage:', error);
      return false;
    }
  }

  remove(key) {
    try {
      localStorage.removeItem(`${this.storageKey}-${key}`);
      return true;
    } catch (error) {
      console.warn('Failed to remove from storage:', error);
      return false;
    }
  }

  clear() {
    try {
      const keys = Object.keys(localStorage).filter(key => 
        key.startsWith(`${this.storageKey}-`)
      );
      keys.forEach(key => localStorage.removeItem(key));
      return true;
    } catch (error) {
      console.warn('Failed to clear storage:', error);
      return false;
    }
  }
}