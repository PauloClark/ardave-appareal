function validatePaymentForm(formSelector) {
  const form = document.querySelector(formSelector);
  if (!form) return;

  const status = form.querySelector('.payment-status');
  const requiredFields = form.querySelectorAll('[data-required]');

  form.addEventListener('submit', event => {
    event.preventDefault();
    let valid = true;
    requiredFields.forEach(input => {
      if (!input.value.trim()) {
        valid = false;
        input.classList.add('input-error');
      } else {
        input.classList.remove('input-error');
      }
    });

    if (!valid) {
      if (status) {
        status.textContent = 'Please fill in all required fields.';
        status.classList.add('error');
      }
      return;
    }

    if (status) {
      status.textContent = 'Payment submitted successfully. Thank you!';
      status.classList.remove('error');
      status.classList.add('success');
    }

    form.reset();
    localStorage.removeItem('ardaveCart');
    document.querySelectorAll('.cart-quantity').forEach(input => input.value = 1);
    // Reset subtotal and total displays
    const subtotalElement = document.querySelector('.cart-subtotal');
    if (subtotalElement) subtotalElement.textContent = '₱0.00';
    const totalElements = document.querySelectorAll('.cart-total');
    totalElements.forEach(el => el.textContent = '₱0.00');
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) cartCount.textContent = '0';
  });
}

function initPaymentPage() {
  validatePaymentForm('#paymentForm');
}

window.paymentApp = {
  validatePaymentForm,
  initPaymentPage,
};
