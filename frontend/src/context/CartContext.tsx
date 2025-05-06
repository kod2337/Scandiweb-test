import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { AttributeSet, Price } from '../types';

// Define types for cart item
export interface CartItem {
  id: string;
  productId: string;
  name: string;
  brand: string;
  gallery: string[];
  price: Price;
  quantity: number;
  attributes: AttributeSet[]; // Use the shared AttributeSet type
  selectedAttributes: Record<string, string>;
}

interface CartContextType {
  cartItems: CartItem[];
  isCartOpen: boolean;
  openCart: () => void;
  closeCart: () => void;
  toggleCart: () => void;
  addToCart: (item: CartItem) => void;
  removeFromCart: (itemId: string, attributeSelection: Record<string, string>) => void;
  increaseQuantity: (itemId: string, attributeSelection: Record<string, string>) => void;
  decreaseQuantity: (itemId: string, attributeSelection: Record<string, string>) => void;
  clearCart: () => void;
  getTotalItems: () => number;
  getTotalPrice: () => number;
  getCurrency: () => { label: string; symbol: string };
}

// Create the context with default values
const CartContext = createContext<CartContextType>({
  cartItems: [],
  isCartOpen: false,
  openCart: () => {},
  closeCart: () => {},
  toggleCart: () => {},
  addToCart: () => {},
  removeFromCart: () => {},
  increaseQuantity: () => {},
  decreaseQuantity: () => {},
  clearCart: () => {},
  getTotalItems: () => 0,
  getTotalPrice: () => 0,
  getCurrency: () => ({ label: 'USD', symbol: '$' }),
});

// Generate a unique cart item ID based on product ID and selected attributes
const generateCartItemId = (productId: string, selectedAttributes: Record<string, string>): string => {
  const attributeStr = Object.entries(selectedAttributes)
    .sort(([keyA], [keyB]) => keyA.localeCompare(keyB))
    .map(([key, value]) => `${key}:${value}`)
    .join('-');
  
  return `${productId}-${attributeStr}`;
};

interface CartProviderProps {
  children: ReactNode;
}

// Create the provider component
export const CartProvider: React.FC<CartProviderProps> = ({ children }) => {
  const [cartItems, setCartItems] = useState<CartItem[]>([]);
  const [isCartOpen, setIsCartOpen] = useState(false);

  // Load cart items from localStorage on initial render
  useEffect(() => {
    const storedCartItems = localStorage.getItem('cartItems');
    if (storedCartItems) {
      try {
        setCartItems(JSON.parse(storedCartItems));
      } catch (error) {
        console.error('Failed to parse cart items from localStorage', error);
      }
    }
  }, []);

  // Save cart items to localStorage whenever they change
  useEffect(() => {
    localStorage.setItem('cartItems', JSON.stringify(cartItems));
  }, [cartItems]);

  const openCart = () => setIsCartOpen(true);
  const closeCart = () => setIsCartOpen(false);
  const toggleCart = () => setIsCartOpen(prev => !prev);

  const findCartItemIndex = (productId: string, selectedAttributes: Record<string, string>): number => {
    return cartItems.findIndex(item => 
      item.productId === productId && 
      Object.entries(selectedAttributes).every(([key, value]) => 
        item.selectedAttributes[key] === value
      ) &&
      Object.keys(selectedAttributes).length === Object.keys(item.selectedAttributes).length
    );
  };

  const addToCart = (item: CartItem) => {
    const itemId = generateCartItemId(item.productId, item.selectedAttributes);
    const existingItemIndex = findCartItemIndex(item.productId, item.selectedAttributes);

    setCartItems(prevItems => {
      if (existingItemIndex !== -1) {
        // Item already exists, update quantity
        const updatedItems = [...prevItems];
        updatedItems[existingItemIndex] = {
          ...updatedItems[existingItemIndex],
          quantity: updatedItems[existingItemIndex].quantity + item.quantity
        };
        return updatedItems;
      } else {
        // Add new item with the generated ID
        return [...prevItems, { ...item, id: itemId }];
      }
    });

    // Open the cart when adding an item
    openCart();
  };

  const removeFromCart = (productId: string, selectedAttributes: Record<string, string>) => {
    setCartItems(prevItems => 
      prevItems.filter(item => 
        !(item.productId === productId && 
          Object.entries(selectedAttributes).every(([key, value]) => 
            item.selectedAttributes[key] === value
          ) &&
          Object.keys(selectedAttributes).length === Object.keys(item.selectedAttributes).length
        )
      )
    );
  };

  const increaseQuantity = (productId: string, selectedAttributes: Record<string, string>) => {
    const existingItemIndex = findCartItemIndex(productId, selectedAttributes);
    
    if (existingItemIndex !== -1) {
      setCartItems(prevItems => {
        const updatedItems = [...prevItems];
        updatedItems[existingItemIndex] = {
          ...updatedItems[existingItemIndex],
          quantity: updatedItems[existingItemIndex].quantity + 1
        };
        return updatedItems;
      });
    }
  };

  const decreaseQuantity = (productId: string, selectedAttributes: Record<string, string>) => {
    const existingItemIndex = findCartItemIndex(productId, selectedAttributes);
    
    if (existingItemIndex !== -1) {
      const currentQuantity = cartItems[existingItemIndex].quantity;
      
      if (currentQuantity === 1) {
        // If quantity is 1, remove the item
        removeFromCart(productId, selectedAttributes);
      } else {
        // Otherwise, decrease the quantity
        setCartItems(prevItems => {
          const updatedItems = [...prevItems];
          updatedItems[existingItemIndex] = {
            ...updatedItems[existingItemIndex],
            quantity: updatedItems[existingItemIndex].quantity - 1
          };
          return updatedItems;
        });
      }
    }
  };

  const clearCart = () => {
    setCartItems([]);
  };

  const getTotalItems = (): number => {
    return cartItems.reduce((total, item) => total + item.quantity, 0);
  };

  const getTotalPrice = (): number => {
    return cartItems.reduce(
      (total, item) => total + item.price.amount * item.quantity, 
      0
    );
  };

  const getCurrency = () => {
    // Return the currency of the first item, or default if cart is empty
    return cartItems.length > 0 
      ? cartItems[0].price.currency 
      : { label: 'USD', symbol: '$' };
  };

  return (
    <CartContext.Provider
      value={{
        cartItems,
        isCartOpen,
        openCart,
        closeCart,
        toggleCart,
        addToCart,
        removeFromCart,
        increaseQuantity,
        decreaseQuantity,
        clearCart,
        getTotalItems,
        getTotalPrice,
        getCurrency
      }}
    >
      {children}
    </CartContext.Provider>
  );
};

// Custom hook to use the cart context
export const useCart = () => useContext(CartContext); 