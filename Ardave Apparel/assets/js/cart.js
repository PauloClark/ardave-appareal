const cartKey = 'ardaveCart';

function getCart() {
  try {
    const stored = localStorage.getItem(cartKey);
    return stored ? JSON.parse(stored) : [];
  } catch (e) {
    return [];
  }
}

function saveCart(cart) {
  localStorage.setItem(cartKey, JSON.stringify(cart));
  window.dispatchEvent(new Event('cartUpdated'));
}

function normalizeItem(item) {
  const productId = item.product_id ?? item.id ?? null;
  return {
    ...item,
    id: String(productId ?? Math.random().toString(36).slice(2)),
    product_id: productId ?? item.product_id ?? null,
    price: Number(item.price) || 0,
    quantity: Math.max(1, Number(item.quantity) || 1),
    size: item.size || 'M',
  };
}

function addToCart(product) {
  const cart = getCart().map(normalizeItem);
  const sizeKey = String(product.size || '').toLowerCase();
  const existing = cart.find(item => String(item.product_id ?? item.id) === String(product.product_id ?? product.id) && String(item.size || '').toLowerCase() === sizeKey);

  if (existing) {
    existing.quantity += Math.max(1, Number(product.quantity) || 1);
  } else {
    cart.push({ ...normalizeItem(product), quantity: Math.max(1, Number(product.quantity) || 1) });
  }

  saveCart(cart);
  updateCartCount();
  return cart;
}

function removeFromCart(productId) {
  const cart = getCart().filter(item => String(item.product_id ?? item.id) !== String(productId));
  saveCart(cart);
  updateCartCount();
  return cart;
}

function updateQuantity(productId, quantity) {
  const cart = getCart().map(item => {
    if (String(item.product_id ?? item.id) === String(productId)) {
      return { ...item, quantity: Math.max(1, Number(quantity) || 1) };
    }
    return item;
  });
  saveCart(cart);
  updateCartCount();
  return cart;
}

function getCartTotal() {
  return getCart().reduce((total, item) => total + Number(item.price || 0) * Number(item.quantity || 1), 0);
}

function updateCartCount() {
  const countElement = document.querySelector('.cart-badge, .cart-count');
  if (!countElement) return;
  const totalCount = getCart().reduce((sum, item) => sum + Number(item.quantity || 0), 0);
  countElement.textContent = totalCount;
}

function renderCartItems(containerSelector) {
  const container = document.querySelector(containerSelector);
  if (!container) return;

  const cart = getCart();
  if (!cart.length) {
    container.innerHTML = '<div class="alert alert-light">Your cart is empty. Add a premium piece to continue.</div>';
    const totalElement = document.querySelector('.cart-total');
    if (totalElement) totalElement.textContent = '₱0.00';
    return;
  }

  container.innerHTML = cart.map(item => {
    const itemTotal = Number(item.price || 0) * Number(item.quantity || 1);
    const preview = item.design || item.logo || item.reference ? '<small class="text-muted">Includes uploaded artwork</small>' : '<small class="text-muted">No artwork uploaded</small>';
    return `
      <div class="cart-item">
        <div>
          <div class="fw-bold">${item.name || 'Product'}</div>
          <div class="text-muted small">Size: ${item.size || 'M'} • Qty: ${item.quantity || 1}</div>
          <div class="text-muted small">${preview}</div>
        </div>
        <div class="text-end">
          <div class="fw-bold">₱${itemTotal.toFixed(2)}</div>
          <div class="qty-control mt-2">
            <input type="number" min="1" class="form-control form-control-sm cart-quantity" data-product-id="${item.product_id ?? item.id}" value="${item.quantity || 1}">
            <button class="btn btn-outline-dark btn-sm cart-remove" data-product-id="${item.product_id ?? item.id}">Remove</button>
          </div>
        </div>
      </div>
    `;
  }).join('');

  // Update subtotal and any total displays
  const subtotalElement = document.querySelector('.cart-subtotal');
  if (subtotalElement) subtotalElement.textContent = `₱${getCartTotal().toFixed(2)}`;
  const totalElements = document.querySelectorAll('.cart-total');
  totalElements.forEach(el => el.textContent = `₱${getCartTotal().toFixed(2)}`);
}

function initCartPage() {
  document.addEventListener('click', event => {
    const removeButton = event.target.closest('.cart-remove');
    if (removeButton) {
      const productId = removeButton.dataset.productId;
      removeFromCart(productId);
      renderCartItems('.cart-items');
      return;
    }
  });

  document.addEventListener('change', event => {
    const quantityInput = event.target.closest('.cart-quantity');
    if (quantityInput) {
      const productId = quantityInput.dataset.productId;
      updateQuantity(productId, Number(quantityInput.value));
      renderCartItems('.cart-items');
    }
  });

  updateCartCount();
  renderCartItems('.cart-items');
}

window.cartApp = {
  getCart,
  addToCart,
  removeFromCart,
  updateQuantity,
  updateCartCount,
  renderCartItems,
  initCartPage,
};
