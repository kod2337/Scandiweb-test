// Common attribute item interface
export interface AttributeItem {
  id: string;
  displayValue: string;
  value: string;
}

// Common attribute set interface for product API data
export interface AttributeSet {
  id: string;
  name: string;
  type: string;
  items: AttributeItem[];
}

// Currency interface
export interface Currency {
  label: string;
  symbol: string;
}

// Price interface
export interface Price {
  amount: number;
  currency: Currency;
}

// Basic product interface from API
export interface Product {
  id: string;
  name: string;
  brand: string;
  inStock: boolean;
  gallery: string[];
  prices: Price[];
  attributes: AttributeSet[];
  description?: string;
  category?: string;
} 