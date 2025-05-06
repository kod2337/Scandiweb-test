import { Link, useLocation } from 'react-router-dom';
import { useQuery } from '@apollo/client';
import { GET_CATEGORIES } from '../../graphql/queries';
import { useCart } from '../../context/CartContext';
import CartOverlay from '../Cart/CartOverlay';

interface Category {
  name: string;
}

interface CategoriesData {
  categories: Category[];
}

const Header = () => {
  const location = useLocation();
  const { data, loading } = useQuery<CategoriesData>(GET_CATEGORIES);
  const { isCartOpen, toggleCart, getTotalItems } = useCart();

  const itemCount = getTotalItems();
  const currentCategory = location.pathname.split('/')[1] || 'all';

  return (
    <header className="sticky top-0 z-20 bg-white shadow-sm">
      <div className="max-w-container mx-auto px-6 py-4 flex justify-between items-center">
        {/* Category Navigation */}
        <nav className="flex">
          {loading ? (
            <div className="animate-pulse h-6 w-32 bg-gray-200 rounded"></div>
          ) : (
            <ul className="flex space-x-6">
              {data?.categories.map((category) => (
                <li key={category.name}>
                  <Link
                    to={`/${category.name}`}
                    className={`uppercase text-sm tracking-wide py-4 px-1 ${
                      currentCategory === category.name
                        ? 'border-b-2 border-primary text-primary font-medium'
                        : 'text-text hover:text-primary'
                    }`}
                    data-testid={
                      currentCategory === category.name
                        ? 'active-category-link'
                        : 'category-link'
                    }
                  >
                    {category.name}
                  </Link>
                </li>
              ))}
            </ul>
          )}
        </nav>

        {/* Logo (Center) */}
        <div className="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2">
          <svg
            className="w-8 h-8 text-primary"
            fill="currentColor"
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
          </svg>
        </div>

        {/* Cart Button */}
        <div className="relative">
          <button 
            className="relative p-2 rounded-full"
            onClick={toggleCart}
            data-testid="cart-btn"
          >
            <svg
              className="w-6 h-6 text-text"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
              />
            </svg>
            
            {/* Item Count Bubble */}
            {itemCount > 0 && (
              <span className="absolute -top-1 -right-1 bg-primary text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">
                {itemCount}
              </span>
            )}
          </button>

          {/* Cart Overlay */}
          {isCartOpen && <CartOverlay />}
        </div>
      </div>

      {/* Overlay when cart is open */}
      {isCartOpen && <div className="global-overlay" onClick={toggleCart} />}
    </header>
  );
};

export default Header; 