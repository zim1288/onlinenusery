<?php
$pageTitle = 'Admin Dashboard - Green Nursery';
require_once __DIR__ . '/../includes/auth_check.php';
if (!isLoggedIn()) { header('Location: /login.php'); exit; }
if (!isAdmin())    { header('Location: /index.php'); exit; }
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>⚙ Admin Dashboard</h1>
    <nav class="admin-nav">
        <a href="/admin/index.php" class="btn btn-sm btn-primary">Dashboard</a>
        <a href="/admin/plants.php" class="btn btn-sm btn-secondary">Manage Plants</a>
        <a href="/admin/orders.php" class="btn btn-sm btn-secondary">Manage Orders</a>
        <a href="/admin/categories.php" class="btn btn-sm btn-secondary">Manage Categories</a>
    </nav>
</div>

<div id="statsGrid" class="stats-grid"></div>

<div class="admin-sections">
    <div class="admin-section">
        <h2>📦 Recent Orders</h2>
        <div id="recentOrders"></div>
    </div>
    <div class="admin-section">
        <h2>⚠ Low Stock Plants</h2>
        <div id="lowStock"></div>
    </div>
</div>

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

fetch('/api/admin_stats.php')
    .then(r => r.json())
    .then(data => {
        document.getElementById('statsGrid').innerHTML = `
            <div class="stat-card">
                <div class="stat-icon">🌿</div>
                <div class="stat-number">${data.total_plants}</div>
                <div class="stat-label">Total Plants</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-number">${data.total_orders}</div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number">${data.total_users}</div>
                <div class="stat-label">Customers</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-number">$${parseFloat(data.total_revenue).toFixed(2)}</div>
                <div class="stat-label">Total Revenue</div>
            </div>
        `;

        // Recent orders
        const ordersDiv = document.getElementById('recentOrders');
        if (!data.recent_orders.length) {
            ordersDiv.innerHTML = '<p>No orders yet.</p>';
        } else {
            ordersDiv.innerHTML = `
                <table class="admin-table">
                    <thead><tr><th>ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        ${data.recent_orders.map(o => `
                            <tr>
                                <td>#${o.id}</td>
                                <td>${escHtml(o.username || 'Guest')}</td>
                                <td>$${parseFloat(o.total_amount).toFixed(2)}</td>
                                <td><span class="status-badge ${statusColors[o.status] || ''}">${o.status}</span></td>
                                <td>${new Date(o.created_at).toLocaleDateString()}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }

        // Low stock
        const lowDiv = document.getElementById('lowStock');
        if (!data.low_stock.length) {
            lowDiv.innerHTML = '<p class="success-msg">✅ All plants have sufficient stock.</p>';
        } else {
            lowDiv.innerHTML = `
                <table class="admin-table">
                    <thead><tr><th>Plant</th><th>Stock</th><th>Price</th><th>Action</th></tr></thead>
                    <tbody>
                        ${data.low_stock.map(p => `
                            <tr class="${p.stock === 0 ? 'row-danger' : 'row-warning'}">
                                <td>${escHtml(p.name)}</td>
                                <td><span class="stock-badge ${p.stock === 0 ? 'out-of-stock' : 'low-stock'}">${p.stock}</span></td>
                                <td>$${parseFloat(p.price).toFixed(2)}</td>
                                <td><a href="/admin/plants.php" class="btn btn-sm btn-primary">Update Stock</a></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }
    })
    .catch(() => {
        document.getElementById('statsGrid').innerHTML = '<p class="error">Failed to load stats.</p>';
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
