import { StorageService } from './StorageService.js';
import { translations } from '../i18n/translations.js';

export class I18nService {
  static CURRENCIES = {
    EUR: { symbol: '€', position: 'before', decimals: 2, decimalSep: ',', thousandSep: '.' },
    GBP: { symbol: '£', position: 'before', decimals: 2, decimalSep: '.', thousandSep: ',' },
    USD: { symbol: '$', position: 'before', decimals: 2, decimalSep: '.', thousandSep: ',' },
    CHF: { symbol: 'CHF', position: 'after', decimals: 2, decimalSep: '.', thousandSep: "'" }
  };

  static LANGUAGES = { en: 'English', es: 'Español', fr: 'Français', de: 'Deutsch' };

  static LOCALE_MAP = {
    en: 'en-GB',
    es: 'es-ES',
    fr: 'fr-FR',
    de: 'de-DE'
  };

  constructor() {
    this.storageService = new StorageService('sequra-webshop');
    this.listeners = [];

    const savedLang = this.storageService.get('language');
    const savedCurrency = this.storageService.get('currency');

    this.currentLanguage = savedLang || 'en';
    this.currentCurrency = savedCurrency || 'EUR';
  }

  setLanguage(lang) {
    if (I18nService.LANGUAGES[lang]) {
      this.currentLanguage = lang;
      this.storageService.set('language', lang);
      this._notifyListeners();
    }
  }

  setCurrency(currency) {
    if (I18nService.CURRENCIES[currency]) {
      this.currentCurrency = currency;
      this.storageService.set('currency', currency);
      this._notifyListeners();
    }
  }

  t(key, params = {}) {
    const langStrings = translations[this.currentLanguage] || translations.en;
    let str = langStrings[key] || translations.en[key] || key;

    for (const [param, value] of Object.entries(params)) {
      str = str.replace(`{${param}}`, value);
    }

    return str;
  }

  formatPrice(amount) {
    const config = I18nService.CURRENCIES[this.currentCurrency];
    if (!config) return `${amount}`;

    const fixed = Math.abs(amount).toFixed(config.decimals);
    const [intPart, decPart] = fixed.split('.');

    // Add thousand separators
    const formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, config.thousandSep);
    const formattedNumber = decPart ? `${formattedInt}${config.decimalSep}${decPart}` : formattedInt;

    const sign = amount < 0 ? '-' : '';

    if (config.position === 'before') {
      return `${sign}${config.symbol}${formattedNumber}`;
    }
    return `${sign}${formattedNumber} ${config.symbol}`;
  }

  getLocale() {
    return I18nService.LOCALE_MAP[this.currentLanguage] || 'en-GB';
  }

  getDecimalSeparator() {
    const config = I18nService.CURRENCIES[this.currentCurrency];
    return config ? config.decimalSep : '.';
  }

  getThousandSeparator() {
    const config = I18nService.CURRENCIES[this.currentCurrency];
    return config ? config.thousandSep : ',';
  }

  addListener(fn) {
    this.listeners.push(fn);
  }

  removeListener(fn) {
    this.listeners = this.listeners.filter(l => l !== fn);
  }

  _notifyListeners() {
    for (const fn of this.listeners) {
      fn();
    }
  }
}
