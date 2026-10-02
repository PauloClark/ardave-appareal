import React from 'react';
import { BrowserRouter as Router, Route, Switch } from 'react-router-dom';
import Home from './pages/Home';
import Products from './pages/Products';
import ProductDetails from './pages/ProductDetails';
import Cart from './pages/Cart';
import Checkout from './pages/Checkout';
import TrackOrder from './pages/TrackOrder';
import Account from './pages/Account';
import AdminDashboard from './pages/admin/AdminDashboard'; // Assuming an AdminDashboard component exists
import './App.css'; // Assuming a CSS file for global styles

const App = () => {
  return (
    <Router>
      <Switch>
        <Route path="/" exact component={Home} />
        <Route path="/products" component={Products} />
        <Route path="/products/:id" component={ProductDetails} />
        <Route path="/cart" component={Cart} />
        <Route path="/checkout" component={Checkout} />
        <Route path="/track-order" component={TrackOrder} />
        <Route path="/account" component={Account} />
        <Route path="/admin" component={AdminDashboard} /> {/* Admin route */}
      </Switch>
    </Router>
  );
};

export default App;