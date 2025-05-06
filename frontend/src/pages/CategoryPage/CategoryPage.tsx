import { useParams } from 'react-router-dom';
import { useQuery } from '@apollo/client';
import { GET_PRODUCTS_BY_CATEGORY } from '../../graphql/queries';
import ProductCard from '../../components/ProductCard/ProductCard';
import { Product } from '../../types';

interface ProductsData {
  products: Product[];
}

const CategoryPage = () => {
  // Default to 'all' category if none is specified
  const { category = 'all' } = useParams<{ category: string }>();
  
  // Query products for the selected category
  const { loading, error, data } = useQuery<ProductsData>(GET_PRODUCTS_BY_CATEGORY, {
    variables: { category },
  });

  if (loading) {
    return (
      <div className="py-8">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
          {[...Array(6)].map((_, index) => (
            <div key={index} className="animate-pulse">
              <div className="bg-gray-200 aspect-square mb-4"></div>
              <div className="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
              <div className="h-4 bg-gray-200 rounded w-1/2"></div>
            </div>
          ))}
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="py-8 text-center">
        <p className="text-error">Failed to load products: {error.message}</p>
      </div>
    );
  }

  return (
    <div className="py-8">
      <h1 className="text-3xl font-normal mb-8 capitalize">{category}</h1>
      
      {data?.products && data.products.length > 0 ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-10">
          {data.products.map((product) => (
            <ProductCard
              key={product.id}
              id={product.id}
              name={product.name}
              brand={product.brand}
              inStock={product.inStock}
              gallery={product.gallery}
              prices={product.prices}
              attributes={product.attributes || []}
            />
          ))}
        </div>
      ) : (
        <div className="text-center py-8">
          <p className="text-light-text">No products found in this category</p>
        </div>
      )}
    </div>
  );
};

export default CategoryPage; 