import React from 'react';
import { Link } from 'react-router-dom';
import HeroSection from '../components/HeroSection';
import FeaturedProducts from '../components/FeaturedProducts';
import './Home.css'; // Assuming you have a CSS file for styling

const Home: React.FC = () => {
    return (
        <div className="home">
            <HeroSection />
            <div className="categories">
                <h2>Shop by Category</h2>
                <div className="category-links">
                    <Link to="/products?category=men">Men's Apparel</Link>
                    <Link to="/products?category=women">Women's Apparel</Link>
                    <Link to="/products?category=accessories">Accessories</Link>
                </div>
            </div>
            <FeaturedProducts />
        </div>
    );
};

export default Home;