(function () {
  function initProductsPage() {
    const toast = document.querySelector('.shop-toast');
    let toastTimer;
    document.addEventListener('click', event => {
      const button = event.target.closest('[data-add-to-cart]');
      if (!button) return;
      const card = button.closest('.shop-card');
      const sizeSelect = card?.querySelector('.shop-size');
      if (!card || !sizeSelect) return;
      if (!sizeSelect.value) {
        sizeSelect.focus();
        showToast('Please choose a size first.');
        return;
      }
      if (!window.cartApp?.addToCart) {
        showToast('Cart is unavailable. Please refresh and try again.');
        return;
      }
      window.cartApp.addToCart({
        id: card.dataset.productId,
        product_id: card.dataset.productId,
        name: card.dataset.productName,
        price: Number(card.dataset.productPrice),
        size: sizeSelect.value,
        quantity: 1,
      });
      showToast(card.dataset.productName + ' added to your cart.');
    });
    function showToast(message) {
      if (!toast) return;
      toast.textContent = message;
      toast.hidden = false;
      clearTimeout(toastTimer);
      toastTimer = setTimeout(() => { toast.hidden = true; }, 4000);
    }
  }
  window.productsApp = { initProductsPage };
}());
