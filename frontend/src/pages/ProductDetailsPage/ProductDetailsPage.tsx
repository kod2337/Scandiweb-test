import { useState, useEffect, useRef } from 'react';
import { useParams } from 'react-router-dom';
import { useQuery } from '@apollo/client';
import { GET_PRODUCT_DETAILS } from '../../graphql/queries';
import { useCart } from '../../context/CartContext';
import { Product } from '../../types';
import { parseHTML } from '../../utils/htmlParser';

interface ProductDetailsData {
  product: Product;
}

const ProductDetailsPage = () => {
  const { id } = useParams<{ id: string }>();
  const { addToCart } = useCart();
  const [selectedAttributes, setSelectedAttributes] = useState<Record<string, string>>({});
  const [selectedImageIndex, setSelectedImageIndex] = useState(0);
  
  const { loading, error, data } = useQuery<ProductDetailsData>(GET_PRODUCT_DETAILS, {
    variables: { id },
    skip: !id
  });

  const descriptionRef = useRef<HTMLDivElement>(null);

  // Initialize attributes when data is loaded
  useEffect(() => {
    if (data?.product && data.product.attributes.length > 0 && Object.keys(selectedAttributes).length === 0) {
      const initialAttributes: Record<string, string> = {};
      data.product.attributes.forEach(attr => {
        if (attr.items && attr.items.length > 0) {
          initialAttributes[attr.name] = attr.items[0].value;
        }
      });
      setSelectedAttributes(initialAttributes);
    }
  }, [data, selectedAttributes]);

  // Handle HTML parsing
  useEffect(() => {
    if (data?.product && descriptionRef.current) {
      const elements = parseHTML(data.product.description || '');
      descriptionRef.current.innerHTML = ''; // Clear existing content
      elements.forEach(element => {
        descriptionRef.current?.appendChild(element);
      });
    }
  }, [data?.product?.description]);

  const handleAttributeSelect = (attributeName: string, value: string) => {
    setSelectedAttributes(prev => ({
      ...prev,
      [attributeName]: value
    }));
  };

  const allAttributesSelected = () => {
    if (!data?.product) return false;
    
    const requiredAttributes = data.product.attributes.length;
    const selectedCount = Object.keys(selectedAttributes).length;
    
    return requiredAttributes === selectedCount;
  };

  const handleAddToCart = () => {
    if (!data?.product || !allAttributesSelected()) return;

    const product = data.product;
    
    addToCart({
      id: '',
      productId: product.id,
      name: product.name,
      brand: product.brand,
      gallery: product.gallery,
      price: product.prices[0], // Using first price by default
      quantity: 1,
      attributes: product.attributes,
      selectedAttributes
    });
  };

  if (loading) {
    return (
      <div className="py-8">
        <div className="animate-pulse">
          <div className="h-6 bg-gray-200 rounded w-1/4 mb-6"></div>
          <div className="flex flex-col md:flex-row gap-8">
            <div className="w-full md:w-1/2">
              <div className="flex">
                <div className="w-24 space-y-4">
                  {[...Array(4)].map((_, index) => (
                    <div key={index} className="bg-gray-200 aspect-square"></div>
                  ))}
                </div>
                <div className="flex-1 ml-4 bg-gray-200 aspect-square"></div>
              </div>
            </div>
            <div className="w-full md:w-1/2 space-y-4">
              <div className="h-6 bg-gray-200 rounded w-3/4"></div>
              <div className="h-4 bg-gray-200 rounded w-1/2"></div>
              <div className="space-y-2">
                <div className="h-4 bg-gray-200 rounded w-24"></div>
                <div className="flex space-x-2">
                  {[...Array(3)].map((_, index) => (
                    <div key={index} className="w-10 h-10 bg-gray-300 rounded"></div>
                  ))}
                </div>
              </div>
              <div className="h-10 bg-gray-200 rounded w-1/3"></div>
              <div className="h-40 bg-gray-200 rounded w-full"></div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="py-8 text-center">
        <p className="text-error">Failed to load product details: {error.message}</p>
      </div>
    );
  }

  if (!data || !data.product) {
    return (
      <div className="py-8 text-center">
        <p className="text-light-text">Product not found</p>
      </div>
    );
  }

  const { product } = data;
  const selectedImage = product.gallery[selectedImageIndex];

  return (
    <div className="py-8">
      <div className="flex flex-col md:flex-row gap-10">
        {/* Product Gallery */}
        <div className="w-full md:w-1/2" data-testid="product-gallery">
          <div className="flex">
            {/* Thumbnail Gallery */}
            <div className="w-20 md:w-24 space-y-4 mr-4">
              {product.gallery.map((image, index) => (
                <button
                  key={index}
                  className={`w-full aspect-square bg-white ${selectedImageIndex === index ? 'border-2 border-primary' : 'border'}`}
                  onClick={() => setSelectedImageIndex(index)}
                >
                  <img 
                    src={image} 
                    alt={`${product.name} ${index + 1}`} 
                    className="w-full h-full object-cover"
                  />
                </button>
              ))}
            </div>
            
            {/* Main Image */}
            <div className="flex-1 relative">
              <img 
                src={selectedImage} 
                alt={product.name} 
                className="w-full aspect-square object-cover"
              />
              
              {/* Navigation Arrows */}
              {product.gallery.length > 1 && (
                <>
                  <button 
                    className="absolute left-2 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-70 p-2 rounded-full"
                    onClick={() => setSelectedImageIndex(prev => (prev === 0 ? product.gallery.length - 1 : prev - 1))}
                  >
                    <svg 
                      className="w-5 h-5" 
                      fill="none" 
                      stroke="currentColor" 
                      viewBox="0 0 24 24"
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                    </svg>
                  </button>
                  <button 
                    className="absolute right-2 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-70 p-2 rounded-full"
                    onClick={() => setSelectedImageIndex(prev => (prev === product.gallery.length - 1 ? 0 : prev + 1))}
                  >
                    <svg 
                      className="w-5 h-5" 
                      fill="none" 
                      stroke="currentColor" 
                      viewBox="0 0 24 24"
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                    </svg>
                  </button>
                </>
              )}
            </div>
          </div>
        </div>
        
        {/* Product Details */}
        <div className="w-full md:w-1/2">
          <h1 className="text-3xl font-normal">{product.brand}</h1>
          <h2 className="text-2xl font-normal mb-6">{product.name}</h2>
          
          {/* Product Attributes */}
          {product.attributes.map(attribute => (
            <div 
              key={attribute.id} 
              className="mb-6"
              data-testid={`product-attribute-${attribute.name.toLowerCase().replace(/\s+/g, '-')}`}
            >
              <h3 className="text-sm uppercase font-medium mb-2">{attribute.name}:</h3>
              <div className="flex flex-wrap gap-2">
                {attribute.items.map(item => {
                  const isSelected = selectedAttributes[attribute.name] === item.value;
                  
                  // Color swatch
                  if (attribute.type === 'swatch') {
                    return (
                      <button
                        key={item.id}
                        className={`w-8 h-8 rounded ${isSelected ? 'ring-2 ring-primary' : ''}`}
                        style={{ backgroundColor: item.value }}
                        onClick={() => handleAttributeSelect(attribute.name, item.value)}
                      />
                    );
                  }
                  
                  // Size or other text attributes
                  return (
                    <button
                      key={item.id}
                      className={`px-3 py-2 border ${
                        isSelected 
                          ? 'bg-text text-white' 
                          : 'bg-white text-text hover:bg-gray-50'
                      }`}
                      onClick={() => handleAttributeSelect(attribute.name, item.value)}
                    >
                      {item.displayValue}
                    </button>
                  );
                })}
              </div>
            </div>
          ))}
          
          {/* Price */}
          <div className="mb-6">
            <h3 className="text-sm uppercase font-medium mb-2">Price:</h3>
            <p className="text-lg font-medium">
              {product.prices[0].currency.symbol}
              {product.prices[0].amount.toFixed(2)}
            </p>
          </div>
          
          {/* Add to Cart Button */}
          <button
            className={`w-full py-3 px-6 mb-8 text-white font-medium ${
              !allAttributesSelected() || !product.inStock
                ? 'bg-gray-400 cursor-not-allowed'
                : 'bg-primary hover:bg-primary-dark'
            }`}
            disabled={!allAttributesSelected() || !product.inStock}
            onClick={handleAddToCart}
            data-testid="add-to-cart"
          >
            {!product.inStock ? 'Out of Stock' : 'Add to Cart'}
          </button>
          
          {/* Product Description */}
          <div 
            className="prose max-w-none"
            data-testid="product-description"
            ref={descriptionRef}
          />
        </div>
      </div>
    </div>
  );
};

export default ProductDetailsPage; 