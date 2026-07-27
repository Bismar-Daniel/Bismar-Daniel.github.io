// Manejo del carrito de compras

let cart = [];

const cartCount = Document.gelElementById('cart-count');
const cartItems = Document.getElementById('cart-items');
const totalElement = Document.getElementById('total');
const cartModal = Document.getElementById('cart-modal');
const closeCart = Document.getElementById('close-cart');
const checkoutButton = Document.getElementById('checkout-button');
const purchaseModal = Document.getElementById('purchase-modal');
const closePurchase = Document.getElementById('close-purchase');

document.querySelectorAll(".add-to-cart").forEach(button => {
    button.addEventListener("click", function (event) {
        event.preventDefault();
        const productCard = button.closest(".card-product");
        const productName = productCard.querySelector("h3").textContent;
        const productPrice = parseFloat(productCard.querySelector(".price").textContent.replace("$", ""));
        const product = { name: productName, price: productPrice };
        cart.push(product);
        updateCartCount();
        saverCart();
        updateTotal();

    });
});

function updateCartCount() {
    cartCount.textContent = cart.length;
}

function saverCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

function displayCart() {
    cartItems.innerHTML = '';
    cart.forEach((item) => {
        const li = document.createElement('li');
        li.textContent = `${item.name} - $${item.price}`;
        cartItems.appendChild(li);
    });
}

function updateTotal() {
    const total = cart.reduce((acc, item) => acc + item.price, 0);
    totalElement.textContent = `Total: $${total}`;
}

document.getElementById('cart-icon').addEventListener('click', function () {
    cartModal.style.display = 'flex';
    displayCart();
    updateTotal();
});

closeCart.addEventListener('click', function () {
    cartModal.style.display = 'none';
});

checkoutButton.addEventListener('click', function () {
    purchaseModal.style.display = 'flex';
    cart = [];;
    updateCartCount();
    saverCart();
    displayCart();
    updateTotal();
    cartModal.style.display = 'none';
});

closePurchase.addEventListener('click', function () {
    purchaseModal.style.display = 'none';
});

function loadCart() {
    const savedCart = localStorage.getItem('cart');
    if (savedCart) {
        cart = JSON.parse(savedCart);
        updateCartCount();
        updateTotal();
    }
}

loadCart();