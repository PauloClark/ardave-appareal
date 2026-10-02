import React, { useState } from 'react';
import { useHistory } from 'react-router-dom';
import { useCart } from '../hooks/useCart';
import { createOrder } from '../services/orderService';

const Checkout = () => {
    const history = useHistory();
    const { cartItems, clearCart } = useCart();
    const [customerInfo, setCustomerInfo] = useState({
        name: '',
        contactNumber: '',
        email: '',
        address: '',
        orderNotes: ''
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const handleChange = (e) => {
        const { name, value } = e.target;
        setCustomerInfo({ ...customerInfo, [name]: value });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const orderData = {
                customerInfo,
                items: cartItems,
                total: cartItems.reduce((acc, item) => acc + item.price * item.quantity, 0)
            };
            await createOrder(orderData);
            clearCart();
            history.push('/thank-you'); // Redirect to a thank you page or order confirmation
        } catch (err) {
            setError('Failed to create order. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="checkout">
            <h2>Checkout</h2>
            {error && <div className="error">{error}</div>}
            <form onSubmit={handleSubmit}>
                <div>
                    <label>Name:</label>
                    <input type="text" name="name" value={customerInfo.name} onChange={handleChange} required />
                </div>
                <div>
                    <label>Contact Number:</label>
                    <input type="text" name="contactNumber" value={customerInfo.contactNumber} onChange={handleChange} required />
                </div>
                <div>
                    <label>Email:</label>
                    <input type="email" name="email" value={customerInfo.email} onChange={handleChange} required />
                </div>
                <div>
                    <label>Delivery Address:</label>
                    <textarea name="address" value={customerInfo.address} onChange={handleChange} required />
                </div>
                <div>
                    <label>Order Notes:</label>
                    <textarea name="orderNotes" value={customerInfo.orderNotes} onChange={handleChange} />
                </div>
                <button type="submit" disabled={loading}>
                    {loading ? 'Processing...' : 'Place Order'}
                </button>
            </form>
        </div>
    );
};

export default Checkout;