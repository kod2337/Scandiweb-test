import { useCart } from '../../context/CartContext';
import { useMutation } from '@apollo/client';
import { PLACE_ORDER } from '../../graphql/mutations';
import { AttributeItem } from '../../types';
import { Link } from 'react-router-dom';

// Convert cart items to a shorter name that better fits the screen
const formatItemsCount = (count: number) => {
  return count === 1 ? '1 Item' : `${count} Items`;
};

const CartOverlay = () => {
  const { 
    cartItems, 
    increaseQuantity, 
    decreaseQuantity,
    getTotalItems,
    getTotalPrice,
    getCurrency,
    clearCart
  } = useCart();

  const [placeOrder, { loading }] = useMutation(PLACE_ORDER);

  const totalItems = getTotalItems();
  const totalPrice = getTotalPrice();
  const currency = getCurrency();

  const handlePlaceOrder = async () => {
    if (cartItems.length === 0) return;

    try {
      const orderInput = {
        items: cartItems.map(item => ({
          productId: item.productId,
          quantity: item.quantity,
          attributes: Object.entries(item.selectedAttributes).map(([name, value]) => ({
            name,
            value
          }))
        })),
        currency: currency.label
      };

      const { data } = await placeOrder({ variables: { order: orderInput } });
      
      if (data?.placeOrder?.success) {
        clearCart();
      }
    } catch (error) {
      console.error('Failed to place order:', error);
    }
  };

  return (
    <div className="absolute top-full right-0 mt-2 bg-white shadow-lg rounded w-[325px] md:w-[400px] z-30">
      <div className="p-4">
        <h3 className="font-medium mb-4">My Bag, {formatItemsCount(totalItems)}</h3>
        
        {cartItems.length === 0 ? (
          <p className="text-center py-8 text-light-text">Your cart is empty</p>
        ) : (
          <>
            <div className="max-h-[400px] overflow-y-auto divide-y">
              {cartItems.map(item => (
                <div key={item.id} className="py-4">
                  <div className="flex justify-between">
                    <div className="flex-1">
                      <p className="font-medium">{item.brand}</p>
                      <p>{item.name}</p>
                      <p className="font-medium mt-2">
                        {currency.symbol}{item.price.amount.toFixed(2)}
                      </p>
                      
                      {/* Product Attributes */}
                      {Object.entries(item.selectedAttributes).map(([attrName, attrValue]) => (
                        <div 
                          key={attrName}
                          className="mt-3"
                          data-testid={`cart-item-attribute-${attrName.toLowerCase().replace(/\s+/g, '-')}`}
                        >
                          <p className="text-xs uppercase mb-1">{attrName}:</p>
                          <div className="flex flex-wrap gap-1">
                            {item.attributes
                              .find(attr => attr.name === attrName)
                              ?.items
                              ?.map((option: AttributeItem) => {
                                // Handle color swatches differently
                                if (attrName.toLowerCase() === 'color') {
                                  return (
                                    <div
                                      key={option.id}
                                      data-testid={`cart-item-attribute-${attrName.toLowerCase().replace(/\s+/g, '-')}-${option.id.toLowerCase().replace(/\s+/g, '-')}${option.value === attrValue ? '-selected' : ''}`}
                                      className={`
                                        w-5 h-5 border
                                        ${option.value === attrValue 
                                          ? 'ring-2 ring-primary' 
                                          : ''}
                                      `}
                                      style={{ backgroundColor: option.value }}
                                    ></div>
                                  );
                                }
                                
                                // Text attributes
                                return (
                                  <div
                                    key={option.id}
                                    data-testid={`cart-item-attribute-${attrName.toLowerCase().replace(/\s+/g, '-')}-${option.id.toLowerCase().replace(/\s+/g, '-')}${option.value === attrValue ? '-selected' : ''}`}
                                    className={`
                                      text-xs py-1 px-2 border
                                      ${option.value === attrValue 
                                        ? 'bg-text text-white' 
                                        : 'bg-white text-text'}
                                    `}
                                  >
                                    {option.displayValue}
                                  </div>
                                );
                              })
                            }
                          </div>
                        </div>
                      ))}
                    </div>
                    
                    <div className="flex items-start ml-4">
                      {/* Quantity controls */}
                      <div className="flex flex-col items-center justify-between h-[120px] mr-4">
                        <button 
                          className="w-6 h-6 border border-text flex items-center justify-center"
                          onClick={() => increaseQuantity(item.productId, item.selectedAttributes)}
                          data-testid="cart-item-amount-increase"
                        >
                          +
                        </button>
                        <span 
                          className="my-1 font-medium"
                          data-testid="cart-item-amount"
                        >
                          {item.quantity}
                        </span>
                        <button 
                          className="w-6 h-6 border border-text flex items-center justify-center"
                          onClick={() => decreaseQuantity(item.productId, item.selectedAttributes)}
                          data-testid="cart-item-amount-decrease"
                        >
                          -
                        </button>
                      </div>
                      
                      {/* Product image */}
                      <div className="w-[80px] h-[120px] bg-gray-100">
                        {item.gallery.length > 0 && (
                          <img
                            src={item.gallery[0]}
                            alt={item.name}
                            className="w-full h-full object-cover"
                          />
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
            
            {/* Totals */}
            <div className="flex justify-between items-center font-bold mt-6">
              <span>Total</span>
              <span data-testid="cart-total">
                {currency.symbol}{totalPrice.toFixed(2)}
              </span>
            </div>
            
            {/* Actions */}
            <div className="mt-6 flex space-x-3">
              <Link 
                to="/cart"
                className="flex-1 button-secondary"
              >
                View Bag
              </Link>
              <button 
                className={`flex-1 button-primary ${cartItems.length === 0 ? 'opacity-50 cursor-not-allowed' : ''}`}
                disabled={cartItems.length === 0 || loading}
                onClick={handlePlaceOrder}
              >
                {loading ? 'Processing...' : 'Checkout'}
              </button>
            </div>
          </>
        )}
      </div>
    </div>
  );
};

export default CartOverlay;
