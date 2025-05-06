# Scandiweb E-commerce Test Project

This is an e-commerce website with product listing and cart functionality, developed according to the Scandiweb Junior Full Stack Developer test task requirements.

## Project Structure

- `/backend` - PHP 7.4+ backend with GraphQL API
- `/frontend` - React frontend with TypeScript and Vite

## Backend Setup

1. Make sure you have PHP 7.4+ and MySQL 5.6+ installed
2. Navigate to the backend directory: `cd backend`
3. Install dependencies: `composer install`
4. Copy `.env.example` to `.env` and configure your database connection
5. Import the database schema: `php src/Database/setup.php`
6. Set up your web server (Apache/Nginx) to point to the backend directory

## Frontend Setup

1. Navigate to the frontend directory: `cd frontend`
2. Install dependencies: `npm install`
3. Update the API URL in `src/graphql/client.ts` if needed
4. Start the development server: `npm run dev`
5. For production build: `npm run build`

## Features

- Product listing by categories
- Product detail view with attribute selection
- Shopping cart functionality with the ability to change quantities
- Checkout process

## Technologies Used

### Backend
- PHP 7.4+
- MySQL
- GraphQL
- Doctrine DBAL

### Frontend
- React
- TypeScript
- Apollo Client for GraphQL
- Tailwind CSS for styling
- Vite build tool

## Testing

To test the application using the Scandiweb AutoQA tool:
1. Deploy the application to a public server
2. Visit: http://165.227.98.170/
3. Enter your deployed application URL 