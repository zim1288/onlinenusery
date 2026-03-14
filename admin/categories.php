<?php
$pageTitle = 'Manage Categories - Green Nursery';
require_once __DIR__ . '/../includes/auth_check.php';
if (!isLoggedIn()) { header('Location: /login.php'); exit; }
if (!isAdmin())    { header('Location: /index.php'); exit; }
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>🏷 Manage Categories</h1>
    <nav class="admin-nav">
        <a href="/admin/index.php" class="btn btn-sm btn-secondary">Dashboard</a>
        <a href="/admin/plants.php" class="btn btn-sm btn-secondary">Plants</a>
        <a href="/admin/orders.php" class="btn btn-sm btn-secondary">Orders</a>
        <a href="/admin/categories.php" class="btn btn-sm btn-primary">Categories</a>
        <button class="btn btn-primary" onclick="openModal()">+ Add Category</button>
    </nav>
</div>

<div id="alertBox"></div>
<div id="catTable"></div>

<!-- Add/Edit Modal -->
<div id="catModal" class="modal" style="display:none;">
    <div class="modal-overlay" onclick="closeModal()"></div>
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modalTitle">Add Category</h3>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <form id="catForm">
            <input type="hidden" id="catId" name="id" value="">
            <div class="form-group">
                <label>Category Name *</label>
                <input type="text" name="name" id="catName" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="catDesc" class="form-control" rows="3"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
            </div>
        </form>
    </div>
</div>

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

function loadCategories() {
    fetch('/api/categories.php')
        .then(r => r.json())
        .then(cats => {
            const div = document.getElementById('catTable');
            if (!cats.length) {
                div.innerHTML = '<p>No categories yet. Add your first one!</p>';
                return;
            }
            div.innerHTML = `
                <table class="admin-table">
                    <thead><tr><th>#</th><th>Name</th><th>Description</th><th>Actions</th></tr></thead>
                    <tbody>
                        ${cats.map(c => `
                            <tr>
                                <td>${c.id}</td>
                                <td>${escHtml(c.name)}</td>
                                <td>${escHtml(c.description || '—')}</td>
                                <td class="actions-cell">
                                    <button class="btn btn-sm btn-secondary" onclick='editCategory(${JSON.stringify(c)})'>Edit</button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteCategory(${c.id}, '${escHtml(c.name)}')">Delete</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        });
}

function openModal(cat = null) {
    document.getElementById('catForm').reset();
    if (cat) {
        document.getElementById('modalTitle').textContent = 'Edit Category';
        document.getElementById('catId').value   = cat.id;
        document.getElementById('catName').value = cat.name;
        document.getElementById('catDesc').value = cat.description || '';
        document.getElementById('saveBtn').textContent = 'Update';
    } else {
        document.getElementById('modalTitle').textContent = 'Add Category';
        document.getElementById('catId').value = '';
        document.getElementById('saveBtn').textContent = 'Save';
    }
    document.getElementById('catModal').style.display = 'flex';
}

function editCategory(cat) { openModal(cat); }

function closeModal() {
    document.getElementById('catModal').style.display = 'none';
}

function deleteCategory(id, name) {
    if (!confirm(`Delete category "${name}"?`)) return;
    fetch('/api/categories.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=delete&id=${id}`
    })
    .then(r => r.json())
    .then(data => {
        showAlert(data.message, data.success ? 'success' : 'error');
        if (data.success) loadCategories();
    });
}

document.getElementById('catForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn   = document.getElementById('saveBtn');
    const catId = document.getElementById('catId').value;
    btn.disabled = true;

    const data = new URLSearchParams({
        action: catId ? 'update' : 'add',
        id: catId,
        name: document.getElementById('catName').value,
        description: document.getElementById('catDesc').value
    });

    fetch('/api/categories.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: data
    })
    .then(r => r.json())
    .then(res => {
        showAlert(res.message, res.success ? 'success' : 'error');
        if (res.success) { closeModal(); loadCategories(); }
        btn.disabled = false;
    });
});

loadCategories();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
