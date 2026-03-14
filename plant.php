<?php
require_once __DIR__ . '/api/db.php';
require_once __DIR__ . '/includes/auth_check.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /index.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT p.*, c.name AS category_name,
           ROUND(COALESCE(AVG(r.rating), 0), 1) AS avg_rating,
           COUNT(r.id) AS review_count
    FROM plants p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN reviews r ON p.id = r.plant_id
    WHERE p.id = ?
    GROUP BY p.id
");
$stmt->bind_param('i', $id);
$stmt->execute();
$plant = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$plant) {
    header('Location: /index.php');
    exit;
}

$pageTitle = htmlspecialchars($plant['name']) . ' - Green Nursery';
require_once __DIR__ . '/includes/header.php';
?>

<div class="plant-detail">
    <a href="/index.php" class="back-link">← Back to Catalog</a>

    <div class="plant-detail-grid">
        <div class="plant-detail-image">
            <img id="plantImage"
                 src="/uploads/<?= htmlspecialchars($plant['image']) ?>"
                 alt="<?= htmlspecialchars($plant['name']) ?>"
                 onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22500%22 height=%22400%22><rect fill=%22%23a5d6a7%22 width=%22500%22 height=%22400%22/><text x=%2250%%22 y=%2250%%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2296%22>🌿</text></svg>'; this.onerror=null;">
        </div>

        <div class="plant-detail-info">
            <span class="category-badge"><?= htmlspecialchars($plant['category_name'] ?? 'Plant') ?></span>
            <h1><?= htmlspecialchars($plant['name']) ?></h1>

            <div class="detail-rating">
                <?php
                $full = round($plant['avg_rating']);
                for ($i = 1; $i <= 5; $i++) {
                    echo $i <= $full ? '<span class="star filled">★</span>' : '<span class="star">☆</span>';
                }
                ?>
                <span class="rating-value"><?= $plant['avg_rating'] ?> / 5</span>
                <span class="review-count">(<?= $plant['review_count'] ?> review<?= $plant['review_count'] != 1 ? 's' : '' ?>)</span>
            </div>

            <p class="detail-price">$<?= number_format($plant['price'], 2) ?></p>

            <div class="stock-info">
                <?php if ($plant['stock'] > 0): ?>
                    <span class="stock-badge in-stock">✓ In Stock (<?= $plant['stock'] ?> available)</span>
                <?php else: ?>
                    <span class="stock-badge out-of-stock">✗ Out of Stock</span>
                <?php endif; ?>
            </div>

            <div class="detail-description">
                <h3>Description</h3>
                <p><?= nl2br(htmlspecialchars($plant['description'] ?? '')) ?></p>
            </div>

            <?php if ($plant['stock'] > 0): ?>
            <form id="addToCartForm" class="add-to-cart-form">
                <div class="quantity-row">
                    <label for="quantity">Quantity:</label>
                    <div class="quantity-controls">
                        <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= $plant['stock'] ?>" class="qty-input">
                        <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg">🛒 Add to Cart</button>
            </form>
            <div id="cartAlert"></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="reviews-section">
        <h2>Customer Reviews</h2>
        <div id="reviewsList"></div>

        <?php if (isLoggedIn()): ?>
        <div class="review-form-container">
            <h3>Write a Review</h3>
            <form id="reviewForm">
                <div class="form-group">
                    <label>Rating:</label>
                    <select name="rating" id="ratingSelect" class="form-control" required>
                        <option value="">Select rating</option>
                        <option value="5">★★★★★ Excellent</option>
                        <option value="4">★★★★☆ Good</option>
                        <option value="3">★★★☆☆ Average</option>
                        <option value="2">★★☆☆☆ Poor</option>
                        <option value="1">★☆☆☆☆ Terrible</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Comment:</label>
                    <textarea name="comment" id="commentArea" class="form-control" rows="4" placeholder="Share your experience with this plant..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit Review</button>
            </form>
            <div id="reviewAlert"></div>
        </div>
        <?php else: ?>
        <p class="login-prompt"><a href="/login.php">Login</a> to write a review.</p>
        <?php endif; ?>
    </div>
</div>

<script>
const plantId = <?= $id ?>;
const maxStock = <?= $plant['stock'] ?>;

function changeQty(delta) {
    const input = document.getElementById('quantity');
    if (!input) return;
    let val = parseInt(input.value) + delta;
    val = Math.max(1, Math.min(maxStock, val));
    input.value = val;
}

function showAlert(id, msg, type = 'success') {
    const el = document.getElementById(id);
    if (!el) return;
    el.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
    setTimeout(() => { el.innerHTML = ''; }, 3500);
}

function renderStars(avg) {
    const full = Math.round(avg);
    let s = '';
    for (let i = 1; i <= 5; i++) s += i <= full ? '★' : '☆';
    return s;
}

// Load reviews
function loadReviews() {
    fetch(`/api/reviews.php?plant_id=${plantId}`)
        .then(r => r.json())
        .then(reviews => {
            const list = document.getElementById('reviewsList');
            if (!reviews.length) {
                list.innerHTML = '<p class="no-reviews">No reviews yet. Be the first!</p>';
                return;
            }
            list.innerHTML = reviews.map(r => `
                <div class="review-item">
                    <div class="review-header">
                        <strong>${escHtml(r.username)}</strong>
                        <span class="stars">${renderStars(r.rating)}</span>
                        <span class="review-date">${new Date(r.created_at).toLocaleDateString()}</span>
                    </div>
                    <p class="review-comment">${escHtml(r.comment || '')}</p>
                </div>
            `).join('');
        });
}

function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str || ''));
    return d.innerHTML;
}

// Add to cart form
const cartForm = document.getElementById('addToCartForm');
if (cartForm) {
    cartForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const qty = document.getElementById('quantity').value;
        fetch('/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add&plant_id=${plantId}&quantity=${qty}`
        })
        .then(r => r.json())
        .then(data => {
            showAlert('cartAlert', data.success ? '✅ Added to cart!' : (data.message || 'Failed.'), data.success ? 'success' : 'error');
        })
        .catch(() => showAlert('cartAlert', 'Something went wrong.', 'error'));
    });
}

// Review form
const reviewForm = document.getElementById('reviewForm');
if (reviewForm) {
    reviewForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const rating  = document.getElementById('ratingSelect').value;
        const comment = document.getElementById('commentArea').value;
        if (!rating) { showAlert('reviewAlert', 'Please select a rating.', 'error'); return; }
        fetch('/api/reviews.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add&plant_id=${plantId}&rating=${rating}&comment=${encodeURIComponent(comment)}`
        })
        .then(r => r.json())
        .then(data => {
            showAlert('reviewAlert', data.message, data.success ? 'success' : 'error');
            if (data.success) {
                reviewForm.reset();
                loadReviews();
            }
        })
        .catch(() => showAlert('reviewAlert', 'Something went wrong.', 'error'));
    });
}

loadReviews();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
