import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { ApolloProvider } from '@apollo/client';
import client from './graphql/client';
import { CartProvider } from './context/CartContext';
import Header from './components/Header/Header';
import CategoryPage from './pages/CategoryPage/CategoryPage';
import ProductDetailsPage from './pages/ProductDetailsPage/ProductDetailsPage';
import './styles/index.css';

// Import your page components here (these will be created later)
// import ProductPage from './pages/ProductPage/ProductPage';
// import CartPage from './pages/CartPage/CartPage';

function App() {
  return (
    <ApolloProvider client={client}>
      <CartProvider>
        <BrowserRouter>
          <div className="flex flex-col min-h-full">
            <Header />
            <main className="flex-1 px-6 py-8 max-w-container mx-auto w-full">
              <Routes>
                <Route path="/" element={<Navigate to="/all" replace />} />
                <Route path="/:category" element={<CategoryPage />} />
                <Route path="/product/:id" element={<ProductDetailsPage />} />
                <Route path="/cart" element={<div>Cart Page Coming Soon</div>} />
              </Routes>
            </main>
      </div>
        </BrowserRouter>
      </CartProvider>
    </ApolloProvider>
  );
}

export default App;
