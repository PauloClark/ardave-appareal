import React, { useState } from 'react';

const TrackOrder: React.FC = () => {
    const [orderId, setOrderId] = useState('');
    const [orderDetails, setOrderDetails] = useState(null);
    const [error, setError] = useState('');

    const handleTrackOrder = async (e: React.FormEvent) => {
        e.preventDefault();
        setError('');
        setOrderDetails(null);

        try {
            const response = await fetch(`/api/orders/${orderId}`);
            if (!response.ok) {
                throw new Error('Order not found');
            }
            const data = await response.json();
            setOrderDetails(data);
        } catch (err) {
            setError(err.message);
        }
    };

    return (
        <div className="track-order">
            <h1>Track Your Order</h1>
            <form onSubmit={handleTrackOrder}>
                <input
                    type="text"
                    placeholder="Enter your Order ID"
                    value={orderId}
                    onChange={(e) => setOrderId(e.target.value)}
                    required
                />
                <button type="submit">Track Order</button>
            </form>
            {error && <p className="error">{error}</p>}
            {orderDetails && (
                <div className="order-details">
                    <h2>Order Details</h2>
                    <p>Order ID: {orderDetails.id}</p>
                    <p>Status: {orderDetails.status}</p>
                    <p>Total: ${orderDetails.total}</p>
                    <h3>Items:</h3>
                    <ul>
                        {orderDetails.items.map((item: any) => (
                            <li key={item.id}>
                                {item.name} - {item.quantity} x ${item.price}
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
};

export default TrackOrder;