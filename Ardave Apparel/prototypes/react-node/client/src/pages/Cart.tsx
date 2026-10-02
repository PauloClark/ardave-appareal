import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { getCartItems, updateCartItem, removeCartItem } from '../services/cartService';
import { CartItem } from '../types';

const Cart: React.FC = () => {
    const [cartItems, setCartItems] = useState<CartItem[]>([]);
    const [loading, setLoading] = useState<boolean>(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetchCartItems = async () => {
            try {
                const items = await getCartItems();
                setCartItems(items);
            } catch (err) {
                setError('Failed to load cart items.');
            } finally {
                setLoading(false);
            }
        };

        fetchCartItems();
    }, []);

    const handleQuantityChange = async (itemId: string, quantity: number) => {
        try {
            const updatedItem = await updateCartItem(itemId, quantity);
            setCartItems((prevItems) =>
                prevItems.map((item) => (item.id === updatedItem.id ? updatedItem : item))
            );
        } catch (err) {
            setError('Failed to update item quantity.');
        }
    };

    const handleRemoveItem = async (itemId: string) => {
        try {
            await removeCartItem(itemId);
            setCartItems((prevItems) => prevItems.filter((item) => item.id !== itemId));
        } catch (err) {
            setError('Failed to remove item from cart.');
        }
    };

    const calculateSubtotal = () => {
        return cartItems.reduce((total, item) => total + item.price * item.quantity, 0);
    };

    if (loading) {
        return <div>Loading...</div>;
    }

    if (error) {
        return <div>{error}</div>;
    }

    return (
        <div className="cart-container">
            <h1>Your Cart</h1>
            {cartItems.length === 0 ? (
                <div>Your cart is empty. <Link to="/products">Continue shopping</Link></div>
            ) : (
                <div>
                    <ul>
                        {cartItems.map((item) => (
                            <li key={item.id}>
                                <div>
                                    <h2>{item.name}</h2>
                                    <p>Price: ${item.price.toFixed(2)}</p>
                                    <input
                                        type="number"
                                        value={item.quantity}
                                        min="1"
                                        onChange={(e) => handleQuantityChange(item.id, Number(e.target.value))}
                                    />
                                    <button onClick={() => handleRemoveItem(item.id)}>Remove</button>
                                </div>
                            </li>
                        ))}
                    </ul>
                    <h2>Subtotal: ${calculateSubtotal().toFixed(2)}</h2>
                    <Link to="/checkout" className="checkout-button">Proceed to Checkout</Link>
                </div>
            )}
        </div>
    );
};

export default Cart;