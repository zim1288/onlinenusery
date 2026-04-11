<?php
$pageTitle = 'Manage Orders - Green Nursery';
require_once __DIR__ . '/../includes/auth_check.php';
if (!isLoggedIn()) { header('Location: /login.php'); exit; }
if (!isAdmin())    { header('Location: /index.php'); exit; }
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>📦 Manage Orders</h1>
    <nav class="admin-nav">
        <a href="/admin/index.php" class="btn btn-sm btn-secondary">Dashboard</a>
        <a href="/admin/plants.php" class="btn btn-sm btn-secondary">Plants</a>
        <a href="/admin/orders.php" class="btn btn-sm btn-primary">Orders</a>
        <a href="/admin/categories.php" class="btn btn-sm btn-secondary">Categories</a>
    </nav>
</div>

<div id="alertBox"></div>
<div id="ordersTable"></div>

<script>
const statusColors = {
    pending: 'status-pending', processing: 'status-processing',
    shipped: 'status-shipped', delivered: 'status-delivered', cancelled: 'status-cancelled'
};

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

function updateStatus(orderId, newStatus) {
    fetch('/api/update_order_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `order_id=${orderId}&status=${newStatus}`
    })
    .then(r => r.json())
    .then(data => {
        showAlert(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            const badge = document.getElementById(`status-badge-${orderId}`);
            if (badge) {
                badge.textContent = newStatus;
                badge.className = `status-badge ${statusColors[newStatus] || ''}`;
            }
        }
    })
    .catch(() => showAlert('Update failed.', 'error'));
}

function toggleItems(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.display = el.style.display === 'none' ? 'table-row' : 'none';
}

function loadOrders() {
    fetch('/api/get_orders.php')
        .then(r => r.json())
        .then(orders => {
            const div = document.getElementById('ordersTable');
            if (!orders.length) {
                div.innerHTML = '<p>No orders yet.</p>';
                return;
            }
            div.innerHTML = `
                <table class="admin-table">
                    <thead>
                        <tr><th>ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        ${orders.map(o => `
                            <tr>
                                <td>#${o.id}</td>
                                <td>${escHtml(o.username || 'Guest')}</td>
                                <td>$${parseFloat(o.total_amount).toFixed(2)}</td>
                                <td>
                                    <span id="status-badge-${o.id}" class="status-badge ${statusColors[o.status] || ''}">${o.status}</span>
                                    <select class="form-control form-control-sm status-select" onchange="updateStatus('${o.id}', this.value)" title="Change status">
                                        <option value="">Change status...</option>
                                        <option value="pending">Pending</option>
                                        <option value="processing">Processing</option>
                                        <option value="shipped">Shipped</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </td>
                                <td>${new Date(o.created_at).toLocaleDateString()}</td>
                                <td><button class="btn btn-sm btn-secondary" onclick="toggleItems('items-${o.id}')">View Items</button></td>
                            </tr>
                            <tr id="items-${o.id}" style="display:none;" class="items-row">
                                <td colspan="6">
                                    <table class="items-table">
                                        <thead><tr><th>Plant</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
                                        <tbody>
                                            ${o.items.map(item => `
                                                <tr>
                                                    <td>${escHtml(item.plant_name || 'Deleted Plant')}</td>
                                                    <td>${item.quantity}</td>
                                                    <td>$${parseFloat(item.price).toFixed(2)}</td>
                                                    <td>$${(item.price * item.quantity).toFixed(2)}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        })
        .catch(() => {
            document.getElementById('ordersTable').innerHTML = '<p class="error">Failed to load orders.</p>';
        });
}

loadOrders();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
