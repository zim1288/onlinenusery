<?php
$pageTitle = 'My Orders - Green Nursery';
require_once __DIR__ . '/includes/auth_check.php';
requireLogin('/login.php');
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>📦 My Orders</h1>
</div>

<div id="ordersContent"></div>

<script>
function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str || ''));
    return d.innerHTML;
}

const statusColors = {
    pending:    'status-pending',
    processing: 'status-processing',
    shipped:    'status-shipped',
    delivered:  'status-delivered',
    cancelled:  'status-cancelled'
};

function loadOrders() {
    fetch('/api/get_orders.php')
        .then(r => r.json())
        .then(orders => {
            const container = document.getElementById('ordersContent');
            if (!orders.length) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">📦</div>
                        <h2>No orders yet</h2>
                        <p>Start shopping to place your first order!</p>
                        <a href="/index.php" class="btn btn-primary">Browse Plants</a>
                    </div>`;
                return;
            }

            container.innerHTML = orders.map(order => `
                <div class="order-card">
                    <div class="order-header" onclick="toggleOrder('order-${order.id}')">
                        <div class="order-id">
                            <strong>Order #${order.id}</strong>
                        </div>
                        <div class="order-date">${new Date(order.created_at).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' })}</div>
                        <div class="order-total"><strong>$${parseFloat(order.total_amount).toFixed(2)}</strong></div>
                        <div><span class="status-badge ${statusColors[order.status] || ''}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></div>
                        <div class="order-toggle">▼</div>
                    </div>
                    <div class="order-items" id="order-${order.id}" style="display:none;">
                        <table class="items-table">
                            <thead><tr><th>Plant</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
                            <tbody>
                                ${order.items.map(item => `
                                    <tr>
                                        <td>${escHtml(item.plant_name || 'Deleted Plant')}</td>
                                        <td>${item.quantity}</td>
                                        <td>$${parseFloat(item.price).toFixed(2)}</td>
                                        <td>$${(item.price * item.quantity).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `).join('');
        })
        .catch(() => {
            document.getElementById('ordersContent').innerHTML = '<p class="error">Failed to load orders.</p>';
        });
}

function toggleOrder(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

loadOrders();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
