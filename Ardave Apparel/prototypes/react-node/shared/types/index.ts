export type User = {
    id: string;
    username: string;
    email: string;
    passwordHash: string;
    createdAt: Date;
    updatedAt: Date;
};

export type Product = {
    id: string;
    name: string;
    description: string;
    price: number;
    stock: number;
    categoryId: string;
    images: string[];
    variants: ProductVariant[];
    createdAt: Date;
    updatedAt: Date;
};

export type ProductVariant = {
    id: string;
    size: string;
    color: string;
    stock: number;
};

export type Category = {
    id: string;
    name: string;
    createdAt: Date;
    updatedAt: Date;
};

export type Order = {
    id: string;
    userId: string;
    items: OrderItem[];
    total: number;
    status: OrderStatus;
    createdAt: Date;
    updatedAt: Date;
};

export type OrderItem = {
    productId: string;
    quantity: number;
    price: number;
};

export enum OrderStatus {
    Pending = 'Pending',
    Confirmed = 'Confirmed',
    Processing = 'Processing',
    ReadyToShip = 'Ready to Ship',
    Shipped = 'Shipped',
    Delivered = 'Delivered',
    Cancelled = 'Cancelled',
}

export type CartItem = {
    productId: string;
    quantity: number;
    size?: string;
    color?: string;
};