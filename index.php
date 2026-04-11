<?php
$pageTitle = 'Plant Catalog - Green Nursery';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>🌱 Our Plant Collection</h1>
    <p>Discover beautiful plants for every space and skill level</p>
</div>

<!-- Search & Filter Form -->
<form id="filterForm" class="search-form" onsubmit="return false;">
    <div class="form-group">
        <input type="text" id="searchInput" class="form-control" placeholder="Search plants...">
    </div>
    <div class="form-group">
        <select id="categorySelect" class="form-control">
            <option value="">All Categories</option>
        </select>
    </div>
    <div class="form-group">
        <input type="number" id="minPrice" class="form-control" placeholder="Min price" min="0" step="0.01">
    </div>
    <div class="form-group">
        <input type="number" id="maxPrice" class="form-control" placeholder="Max price" min="0" step="0.01">
    </div>
    <div class="form-group">
        <select id="sortSelect" class="form-control">
            <option value="newest">Newest First</option>
            <option value="price_asc">Price: Low to High</option>
            <option value="price_desc">Price: High to Low</option>
            <option value="name_asc">Name: A–Z</option>
        </select>
    </div>
    <button type="button" class="btn btn-primary" onclick="loadPlants()">🔍 Search</button>
    <button type="button" class="btn btn-secondary" onclick="resetFilters()">Reset</button>
</form>

<div id="alertBox"></div>
<div id="plantGrid" class="plant-grid"></div>
<div id="loadingMsg" class="loading-msg">Loading plants...</div>

<script>
function renderStars(avg) {
    const full  = Math.round(avg);
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        stars += i <= full ? '★' : '☆';
    }
    return `<span class="stars">${stars}</span> <small>(${avg})</small>`;
}

function showAlert(msg, type = 'success') {
    const box = document.getElementById('alertBox');
    box.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
    setTimeout(() => { box.innerHTML = ''; }, 3500);
}

function addToCart(plantId, stock) {
    if (stock < 1) { showAlert('This plant is out of stock.', 'error'); return; }
    fetch('/api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add&plant_id=${plantId}&quantity=1`
    })
    .then(r => r.json())
    .then(data => {
        if (data.redirect) { window.location.href = data.redirect; return; }
        if (data.success) {
            showAlert('✅ Added to cart!');
            updateCartBadge();
        } else {
            showAlert(data.message || 'Failed to add to cart.', 'error');
        }
    })
    .catch(() => showAlert('Something went wrong.', 'error'));
}

function updateCartBadge() {
    fetch('/api/cart.php?action=get')
        .then(r => r.json())
        .then(data => {
            const badge = document.querySelector('.cart-badge');
            const count = data.items ? data.items.reduce((s, i) => s + parseInt(i.quantity), 0) : 0;
            if (count > 0) {
                if (badge) { badge.textContent = count; }
                else {
                    const link = document.querySelector('.cart-link');
                    if (link) {
                        const b = document.createElement('span');
                        b.className = 'cart-badge';
                        b.textContent = count;
                        link.appendChild(b);
                    }
                }
            } else if (badge) { badge.remove(); }
        })
        .catch(() => {});
}

function loadPlants() {
    const search     = document.getElementById('searchInput').value;
    const categoryId = document.getElementById('categorySelect').value;
    const minPrice   = document.getElementById('minPrice').value;
    const maxPrice   = document.getElementById('maxPrice').value;
    const sort       = document.getElementById('sortSelect').value;

    const params = new URLSearchParams({ search, sort });
    if (categoryId) params.set('category_id', categoryId);
    if (minPrice)   params.set('min_price', minPrice);
    if (maxPrice)   params.set('max_price', maxPrice);

    const grid = document.getElementById('plantGrid');
    const msg  = document.getElementById('loadingMsg');
    grid.innerHTML = '';
    msg.style.display = 'block';

    fetch('/api/get_plants.php?' + params)
        .then(r => r.json())
        .then(plants => {
            msg.style.display = 'none';
            if (!plants.length) {
                grid.innerHTML = '<p class="no-results">No plants found. Try different filters.</p>';
                return;
            }
            grid.innerHTML = plants.map(p => `
                <div class="plant-card">
                    <a href="/plant.php?id=${p.id}">
                        <img src="/uploads/${encodeURIComponent(p.image)}"
                             alt="${escHtml(p.name)}"
                             onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22200%22><rect fill=%22%23a5d6a7%22 width=%22300%22 height=%22200%22/><text x=%2250%%22 y=%2250%%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2248%22>🌿</text></svg>'; this.onerror=null;">
                    </a>
                    <div class="card-body">
                        <span class="category-badge">${escHtml(p.category_name || 'Plant')}</span>
                        <h3 class="plant-name"><a href="/plant.php?id=${p.id}">${escHtml(p.name)}</a></h3>
                        <p class="plant-desc">${escHtml((p.description || '').substring(0, 100))}${p.description && p.description.length > 100 ? '…' : ''}</p>
                        <div class="card-meta">
                            <span class="price">$${parseFloat(p.price).toFixed(2)}</span>
                            <span class="stock-badge ${p.stock > 0 ? 'in-stock' : 'out-of-stock'}">${p.stock > 0 ? 'In Stock' : 'Out of Stock'}</span>
                        </div>
                        <div class="card-rating">${renderStars(p.avg_rating)}</div>
                        <div class="card-actions">
                            <button class="btn btn-primary btn-sm" onclick="addToCart('${p.id}', ${p.stock})" ${p.stock < 1 ? 'disabled' : ''}>
                                🛒 Add to Cart
                            </button>
                            <a href="/plant.php?id=${p.id}" class="btn btn-secondary btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
            `).join('');
        })
        .catch(() => { msg.style.display = 'none'; grid.innerHTML = '<p class="error">Failed to load plants.</p>'; });
}

function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str || ''));
    return d.innerHTML;
}

function resetFilters() {
    document.getElementById('searchInput').value   = '';
    document.getElementById('categorySelect').value = '';
    document.getElementById('minPrice').value       = '';
    document.getElementById('maxPrice').value       = '';
    document.getElementById('sortSelect').value     = 'newest';
    loadPlants();
}

// Load categories into dropdown
fetch('/api/get_categories.php')
    .then(r => r.json())
    .then(cats => {
        const sel = document.getElementById('categorySelect');
        cats.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name;
            sel.appendChild(opt);
        });
    });

// Initial load
loadPlants();

// Live search debounce
let debounceTimer;
['searchInput', 'minPrice', 'maxPrice'].forEach(id => {
    document.getElementById(id).addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(loadPlants, 400);
    });
});
document.getElementById('categorySelect').addEventListener('change', loadPlants);
document.getElementById('sortSelect').addEventListener('change', loadPlants);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
