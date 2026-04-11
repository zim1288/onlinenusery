<?php
$pageTitle = 'Manage Plants - Green Nursery';
require_once __DIR__ . '/../includes/auth_check.php';
if (!isLoggedIn()) { header('Location: /login.php'); exit; }
if (!isAdmin())    { header('Location: /index.php'); exit; }
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>🌿 Manage Plants</h1>
    <nav class="admin-nav">
        <a href="/admin/index.php" class="btn btn-sm btn-secondary">Dashboard</a>
        <a href="/admin/plants.php" class="btn btn-sm btn-primary">Plants</a>
        <a href="/admin/orders.php" class="btn btn-sm btn-secondary">Orders</a>
        <a href="/admin/categories.php" class="btn btn-sm btn-secondary">Categories</a>
        <button class="btn btn-primary" onclick="openModal()">+ Add New Plant</button>
    </nav>
</div>

<div id="alertBox"></div>
<div id="plantsTable"></div>

<!-- Add/Edit Modal -->
<div id="plantModal" class="modal" style="display:none;">
    <div class="modal-overlay" onclick="closeModal()"></div>
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Plant</h3>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <form id="plantForm" enctype="multipart/form-data">
            <input type="hidden" id="plantId" name="id" value="">
            <div class="form-row">
                <div class="form-group">
                    <label>Plant Name *</label>
                    <input type="text" name="name" id="pName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" id="pCategory" class="form-control"></select>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="pDesc" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Price ($) *</label>
                    <input type="number" name="price" id="pPrice" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" id="pStock" class="form-control" min="0" value="0">
                </div>
            </div>
            <div class="form-group">
                <label>Image (jpg, png, gif, webp)</label>
                <input type="file" name="image" id="pImage" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
                <div id="currentImage" class="current-image"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveBtn">Save Plant</button>
            </div>
        </form>
    </div>
</div>

<script>
let categories = [];

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

function loadCategories() {
    return fetch('/api/get_categories.php')
        .then(r => r.json())
        .then(cats => {
            categories = cats;
            const sel = document.getElementById('pCategory');
            sel.innerHTML = '<option value="">-- Select Category --</option>' +
                cats.map(c => `<option value="${c.id}">${escHtml(c.name)}</option>`).join('');
        });
}

function loadPlants() {
    fetch('/api/get_plants.php')
        .then(r => r.json())
        .then(plants => {
            const div = document.getElementById('plantsTable');
            if (!plants.length) {
                div.innerHTML = '<p>No plants found. Add your first plant!</p>';
                return;
            }
            div.innerHTML = `
                <table class="admin-table">
                    <thead>
                        <tr><th>#</th><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Rating</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        ${plants.map(p => `
                            <tr>
                                <td>${p.id}</td>
                                <td><img src="/uploads/${encodeURIComponent(p.image)}" class="table-thumb"
                                         onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2250%22 height=%2250%22><rect fill=%22%23a5d6a7%22 width=%2250%22 height=%2250%22/><text x=%2250%%22 y=%2250%%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2220%22>🌿</text></svg>'; this.onerror=null;"></td>
                                <td>${escHtml(p.name)}</td>
                                <td>${escHtml(p.category_name || '—')}</td>
                                <td>$${parseFloat(p.price).toFixed(2)}</td>
                                <td><span class="stock-badge ${p.stock > 4 ? 'in-stock' : p.stock > 0 ? 'low-stock' : 'out-of-stock'}">${p.stock}</span></td>
                                <td>★ ${p.avg_rating}</td>
                                <td class="actions-cell">
                                    <button class="btn btn-sm btn-secondary" onclick='editPlant(${JSON.stringify(p)})'>Edit</button>
                                    <button class="btn btn-sm btn-danger" onclick="deletePlant('${p.id}', '${escHtml(p.name)}')">Delete</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        });
}

function openModal(plant = null) {
    document.getElementById('plantForm').reset();
    document.getElementById('currentImage').innerHTML = '';
    if (plant) {
        document.getElementById('modalTitle').textContent = 'Edit Plant';
        document.getElementById('plantId').value  = plant.id;
        document.getElementById('pName').value    = plant.name;
        document.getElementById('pDesc').value    = plant.description || '';
        document.getElementById('pPrice').value   = plant.price;
        document.getElementById('pStock').value   = plant.stock;
        document.getElementById('pCategory').value = plant.category_id;
        document.getElementById('saveBtn').textContent = 'Update Plant';
        if (plant.image && plant.image !== 'default.jpg') {
            document.getElementById('currentImage').innerHTML =
                `<img src="/uploads/${encodeURIComponent(plant.image)}" style="max-height:80px;margin-top:8px;">
                 <small>Current image</small>`;
        }
    } else {
        document.getElementById('modalTitle').textContent = 'Add New Plant';
        document.getElementById('plantId').value = '';
        document.getElementById('saveBtn').textContent = 'Save Plant';
    }
    document.getElementById('plantModal').style.display = 'flex';
}

function editPlant(plant) { openModal(plant); }

function closeModal() {
    document.getElementById('plantModal').style.display = 'none';
}

function deletePlant(id, name) {
    if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
    fetch('/api/delete_plant.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}`
    })
    .then(r => r.json())
    .then(data => {
        showAlert(data.message, data.success ? 'success' : 'error');
        if (data.success) loadPlants();
    })
    .catch(() => showAlert('Delete failed.', 'error'));
}

document.getElementById('plantForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn    = document.getElementById('saveBtn');
    const plantId = document.getElementById('plantId').value;
    btn.disabled = true;
    btn.textContent = 'Saving...';

    const formData = new FormData(this);
    const url = plantId ? '/api/update_plant.php' : '/api/add_plant.php';

    fetch(url, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            showAlert(data.message, data.success ? 'success' : 'error');
            if (data.success) { closeModal(); loadPlants(); }
            btn.disabled = false;
            btn.textContent = plantId ? 'Update Plant' : 'Save Plant';
        })
        .catch(() => {
            showAlert('Save failed.', 'error');
            btn.disabled = false;
        });
});

// Init
loadCategories().then(() => loadPlants());
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
