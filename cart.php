<?php
$pageTitle = 'Shopping Cart - Green Nursery';
require_once __DIR__ . '/includes/auth_check.php';
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>🛒 Shopping Cart</h1>
</div>

<div id="alertBox"></div>
<div id="cartContent"></div>

<script>
function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str || ''));
    return d.innerHTML;
}

function showAlert(msg, type = 'success') {
    const box = document.getElementById('alertBox');
    box.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
    if (type === 'success') setTimeout(() => { box.innerHTML = ''; }, 3500);
}

function loadCart() {
    fetch('/api/cart.php?action=get')
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('cartContent');
            if (!data.success || !data.items || data.items.length === 0) {
                container.innerHTML = `
                    <div class="empty-cart">
                        <div class="empty-icon">🌿</div>
                        <h2>Your cart is empty</h2>
                        <p>Explore our plant collection and add something you love!</p>
                        <a href="/index.php" class="btn btn-primary">Browse Plants</a>
                    </div>`;
                return;
            }

            let rows = data.items.map(item => `
                <tr class="cart-row" id="row-${item.plant_id}">
                    <td class="cart-img-cell">
                        <img src="/uploads/${encodeURIComponent(item.image)}"
                             alt="${escHtml(item.name)}"
                             class="cart-thumb"
                             onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2280%22 height=%2280%22><rect fill=%22%23a5d6a7%22 width=%2280%22 height=%2280%22/><text x=%2250%%22 y=%2250%%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2232%22>🌿</text></svg>'; this.onerror=null;">
                    </td>
                    <td class="cart-name-cell">
                        <a href="/plant.php?id=${item.plant_id}">${escHtml(item.name)}</a>
                        <div class="unit-price">$${parseFloat(item.price).toFixed(2)} each</div>
                    </td>
                    <td class="cart-qty-cell">
                        <div class="quantity-controls">
                            <button class="qty-btn" onclick="updateQty('${item.plant_id}', ${item.quantity - 1}, ${item.stock})">−</button>
                            <span class="qty-display">${item.quantity}</span>
                            <button class="qty-btn" onclick="updateQty('${item.plant_id}', ${item.quantity + 1}, ${item.stock})">+</button>
                        </div>
                    </td>
                    <td class="cart-subtotal">$${(parseFloat(item.price) * item.quantity).toFixed(2)}</td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="removeItem('${item.plant_id}')">🗑 Remove</button>
                    </td>
                </tr>
            `).join('');

            container.innerHTML = `
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Plant</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
                <div class="cart-summary">
                    <div class="cart-total">
                        <strong>Total: <span class="total-amount">$${parseFloat(data.total).toFixed(2)}</span></strong>
                    </div>
                    <div class="cart-actions-row">
                        <a href="/index.php" class="btn btn-secondary">← Continue Shopping</a>
                        <button class="btn btn-primary btn-lg" onclick="checkout()" id="checkoutBtn">
                            ✅ Place Order ($${parseFloat(data.total).toFixed(2)})
                        </button>
                    </div>
                </div>`;
        })
        .catch(() => showAlert('Failed to load cart.', 'error'));
}

function updateQty(plantId, newQty, stock) {
    if (newQty < 1) { removeItem(plantId); return; }
    if (newQty > stock) { showAlert('Not enough stock available.', 'error'); return; }
    fetch('/api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update&plant_id=${plantId}&quantity=${newQty}`
    })
    .then(r => r.json())
    .then(data => { if (data.success) loadCart(); });
}

function removeItem(plantId) {
    fetch('/api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=remove&plant_id=${plantId}`
    })
    .then(r => r.json())
    .then(data => { if (data.success) { showAlert('Item removed.'); loadCart(); } });
}

function checkout() {
    const btn = document.getElementById('checkoutBtn');
    btn.textContent = 'Placing order...';
    btn.disabled = true;
    fetch('/api/place_order.php', { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.redirect) { window.location.href = data.redirect; return; }
            if (data.success) {
                showAlert(`✅ Order #${data.order_id} placed successfully! Redirecting...`);
                setTimeout(() => { window.location.href = '/orders.php'; }, 1500);
            } else {
                showAlert(data.message || 'Failed to place order.', 'error');
                btn.textContent = '✅ Place Order';
                btn.disabled = false;
            }
        })
        .catch(() => {
            showAlert('Something went wrong.', 'error');
            btn.textContent = '✅ Place Order';
            btn.disabled = false;
        });
}

loadCart();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
