import { gql } from '@apollo/client';

export const PLACE_ORDER = gql`
  mutation PlaceOrder($order: OrderInput!) {
    placeOrder(order: $order) {
      success
      order {
        id
        total
        currency
        status
        items {
          id
          product {
            id
            name
          }
          quantity
          unitPrice
        }
      }
      message
    }
  }
`; 