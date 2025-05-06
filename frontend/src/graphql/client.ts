import { ApolloClient, InMemoryCache, HttpLink, from, ApolloLink } from '@apollo/client';
import { onError } from '@apollo/client/link/error';

// GraphQL API endpoint - uncomment the one you're using
// For XAMPP
const API_URL = 'http://localhost/ScandiwebProj/backend/index.php';
// For PHP built-in server
// const API_URL = 'http://localhost:8000';

// Remove __typename fields from all responses
const removeTypenameLink = new ApolloLink((operation, forward) => {
  if (operation.variables) {
    operation.variables = JSON.parse(JSON.stringify(operation.variables), (key, value) => {
      return key === '__typename' ? undefined : value;
    });
  }
  
  return forward(operation).map((response) => {
    if (response.data) {
      response.data = JSON.parse(JSON.stringify(response.data), (key, value) => {
        return key === '__typename' ? undefined : value;
      });
    }
    return response;
  });
});

// Error handling link
const errorLink = onError(({ graphQLErrors, networkError, operation, response }) => {
  if (graphQLErrors) {
    graphQLErrors.forEach(({ message, locations, path, extensions }) => {
      console.error(
        `[GraphQL error]: Message: ${message}, Location: ${JSON.stringify(locations)}, Path: ${path}`,
        extensions
      );
      
      // Attempt to provide fallback data for common errors
      if (response && response.data) {
        if (path && path[0] === 'products' && Array.isArray(response.data.products)) {
          // Ensure products array is at least empty array rather than null
          if (response.data.products === null) {
            response.data.products = [];
          }
        }
        if (path && path[0] === 'categories' && Array.isArray(response.data.categories)) {
          // Ensure categories array is at least empty array rather than null
          if (response.data.categories === null) {
            response.data.categories = [];
          }
        }
      }
    });
  }
  
  if (networkError) {
    console.error(`[Network error]: ${networkError}`);
  }
});

const httpLink = new HttpLink({
  uri: API_URL,
  credentials: 'same-origin'
});

const client = new ApolloClient({
  link: from([removeTypenameLink, errorLink, httpLink]),
  cache: new InMemoryCache({
    addTypename: false
  }),
  defaultOptions: {
    watchQuery: {
      fetchPolicy: 'cache-first',
      errorPolicy: 'all',
    },
    query: {
      fetchPolicy: 'cache-first',
      errorPolicy: 'all',
    },
  },
});

export default client; 