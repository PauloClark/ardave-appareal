import { connect } from '../src/database'; // Adjust the import path as necessary
import User from '../src/models/User';
import Product from '../src/models/Product';
import Order from '../src/models/Order';

const seedDatabase = async () => {
    try {
        await connect();

        // Clear existing data
        await User.deleteMany({});
        await Product.deleteMany({});
        await Order.deleteMany({});

        // Seed users
        const users = [
            { username: 'admin', password: 'admin123', role: 'admin' },
            { username: 'customer1', password: 'password1', role: 'customer' },
            { username: 'customer2', password: 'password2', role: 'customer' },
        ];
        await User.insertMany(users);

        // Seed products
        const products = [
            {
                name: 'Sports T-Shirt',
                description: 'High-quality sports t-shirt for athletes.',
                price: 29.99,
                sizes: ['S', 'M', 'L', 'XL'],
                colors: ['Red', 'Blue', 'Black'],
                stock: 100,
                images: ['image1.jpg', 'image2.jpg'],
            },
            {
                name: 'Running Shorts',
                description: 'Comfortable running shorts for all-day wear.',
                price: 24.99,
                sizes: ['S', 'M', 'L', 'XL'],
                colors: ['Black', 'Gray'],
                stock: 50,
                images: ['image3.jpg', 'image4.jpg'],
            },
        ];
        await Product.insertMany(products);

        // Seed orders
        const orders = [
            {
                userId: 'customer1', // Replace with actual user ID
                products: [
                    { productId: 'product1', quantity: 2 }, // Replace with actual product ID
                ],
                status: 'Pending',
                total: 59.98,
            },
            {
                userId: 'customer2', // Replace with actual user ID
                products: [
                    { productId: 'product2', quantity: 1 }, // Replace with actual product ID
                ],
                status: 'Pending',
                total: 24.99,
            },
        ];
        await Order.insertMany(orders);

        console.log('Database seeded successfully!');
    } catch (error) {
        console.error('Error seeding database:', error);
    } finally {
        process.exit();
    }
};

seedDatabase();