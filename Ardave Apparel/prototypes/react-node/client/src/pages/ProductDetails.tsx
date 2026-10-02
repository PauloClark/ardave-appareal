import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { getProductDetails } from '../services/productService';
import './ProductDetails.css';

const ProductDetails = () => {
    const { id } = useParams();
    const [product, setProduct] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        const fetchProductDetails = async () => {
            try {
                const data = await getProductDetails(id);
                setProduct(data);
            } catch (err) {
                setError('Failed to fetch product details');
            } finally {
                setLoading(false);
            }
        };

        fetchProductDetails();
    }, [id]);

    if (loading) return <div>Loading...</div>;
    if (error) return <div>{error}</div>;

    return (
        <div className="product-details">
            <h1>{product.name}</h1>
            <div className="product-images">
                {product.images.map((image, index) => (
                    <img key={index} src={image} alt={product.name} />
                ))}
            </div>
            <p>{product.description}</p>
            <div className="product-variants">
                <h3>Variants</h3>
                {product.variants.map((variant, index) => (
                    <div key={index}>
                        <span>{variant.size}</span> - <span>{variant.color}</span>
                    </div>
                ))}
            </div>
            <p>Price: ${product.price}</p>
            <p>Stock: {product.stock > 0 ? product.stock : 'Out of stock'}</p>
            <button disabled={product.stock === 0}>Add to Cart</button>
        </div>
    );
};

export default ProductDetails;