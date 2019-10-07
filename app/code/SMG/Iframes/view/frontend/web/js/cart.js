document.getElementById('cart-checkout').addEventListener('click', function() {
    window.parent.postMessage('cartClicked', '*');
});
