// src/js/app.js

/*
  Full app frontend logic: load products, login, cart, checkout, orders.
*/
const apiBase = 'api';

function escapeHtml(text) {
  if (!text) return '';
  return String(text)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function escapeAttr(text) {
  if (!text) return '';
  return String(text).replace(/"/g, '&quot;');
}

// Get product image with fallback placeholder
function getProductImage(imageUrl, productName = 'Produk') {
  if (imageUrl && imageUrl.trim() !== '') {
    return imageUrl;
  }
  return generatePlaceholder(productName);
}

// Generate placeholder image when image fails to load
function generatePlaceholder(productName = 'Produk') {
  const canvas = document.createElement('canvas');
  canvas.width = 300;
  canvas.height = 300;
  const ctx = canvas.getContext('2d');

  // Gradient background
  const gradient = ctx.createLinearGradient(0, 0, 300, 300);
  gradient.addColorStop(0, '#667eea');
  gradient.addColorStop(1, '#764ba2');
  ctx.fillStyle = gradient;
  ctx.fillRect(0, 0, 300, 300);

  // Text
  ctx.fillStyle = '#ffffff';
  ctx.font = 'bold 24px Arial';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  const displayName = productName.length > 20 ? productName.substring(0, 17) + '...' : productName;
  ctx.fillText(displayName, 150, 150);

  return canvas.toDataURL('image/png');
}

// Mobile sidebar toggle
function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.mobile-overlay');
  if (sidebar) {
    sidebar.classList.toggle('show');
  }
  if (overlay) {
    overlay.classList.toggle('show');
  }
}

// Close sidebar when clicking on a link (mobile)
document.addEventListener('click', (e) => {
  if (e.target.closest('.sidebar .nav-link') && window.innerWidth < 768) {
    toggleSidebar();
  }
});

function showToast(msg) {
  // Simple toast implementation
  let toast = document.getElementById('toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'toast';
    toast.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#333;color:#fff;padding:10px 20px;border-radius:5px;z-index:1000;display:none;';
    document.body.appendChild(toast);
  }
  toast.innerText = msg;
  toast.style.display = 'block';
  setTimeout(() => { toast.style.display = 'none'; }, 3000);
}

// Download Invoice PDF
function downloadInvoice(orderId) {
  const user = getUser();
  if (!user) {
    alert('Silakan login terlebih dahulu');
    return;
  }
  window.open(`${apiBase}/invoice.php?order_id=${orderId}&user_id=${user.id}`, '_blank');
}

function fetchJson(url, opts = {}) {
  // Check for file protocol
  if (window.location.protocol === 'file:') {
    alert('Error: Aplikasi ini harus dijalankan melalui server (localhost), bukan dibuka langsung sebagai file.');
    return Promise.reject('Running on file protocol');
  }

  console.log('Fetching:', url);
  if (!opts.headers) opts.headers = {};
  if (opts.body && typeof opts.body === 'object' && !(opts.body instanceof FormData)) {
    opts.body = JSON.stringify(opts.body);
    opts.headers['Content-Type'] = 'application/json';
  }
  return fetch(url, opts)
    .then(r => {
      return r.text().then(text => {
        try {
          const json = JSON.parse(text);
          // If valid JSON, return it regardless of status code
          // This allows backend 400/409/500 responses with { error: "msg" } to be handled by the caller
          return json;
        } catch (e) {
          // If not valid JSON and status is not OK, throw error with status
          if (!r.ok) {
            throw new Error('Network response was not ok: ' + r.status + ' - ' + text.substring(0, 100));
          }
          // If status OK but invalid JSON
          console.error('JSON Parse Error:', e, 'Response text:', text);
          throw new Error('Invalid JSON response');
        }
      });
    });
}

document.addEventListener('DOMContentLoaded', () => {
  bindUI();

  // Load products if on catalog or home
  loadProducts();

  const user = getUser();
  showUserState(); // Always call this to set initial state (hide/show cart)

  if (user) {
    loadOrders();
    // Load server cart
    loadCartFromServer();
    // Start notification polling for logged in users
    startNotificationPolling();
  }
});

function bindUI() {
  const loginForm = document.getElementById('loginForm');
  if (loginForm) loginForm.addEventListener('submit', onLogin);

  const cartBtn = document.getElementById('cartButton');
  if (cartBtn) {
    cartBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const cartDrawer = document.getElementById('cartDrawer');
      if (cartDrawer) {
        const offcanvasInstance = bootstrap.Offcanvas.getOrCreateInstance(cartDrawer);
        // Toggle - if visible hide, if hidden show
        if (cartDrawer.classList.contains('show')) {
          offcanvasInstance.hide();
        } else {
          showCart();
        }
      }
    });
  }

  // delegate add-to-cart - show quick add modal
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.add-to-cart');
    if (!btn) return;

    const productId = btn.dataset.id;
    showQuickAddModal(productId);
  });
}

// Quick Add to Cart Modal state
let quickAddProduct = null;
let quickAddVariants = [];
let quickAddSelectedVariant = null;

async function showQuickAddModal(productId) {
  const user = getUser();
  if (!user) {
    alert('Silakan login terlebih dahulu untuk berbelanja.');
    const modal = new bootstrap.Modal(document.getElementById('loginModal'));
    modal.show();
    return;
  }

  if (user.role === 'admin') {
    alert('Admin tidak dapat melakukan pemesanan.');
    return;
  }

  // Fetch product details
  try {
    const product = await fetchJson(`${apiBase}/get_products.php?id=${productId}`);
    quickAddProduct = product;

    // Populate modal
    document.getElementById('quickAddProductName').textContent = product.name;
    document.getElementById('quickAddProductImage').src = getProductImage(product.image_url, product.name);
    document.getElementById('quickAddProductPrice').textContent = 'Rp ' + Number(product.price).toLocaleString();
    document.getElementById('quickAddQty').value = 1;

    // Reset variant selection
    quickAddSelectedVariant = null;
    quickAddVariants = [];

    // Reset variant info display
    document.getElementById('quickAddVariantInfo').style.display = 'none';
    document.getElementById('quickAddSizeOptions').innerHTML = '';
    document.getElementById('quickAddColorOptions').innerHTML = '';

    // Fetch variants
    const variantRes = await fetchJson(`${apiBase}/variants.php?product_id=${productId}`);
    if (variantRes.success && variantRes.variants && variantRes.variants.length > 0) {
      quickAddVariants = variantRes.variants;
      renderQuickAddVariants();
      document.getElementById('quickAddVariantSection').style.display = 'block';
    } else {
      document.getElementById('quickAddVariantSection').style.display = 'none';
    }

    // Check if product is already in wishlist
    const wishlistBtn = document.getElementById('quickAddWishlistBtn');
    wishlistBtn.classList.remove('btn-danger');
    wishlistBtn.classList.add('btn-outline-danger');
    wishlistBtn.innerHTML = '<i class="bi bi-heart"></i>';

    try {
      const wishlistRes = await fetchJson(`${apiBase}/wishlist.php?user_id=${user.id}`);
      if (wishlistRes.success && wishlistRes.items) {
        const isInWishlist = wishlistRes.items.some(item => item.product_id == productId);
        if (isInWishlist) {
          wishlistBtn.classList.remove('btn-outline-danger');
          wishlistBtn.classList.add('btn-danger');
          wishlistBtn.innerHTML = '<i class="bi bi-heart-fill"></i>';
        }
      }
    } catch (e) {
      console.log('Could not check wishlist status');
    }

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('quickAddCartModal'));
    modal.show();
  } catch (err) {
    console.error(err);
    alert('Gagal memuat detail produk');
  }
}

function renderQuickAddVariants() {
  const sizeSection = document.getElementById('quickAddSizeSection');
  const colorSection = document.getElementById('quickAddColorSection');
  const sizeOptions = document.getElementById('quickAddSizeOptions');
  const colorOptions = document.getElementById('quickAddColorOptions');

  // Get unique sizes and colors
  const sizes = [...new Set(quickAddVariants.filter(v => v.size_display).map(v => v.size_display))];
  const colors = [...new Set(quickAddVariants.filter(v => v.color_display).map(v => v.color_display))];

  // Render sizes
  if (sizes.length > 0) {
    sizeSection.style.display = 'block';
    sizeOptions.innerHTML = sizes.map(s => `
      <button type="button" class="btn btn-outline-secondary quick-size-btn" data-size="${escapeAttr(s)}">${escapeHtml(s)}</button>
    `).join('');
  } else {
    sizeSection.style.display = 'none';
  }

  // Render colors  
  if (colors.length > 0) {
    colorSection.style.display = 'block';
    colorOptions.innerHTML = colors.map(c => `
      <button type="button" class="btn btn-outline-secondary quick-color-btn" data-color="${escapeAttr(c)}">${escapeHtml(c)}</button>
    `).join('');
  } else {
    colorSection.style.display = 'none';
  }

  // Bind click handlers
  sizeOptions.querySelectorAll('.quick-size-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      sizeOptions.querySelectorAll('.quick-size-btn').forEach(b => {
        b.classList.remove('active', 'btn-primary');
        b.classList.add('btn-outline-secondary');
      });
      btn.classList.add('active', 'btn-primary');
      btn.classList.remove('btn-outline-secondary');
      updateQuickAddVariant();
    });
  });

  colorOptions.querySelectorAll('.quick-color-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      colorOptions.querySelectorAll('.quick-color-btn').forEach(b => {
        b.classList.remove('active', 'btn-primary');
        b.classList.add('btn-outline-secondary');
      });
      btn.classList.add('active', 'btn-primary');
      btn.classList.remove('btn-outline-secondary');
      updateQuickAddVariant();
    });
  });
}

function updateQuickAddVariant() {
  const selectedSize = document.querySelector('.quick-size-btn.active')?.dataset.size || null;
  const selectedColor = document.querySelector('.quick-color-btn.active')?.dataset.color || null;

  // Check what variant types exist
  const hasSizes = quickAddVariants.some(v => v.size_display);
  const hasColors = quickAddVariants.some(v => v.color_display);

  const infoEl = document.getElementById('quickAddVariantInfo');
  const infoText = document.getElementById('quickAddVariantInfoText');

  // Only find variant if all required selections are made
  const sizeComplete = !hasSizes || selectedSize;
  const colorComplete = !hasColors || selectedColor;

  if (sizeComplete && colorComplete) {
    // Find matching variant with exact match
    quickAddSelectedVariant = quickAddVariants.find(v => {
      const sizeMatch = !hasSizes || v.size_display === selectedSize;
      const colorMatch = !hasColors || v.color_display === selectedColor;
      return sizeMatch && colorMatch;
    });

    if (quickAddSelectedVariant) {
      infoEl.style.display = 'block';
      infoText.textContent = `${quickAddSelectedVariant.size_display || ''} ${quickAddSelectedVariant.color_display || ''} - Stok: ${quickAddSelectedVariant.stock}`;
    } else {
      infoEl.style.display = 'block';
      infoText.textContent = 'Kombinasi tidak tersedia';
    }
  } else {
    // Not all selections made yet
    quickAddSelectedVariant = null;
    infoEl.style.display = 'none';
  }
}

// Initialize quick add modal handlers
document.addEventListener('DOMContentLoaded', () => {
  // Qty buttons
  document.getElementById('quickAddQtyMinus')?.addEventListener('click', () => {
    const input = document.getElementById('quickAddQty');
    if (parseInt(input.value) > 1) input.value = parseInt(input.value) - 1;
  });

  document.getElementById('quickAddQtyPlus')?.addEventListener('click', () => {
    const input = document.getElementById('quickAddQty');
    input.value = parseInt(input.value) + 1;
  });

  // Confirm add to cart
  document.getElementById('quickAddConfirmBtn')?.addEventListener('click', () => {
    if (!quickAddProduct) return;

    // Check if variants exist
    if (quickAddVariants.length > 0) {
      // Get available variant types
      const hasSizes = quickAddVariants.some(v => v.size_display);
      const hasColors = quickAddVariants.some(v => v.color_display);
      const selectedSize = document.querySelector('.quick-size-btn.active')?.dataset.size || null;
      const selectedColor = document.querySelector('.quick-color-btn.active')?.dataset.color || null;

      // Check if required selections are made
      if (hasSizes && !selectedSize) {
        alert('Pilih ukuran terlebih dahulu');
        return;
      }
      if (hasColors && !selectedColor) {
        alert('Pilih warna terlebih dahulu');
        return;
      }

      // Check if valid variant combination exists
      if (!quickAddSelectedVariant) {
        alert('Kombinasi ukuran dan warna tidak tersedia');
        return;
      }

      // Check if variant has stock
      if (quickAddSelectedVariant.stock <= 0) {
        alert('Stok untuk varian ini habis');
        return;
      }
    }

    const qty = parseInt(document.getElementById('quickAddQty').value) || 1;

    // Check stock for non-variant products
    if (quickAddVariants.length === 0 && quickAddProduct.stock <= 0) {
      alert('Stok produk ini habis');
      return;
    }

    // If has variant, check qty against variant stock
    if (quickAddSelectedVariant && qty > quickAddSelectedVariant.stock) {
      alert(`Stok tidak mencukupi. Tersedia: ${quickAddSelectedVariant.stock}`);
      return;
    }

    const cartItem = {
      id: quickAddProduct.id,
      name: quickAddProduct.name,
      price: quickAddProduct.price,
      quantity: qty
    };

    if (quickAddSelectedVariant) {
      cartItem.variant_id = quickAddSelectedVariant.id;
    }

    addToCart(cartItem);

    // Close modal
    const modalEl = document.getElementById('quickAddCartModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal?.hide();
  });

  // Wishlist button handler - toggle add/remove
  document.getElementById('quickAddWishlistBtn')?.addEventListener('click', async () => {
    if (!quickAddProduct) return;

    const user = getUser();
    if (!user) {
      showToast('Silakan login terlebih dahulu');
      return;
    }

    const btn = document.getElementById('quickAddWishlistBtn');
    const isInWishlist = btn.classList.contains('btn-danger');

    try {
      if (isInWishlist) {
        // Remove from wishlist
        const res = await fetchJson(`${apiBase}/wishlist.php?user_id=${user.id}&product_id=${quickAddProduct.id}`, {
          method: 'DELETE'
        });

        if (res.success) {
          showToast('Dihapus dari wishlist');
          btn.classList.remove('btn-danger');
          btn.classList.add('btn-outline-danger');
          btn.innerHTML = '<i class="bi bi-heart"></i>';

          // Reload wishlist page if on wishlist page
          if (typeof loadWishlist === 'function') {
            loadWishlist();
          }
        } else {
          showToast(res.error || 'Gagal menghapus dari wishlist');
        }
      } else {
        // Add to wishlist
        const res = await fetchJson(`${apiBase}/wishlist.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            user_id: user.id,
            product_id: quickAddProduct.id
          })
        });

        if (res.success) {
          showToast('Ditambahkan ke wishlist');
          btn.classList.remove('btn-outline-danger');
          btn.classList.add('btn-danger');
          btn.innerHTML = '<i class="bi bi-heart-fill"></i>';
        } else {
          showToast(res.error || 'Gagal menambahkan ke wishlist');
        }
      }
    } catch (err) {
      console.error(err);
      showToast('Terjadi kesalahan');
    }
  });
});

// products
let allProducts = []; // Cache for client-side filtering

function loadProducts(sort = 'newest', limit = 0, targetGridId = 'product-grid') {
  const grid = document.getElementById(targetGridId);
  if (!grid) return;

  // Show loading
  grid.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';

  const urlParams = new URLSearchParams(window.location.search);
  const sortParam = urlParams.get('sort') || sort;

  // Special mode: load products per category (for homepage)
  if (sortParam === 'per_category' && limit > 0) {
    fetchJson(`${apiBase}/get_products.php?sort=newest`)
      .then(data => {
        allProducts = data;

        // Group products by category
        const byCategory = {};
        data.forEach(p => {
          const catId = p.category_id || 'none';
          if (!byCategory[catId]) byCategory[catId] = [];
          byCategory[catId].push(p);
        });

        // Get categories and calculate products per category
        const categories = Object.keys(byCategory);
        const productsPerCategory = Math.ceil(limit / categories.length);

        // Take products from each category
        let selectedProducts = [];
        categories.forEach(catId => {
          const catProducts = byCategory[catId].slice(0, productsPerCategory);
          selectedProducts = selectedProducts.concat(catProducts);
        });

        // Limit to requested amount
        selectedProducts = selectedProducts.slice(0, limit);

        renderProductGrid(grid, selectedProducts);
      })
      .catch(err => {
        console.error(err);
        grid.innerHTML = '<div class="col-12 text-center text-danger">Gagal memuat produk.</div>';
      });
    return;
  }

  fetchJson(`${apiBase}/get_products.php?sort=${sortParam}&limit=${limit}`)
    .then(data => {
      allProducts = data; // Cache
      renderProductGrid(grid, data);

      // Initialize search if on catalog page
      const searchInput = document.getElementById('searchInput');
      if (searchInput) {
        searchInput.addEventListener('input', (e) => {
          const term = e.target.value.toLowerCase();
          const filtered = allProducts.filter(p => p.name.toLowerCase().includes(term));
          renderProductGrid(grid, filtered);
        });
      }

      // Initialize sort
      const sortSelect = document.getElementById('sortSelect');
      if (sortSelect) {
        sortSelect.value = sortParam;
        sortSelect.addEventListener('change', (e) => {
          // Reload with new sort (server side)
          window.location.href = `catalog.html?sort=${e.target.value}`;
        });
      }
    })
    .catch(err => {
      console.error(err);
      grid.innerHTML = '<div class="col-12 text-center text-danger">Gagal memuat produk.</div>';
    });
}

function renderProductGrid(container, products) {
  if (!Array.isArray(products) || !products.length) {
    container.innerHTML = '<div class="col-12 text-center text-muted">Tidak ada produk.</div>';
    return;
  }

  const user = getUser();
  const isAdmin = user && user.role === 'admin';
  const isAdminGrid = container.id === 'admin-product-grid';

  let html = '';
  // Only show "Tambah Produk" for catalog page (product-grid), not admin dashboard
  if (isAdmin && container.id === 'product-grid') {
    html += `
      <div class="col-12 mb-4 text-end">
          <button class="btn btn-success" onclick="showAddProductModal()">
              <i class="bi bi-plus-circle"></i> Tambah Produk
          </button>
      </div>`;
  }

  html += products.map(p => {
    // Use col-md-3 for all grids (4 per row on desktop)
    const colClass = 'col-md-3 mb-4';
    return `
    <div class="${colClass}">
      <div class="card product-card h-100">
        <a href="product.html?id=${p.id}" style="text-decoration:none;color:inherit">
            <img src="${getProductImage(p.image_url, p.name)}" class="card-img-top" alt="${escapeHtml(p.name)}"
                 onerror="this.onerror=null; this.src=generatePlaceholder('${escapeAttr(p.name)}');">
            <div class="card-body">
            <h5 class="card-title${isAdminGrid ? ' text-truncate' : ''}">${escapeHtml(p.name)}</h5>
            <p class="text-info fw-bold mb-1">Rp ${Number(p.price).toLocaleString()}</p>
            <small class="d-block mb-2 text-muted">Stok: ${p.stock}</small>
            </div>
        </a>
        <div class="p-3 pt-0">
            ${isAdmin ? `
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-warning w-50" onclick="editProduct(${p.id})">Edit</button>
                <button class="btn btn-sm btn-danger w-50" onclick="deleteProduct(${p.id})">Hapus</button>
            </div>
            ` : `
            <button class="btn btn-sm btn-primary add-to-cart w-100"
                data-id="${p.id}" data-name="${escapeAttr(p.name)}" data-price="${p.price}" data-image="${escapeAttr(p.image_url)}" data-has-variants="${p.has_variants ? 'true' : 'false'}">
                <i class="bi bi-cart-plus me-1"></i> Tambah
            </button>
            `}
        </div>
      </div>
    </div>
  `}).join('');

  container.innerHTML = html;
}

function showAddProductModal() {
  const modal = new bootstrap.Modal(document.getElementById('addProductModal'));
  modal.show();
}

let currentEditProductId = null;

function editProduct(id) {
  currentEditProductId = id;
  fetchJson(`${apiBase}/get_products.php?id=${id}`).then(p => {
    const f = document.getElementById('editProductForm');
    f.id.value = p.id;
    f.name.value = p.name;
    f.description.value = p.description || '';
    f.price.value = p.price;
    f.stock.value = p.stock;
    f.image_url.value = p.image_url || '';

    // Also populate base stock input for non-variant products
    const baseStockInput = document.getElementById('editBaseStock');
    if (baseStockInput) {
      baseStockInput.value = p.stock;
    }

    // Load variants for this product
    loadEditVariants(id);

    const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
    modal.show();
  });
}

function loadEditVariants(productId) {
  const listEl = document.getElementById('editVariantList');
  const countEl = document.getElementById('editVariantCount');
  const noVariantStockEl = document.getElementById('editNoVariantStock');
  const baseStockInput = document.getElementById('editBaseStock');

  if (!listEl) return;

  listEl.innerHTML = '<p class="text-muted small text-center">Loading...</p>';

  fetchJson(`${apiBase}/variants.php?product_id=${productId}`)
    .then(res => {
      if (!res.success || !res.variants || res.variants.length === 0) {
        listEl.innerHTML = '<p class="text-muted small text-center">Tidak ada varian</p>';
        countEl.textContent = '0';

        // Show stock input for non-variant product
        if (noVariantStockEl) {
          noVariantStockEl.style.display = 'block';
        }
        return;
      }

      // Hide stock input since product has variants
      if (noVariantStockEl) {
        noVariantStockEl.style.display = 'none';
      }

      countEl.textContent = res.variants.length;
      listEl.innerHTML = res.variants.map(v => `
        <div class="d-flex align-items-center justify-content-between mb-2 p-2 bg-secondary bg-opacity-25 rounded" id="variant-row-${v.id}">
          <div>
            <small class="fw-bold">${escapeHtml(v.size_display || '-')} / ${escapeHtml(v.color_display || '-')}</small>
          </div>
          <div class="d-flex align-items-center gap-2">
            <input type="number" class="form-control form-control-sm" style="width:60px" 
                   value="${v.stock}" min="0" onchange="updateVariantStock(${v.id}, this.value)">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteVariant(${v.id})">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
      `).join('');
    })
    .catch(err => {
      listEl.innerHTML = '<p class="text-danger small text-center">Error loading variants</p>';
    });
}

function updateVariantStock(variantId, newStock) {
  fetchJson(`${apiBase}/variants.php`, {
    method: 'PUT',
    body: { id: variantId, stock: parseInt(newStock) }
  }).then(res => {
    if (res.success) {
      showToast('Stok varian diperbarui');
    } else {
      alert('Gagal update stok: ' + (res.error || 'Unknown error'));
    }
  });
}

function deleteVariant(variantId) {
  if (!confirm('Hapus varian ini?')) return;

  fetchJson(`${apiBase}/variants.php?id=${variantId}`, { method: 'DELETE' })
    .then(res => {
      if (res.success) {
        document.getElementById(`variant-row-${variantId}`)?.remove();
        showToast('Varian dihapus');
        // Update count
        const countEl = document.getElementById('editVariantCount');
        if (countEl) countEl.textContent = parseInt(countEl.textContent) - 1;
      } else {
        alert('Gagal hapus varian: ' + (res.error || 'Unknown error'));
      }
    });
}

async function addNewVariantsFromEdit() {
  if (!currentEditProductId) return;

  const sizesText = document.getElementById('editNewSizes')?.value || '';
  const colorsText = document.getElementById('editNewColors')?.value || '';
  // Stock ini adalah stok PER VARIAN yang akan dibuat
  const stockPerVariant = parseInt(document.getElementById('editNewStock')?.value || 10);

  if (!sizesText && !colorsText) {
    alert('Masukkan ukuran atau warna');
    return;
  }

  const sizes = sizesText.split(',').map(s => s.trim()).filter(s => s);
  const colors = colorsText.split(',').map(c => c.trim()).filter(c => c);

  // If only sizes or only colors, create simple variants
  if (sizes.length === 0) sizes.push('');
  if (colors.length === 0) colors.push('');

  let created = 0;
  for (const size of sizes) {
    for (const color of colors) {
      try {
        const res = await fetchJson(`${apiBase}/variants.php`, {
          method: 'POST',
          body: {
            product_id: currentEditProductId,
            size_text: size,
            color_text: color,
            stock: stockPerVariant
          }
        });
        if (res.success) created++;
      } catch (e) {
        console.error('Error creating variant:', e);
      }
    }
  }

  if (created > 0) {
    showToast(`${created} varian ditambahkan (stok ${stockPerVariant}/varian)`);
    loadEditVariants(currentEditProductId);
    // Clear inputs
    document.getElementById('editNewSizes').value = '';
    document.getElementById('editNewColors').value = '';
  } else {
    alert('Tidak ada varian baru yang ditambahkan');
  }
}


function deleteProduct(id) {
  if (confirm('Yakin ingin menghapus produk ini?')) {
    fetchJson(`${apiBase}/admin_delete_product.php?id=${id}`, { method: 'POST' })
      .then(res => {
        if (res.success) {
          showToast('Produk dihapus');
          // Reload page to reset layout properly
          setTimeout(() => location.reload(), 500);
        } else {
          alert('Gagal menghapus: ' + res.message);
        }
      });
  }
}

// Variant options cache
let variantOptions = { sizes: [], colors: [] };

// Load variant options for admin forms
function loadVariantOptions() {
  fetchJson(`${apiBase}/variants.php?options=true`)
    .then(res => {
      if (res.success) {
        variantOptions = res;
        renderVariantCheckboxes();
      }
    });
}

function renderVariantCheckboxes() {
  const sizeContainer = document.getElementById('sizeCheckboxes');
  const colorContainer = document.getElementById('colorCheckboxes');

  if (sizeContainer && variantOptions.sizes) {
    sizeContainer.innerHTML = variantOptions.sizes.map(s => `
      <div class="form-check">
        <input class="form-check-input size-check" type="checkbox" value="${s.id}" id="size_${s.id}">
        <label class="form-check-label" for="size_${s.id}">${escapeHtml(s.display_value || s.value)}</label>
      </div>
    `).join('');
  }

  if (colorContainer && variantOptions.colors) {
    colorContainer.innerHTML = variantOptions.colors.map(c => `
      <div class="form-check">
        <input class="form-check-input color-check" type="checkbox" value="${c.id}" id="color_${c.id}">
        <label class="form-check-label" for="color_${c.id}">${escapeHtml(c.display_value || c.value)}</label>
      </div>
    `).join('');
  }
}

function toggleVariantFields() {
  const checkbox = document.getElementById('hasVariants');
  const variantFields = document.getElementById('variantFields');
  const noVariantStock = document.getElementById('noVariantStock');

  if (checkbox && variantFields) {
    const hasVariants = checkbox.checked;
    variantFields.style.display = hasVariants ? 'block' : 'none';

    // Toggle non-variant stock field
    if (noVariantStock) {
      noVariantStock.style.display = hasVariants ? 'none' : 'block';
    }

    if (hasVariants && variantOptions.sizes.length === 0) {
      loadVariantOptions();
    }
  }
}

// Create variants for a product
async function createProductVariants(productId, selectedSizes, selectedColors, stock) {
  const promises = [];

  // If both sizes and colors selected, create all combinations
  if (selectedSizes.length > 0 && selectedColors.length > 0) {
    for (const sizeId of selectedSizes) {
      for (const colorId of selectedColors) {
        promises.push(fetchJson(`${apiBase}/variants.php`, {
          method: 'POST',
          body: { product_id: productId, size_option_id: sizeId, color_option_id: colorId, stock: stock }
        }));
      }
    }
  }
  // Only sizes
  else if (selectedSizes.length > 0) {
    for (const sizeId of selectedSizes) {
      promises.push(fetchJson(`${apiBase}/variants.php`, {
        method: 'POST',
        body: { product_id: productId, size_option_id: sizeId, color_option_id: null, stock: stock }
      }));
    }
  }
  // Only colors
  else if (selectedColors.length > 0) {
    for (const colorId of selectedColors) {
      promises.push(fetchJson(`${apiBase}/variants.php`, {
        method: 'POST',
        body: { product_id: productId, size_option_id: null, color_option_id: colorId, stock: stock }
      }));
    }
  }

  return Promise.all(promises);
}

// Create variants from comma-separated text (for simplified admin input)
// Stock parameter is the stock PER VARIANT (not total to be divided)
async function createProductVariantsFromText(productId, sizes, colors, stockPerVariant) {
  const promises = [];

  // If both sizes and colors provided, create all combinations
  if (sizes.length > 0 && colors.length > 0) {
    for (const size of sizes) {
      for (const color of colors) {
        promises.push(fetchJson(`${apiBase}/variants.php`, {
          method: 'POST',
          body: {
            product_id: productId,
            size_text: size,
            color_text: color,
            stock: stockPerVariant
          }
        }));
      }
    }
  }
  // Only sizes
  else if (sizes.length > 0) {
    for (const size of sizes) {
      promises.push(fetchJson(`${apiBase}/variants.php`, {
        method: 'POST',
        body: { product_id: productId, size_text: size, stock: stockPerVariant }
      }));
    }
  }
  // Only colors
  else if (colors.length > 0) {
    for (const color of colors) {
      promises.push(fetchJson(`${apiBase}/variants.php`, {
        method: 'POST',
        body: { product_id: productId, color_text: color, stock: stockPerVariant }
      }));
    }
  }

  return Promise.all(promises);
}

// Bind Admin Forms
document.addEventListener('DOMContentLoaded', () => {
  const addForm = document.getElementById('addProductForm');
  if (addForm) {
    // Load variant options when modal opens
    const addModal = document.getElementById('addProductModal');
    if (addModal) {
      addModal.addEventListener('shown.bs.modal', () => {
        if (variantOptions.sizes.length === 0) loadVariantOptions();
      });
    }

    addForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(e.target);

      // Check if has variants
      const hasVariants = document.getElementById('hasVariants')?.checked;

      try {
        const res = await fetchJson(`${apiBase}/admin_add_product.php`, { method: 'POST', body: formData });

        if (res.success) {
          // If has variants, create them from comma-separated text
          if (hasVariants && res.id) {
            const sizesText = document.getElementById('variantSizes')?.value || '';
            const colorsText = document.getElementById('variantColors')?.value || '';
            const stock = parseInt(document.getElementById('variantStock')?.value || 10);

            // Parse comma-separated values
            const sizes = sizesText.split(',').map(s => s.trim()).filter(s => s);
            const colors = colorsText.split(',').map(c => c.trim()).filter(c => c);

            // Create variants for each combination
            await createProductVariantsFromText(res.id, sizes, colors, stock);
          }

          showToast('Produk berhasil ditambahkan!');
          location.reload();
        } else {
          alert('Gagal menambah produk: ' + (res.error || 'Unknown error'));
        }
      } catch (err) {
        console.error(err);
        alert('Terjadi kesalahan');
      }
    });
  }

  const editForm = document.getElementById('editProductForm');
  if (editForm) {
    editForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const data = Object.fromEntries(new FormData(e.target));

      // If product has no variants, use the editBaseStock value
      const noVariantStockEl = document.getElementById('editNoVariantStock');
      if (noVariantStockEl && noVariantStockEl.style.display !== 'none') {
        const baseStockInput = document.getElementById('editBaseStock');
        if (baseStockInput) {
          data.stock = baseStockInput.value;
        }
      }

      fetchJson(`${apiBase}/admin_update_product.php`, { method: 'POST', body: data })
        .then(res => {
          if (res.success) {
            showToast('Produk berhasil diupdate!');
            location.reload();
          } else alert('Gagal update produk');
        });
    });
  }
});

// auth
function onLogin(e) {
  e.preventDefault();
  const f = e.target;
  const email = f.email.value.trim(), password = f.password.value.trim();
  if (!email || !password) return alert('Email & password diperlukan');
  fetchJson(`${apiBase}/login.php`, { method: 'POST', body: { email, password } })
    .then(resp => {
      if (resp.success) {
        localStorage.setItem('user', JSON.stringify(resp.user));
        const modalEl = document.getElementById('loginModal');
        if (modalEl) {
          const m = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
          m.hide();
        }
        showUserState();
        loadOrders();
        loadCartFromServer(); // Sync cart

        if (resp.user.role === 'admin') {
          alert('Login berhasil sebagai Admin');
          window.location.reload(); // Reload to update sidebar
        } else {
          alert('Login berhasil');
          window.location.reload(); // Reload to update sidebar
        }
      } else alert(resp.message || 'Login gagal');
    }).catch(err => { console.error(err); alert('Login error'); });
}

function getUser() { try { return JSON.parse(localStorage.getItem('user') || 'null'); } catch { return null; } }
function logout() {
  localStorage.removeItem('user');
  localStorage.removeItem('cart'); // Clear local cart cache
  window.location.href = 'index.html';
}

function showUserState() {
  const user = getUser();
  const loginItem = document.getElementById('loginNavItem');
  const badge = document.getElementById('userBadge');
  const fab = document.getElementById('adminFab');
  const sidebar = document.querySelector('.sidebar');

  if (!user) {
    if (loginItem) loginItem.style.display = 'block';
    if (badge) badge.innerHTML = '';
    if (fab) fab.style.display = 'none';
    // Hide cart for guests
    const cartBtn = document.getElementById('cartButton');
    if (cartBtn) cartBtn.style.display = 'none';
    return;
  }

  if (loginItem) loginItem.style.display = 'none';

  // Show cart only for normal users
  const cartBtn = document.getElementById('cartButton');
  if (cartBtn) {
    cartBtn.style.display = (user.role === 'admin') ? 'none' : 'block';
  }

  // Show FAB only for admin
  if (fab) {
    fab.style.display = (user.role === 'admin') ? 'block' : 'none';
  }

  const currentPage = window.location.pathname.split('/').pop() || 'index.html';

  // Transform sidebar for admin
  if (user.role === 'admin' && sidebar) {
    sidebar.innerHTML = `
      <div class="logo" style="display: flex; align-items: center; gap: 10px;">
        <img src="assets/images/logo.png" alt="Logo" style="width: 40px; height: 40px;">
        <h2 style="margin: 0; font-size: 1.2rem;">my<span style="color:#9b59b6">ITS</span><br>Admin</h2>
      </div>
      <ul class="nav flex-column mb-auto nav-pills">
        <li class="nav-item">
          <a class="nav-link ${currentPage === 'index.html' ? 'active' : ''}" href="index.html"><i class="bi bi-house-door"></i> Toko</a>
        </li>
        <li class="nav-item">
          <a class="nav-link ${currentPage === 'catalog.html' ? 'active' : ''}" href="catalog.html"><i class="bi bi-box"></i> Katalog Produk</a>
        </li>
        <li class="nav-item">
          <a class="nav-link ${currentPage === 'admin.html' ? 'active' : ''}" href="admin.html"><i class="bi bi-graph-up"></i> Sales Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link ${currentPage === 'orders.html' ? 'active' : ''}" href="orders.html"><i class="bi bi-clipboard-check"></i> Kelola Pesanan</a>
        </li>
        <li class="nav-item">
          <a class="nav-link ${currentPage === 'coupons-admin.html' ? 'active' : ''}" href="coupons-admin.html"><i class="bi bi-ticket-perforated"></i> Kelola Kupon</a>
        </li>
      </ul>
      <div class="mt-auto p-3">
        <button id="logoutBtn" class="btn btn-outline-danger w-100">Logout</button>
      </div>
    `;
    document.getElementById('logoutBtn').addEventListener('click', logout);
    return;
  }

  if (sidebar && user.role !== 'admin') {
    sidebar.innerHTML = `
      <div class="logo" style="display: flex; align-items: center; gap: 10px;">
        <img src="assets/images/logo.png" alt="Logo" style="width: 40px; height: 40px;">
        <h2 style="margin: 0; font-size: 1.2rem;">my<span style="color:#9b59b6">ITS</span><br>Merch</h2>
      </div>
      <ul class="nav flex-column mb-auto nav-pills">
        <li class="nav-item">
          <a class="nav-link ${currentPage === 'index.html' ? 'active' : ''}" href="index.html"><i class="bi bi-house-door"></i> Beranda</a>
        </li>
        <li class="nav-item">
          <a class="nav-link ${currentPage === 'catalog.html' ? 'active' : ''}" href="catalog.html"><i class="bi bi-box"></i> Katalog</a>
        </li>
        <li class="nav-item">
          <a id="cartButton" class="nav-link" href="#" data-bs-toggle="offcanvas" data-bs-target="#cartDrawer">
            <i class="bi bi-cart"></i> Keranjang <span class="badge ms-2 rounded-pill" id="cart-count">0</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link ${currentPage === 'wishlist.html' ? 'active' : ''}" href="wishlist.html"><i class="bi bi-heart"></i> Wishlist</a>
        </li>
        <li class="nav-item">
          <a class="nav-link ${currentPage === 'transactions.html' ? 'active' : ''}" href="transactions.html"><i class="bi bi-receipt"></i> Transaksi</a>
        </li>
        <li class="nav-item">
          <a class="nav-link ${currentPage === 'profile.html' ? 'active' : ''}" href="profile.html"><i class="bi bi-person"></i> Profil</a>
        </li>
      </ul>
      <div class="mt-auto p-3">
        <div class="text-white text-center mb-2"><small>Hi, ${escapeHtml(user.name)}</small></div>
        <button id="logoutBtn" class="btn btn-sm btn-outline-danger w-100">Logout</button>
      </div>
    `;
    document.getElementById('logoutBtn').addEventListener('click', logout);
    // Rebind cart button click
    const newCartBtn = document.getElementById('cartButton');
    if (newCartBtn) newCartBtn.addEventListener('click', (e) => { e.preventDefault(); showCart(); });
    updateCartCount();
    return;
  }

  if (badge) {
    badge.innerHTML = `
      <div class="text-white mb-2"><small>Hi, ${escapeHtml(user.name)}</small></div>
      <a href="transactions.html" class="btn btn-sm btn-outline-info w-100 mb-2">
        <i class="bi bi-receipt me-1"></i>Transaksi
      </a>
      <button id="logoutBtn" class="btn btn-sm btn-outline-danger w-100">Logout</button>
    `;
    badge.style.display = 'block';
  }
  const lb = document.getElementById('logoutBtn'); if (lb) lb.addEventListener('click', logout);
}

// cart
function getCart() {
  // If logged in, we should rely on server data, but we use local cache for speed
  try { return JSON.parse(localStorage.getItem('cart') || '[]') } catch { return [] }
}

function saveCart(c) {
  localStorage.setItem('cart', JSON.stringify(c));
}

function loadCartFromServer() {
  const user = getUser();
  if (!user) return Promise.resolve();

  return fetchJson(`${apiBase}/cart.php?user_id=${user.id}`)
    .then(res => {
      if (res.success) {
        saveCart(res.items);
        updateCartCount();
      }
    });
}

function addToCart(product) {
  const user = getUser();

  if (!user) {
    alert('Silakan login terlebih dahulu untuk berbelanja.');
    const modal = new bootstrap.Modal(document.getElementById('loginModal'));
    modal.show();
    return;
  }

  if (user.role === 'admin') {
    alert('Admin tidak dapat melakukan pemesanan.');
    return;
  }

  // Get quantity from product object or default to 1
  const qty = product.quantity || 1;

  // Server side cart
  const cartData = {
    user_id: user.id,
    product_id: product.id || product.product_id,
    quantity: qty,
    action: 'add'
  };

  // Add variant_id if present
  if (product.variant_id) {
    cartData.variant_id = product.variant_id;
  }

  fetchJson(`${apiBase}/cart.php`, {
    method: 'POST',
    body: cartData
  }).then(res => {
    if (res.success) {
      loadCartFromServer();
      showToast('Ditambahkan ke keranjang');
    } else {
      alert('Gagal menambahkan ke keranjang: ' + (res.error || ''));
    }
  }).catch(err => {
    console.error(err);
    alert('Terjadi kesalahan saat menambahkan ke keranjang.');
  });
}

function updateCartCount() {
  const count = getCart().reduce((s, i) => s + (i.quantity || 1), 0);

  // Update desktop cart count
  const desktopEl = document.getElementById('cart-count');
  if (desktopEl) desktopEl.innerText = count;

  // Update mobile cart count
  const mobileEl = document.getElementById('mobile-cart-count');
  if (mobileEl) mobileEl.innerText = count;
}

function showCart() {
  const drawer = document.getElementById('cartDrawer'); if (!drawer) return;
  const cart = getCart();
  const list = document.getElementById('cart-items');
  const totalEl = document.getElementById('cart-total');

  if (!cart.length) {
    list.innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-cart-x fs-1 mb-2"></i><p>Keranjang kosong</p></div>';
    totalEl.innerText = 'Rp 0';
    return;
  }

  let html = `<ul class="list-group mb-2">` + cart.map((it, idx) => {
    // Build variant info string
    let variantInfo = '';
    if (it.variant_info && it.variant_info.trim() !== '') {
      variantInfo = `<br><small class="text-info">${escapeHtml(it.variant_info)}</small>`;
    }

    return `<li class="list-group-item d-flex justify-content-between align-items-center">
    <div>
      <strong>${escapeHtml(it.name)}</strong>${variantInfo}
      <br><small>Rp ${Number(it.price).toLocaleString()}</small>
    </div>
    <div class="text-end">
      <div class="d-flex align-items-center gap-2 mb-1">
        <button class="btn btn-sm btn-outline-secondary qty-minus" data-id="${it.product_id}" data-variant="${it.variant_id || ''}" data-cart-id="${it.id || ''}" data-qty="${it.quantity}" data-idx="${idx}" style="width:28px;height:28px;padding:0;">-</button>
        <span class="badge bg-primary rounded-pill" style="min-width:24px;">${it.quantity}</span>
        <button class="btn btn-sm btn-outline-secondary qty-plus" data-id="${it.product_id}" data-variant="${it.variant_id || ''}" data-cart-id="${it.id || ''}" data-idx="${idx}" style="width:28px;height:28px;padding:0;">+</button>
      </div>
      <button class="btn btn-sm btn-outline-danger remove-item" data-id="${it.product_id}" data-variant="${it.variant_id || ''}" data-cart-id="${it.id || ''}" data-idx="${idx}"><i class="bi bi-trash"></i></button>
    </div>
  </li>`;
  }).join('') + `</ul>`;

  list.innerHTML = html;

  const total = cart.reduce((s, it) => s + it.price * it.quantity, 0);
  totalEl.innerText = 'Rp ' + Number(total).toLocaleString();

  // Handle quantity decrease (or delete if qty=1)
  list.querySelectorAll('.qty-minus').forEach(b => b.addEventListener('click', () => {
    const prodId = b.dataset.id;
    const variantId = b.dataset.variant || null;
    const currentQty = parseInt(b.dataset.qty) || 1;
    const user = getUser();

    if (user) {
      if (currentQty <= 1) {
        // Delete item if quantity is 1
        let deleteUrl = `${apiBase}/cart.php?user_id=${user.id}&product_id=${prodId}`;
        if (variantId) deleteUrl += `&variant_id=${variantId}`;

        fetchJson(deleteUrl, { method: 'DELETE' }).then(res => {
          if (res.success) {
            loadCartFromServer().then(showCart);
          }
        });
      } else {
        // Decrease quantity
        const body = { user_id: user.id, product_id: prodId, quantity: -1, action: 'update' };
        if (variantId) body.variant_id = variantId;

        fetchJson(`${apiBase}/cart.php`, {
          method: 'POST',
          body: body
        }).then(res => {
          if (res.success) {
            loadCartFromServer().then(showCart);
          }
        });
      }
    }
  }));

  // Handle quantity increase
  list.querySelectorAll('.qty-plus').forEach(b => b.addEventListener('click', () => {
    const prodId = b.dataset.id;
    const variantId = b.dataset.variant || null;
    const user = getUser();

    if (user) {
      const body = { user_id: user.id, product_id: prodId, quantity: 1, action: 'update' };
      if (variantId) body.variant_id = variantId;

      fetchJson(`${apiBase}/cart.php`, {
        method: 'POST',
        body: body
      }).then(res => {
        if (res.success) {
          loadCartFromServer().then(showCart);
        }
      });
    }
  }));

  // Handle remove item
  list.querySelectorAll('.remove-item').forEach(b => b.addEventListener('click', () => {
    const prodId = b.dataset.id;
    const variantId = b.dataset.variant || null;
    const cartId = b.dataset.cartId || null;
    const idx = parseInt(b.dataset.idx);
    const user = getUser();

    if (user) {
      // Use cart_id if available (more accurate for variants)
      let deleteUrl = `${apiBase}/cart.php?user_id=${user.id}`;
      if (cartId) {
        deleteUrl += `&cart_id=${cartId}`;
      } else {
        deleteUrl += `&product_id=${prodId}`;
        if (variantId) deleteUrl += `&variant_id=${variantId}`;
      }

      fetchJson(deleteUrl, { method: 'DELETE' })
        .then(res => {
          if (res.success) {
            loadCartFromServer().then(showCart);
          }
        });
    } else {
      const c = getCart();
      c.splice(idx, 1);
      saveCart(c);
      updateCartCount();
      showCart();
    }
  }));
}

function loadOrders() {
  const user = getUser();
  if (!user) return;
  fetchJson(`${apiBase}/get_orders.php?user_id=${user.id}`)
    .then(data => {
      const c = document.getElementById('order-list');
      if (!c) return;
      if (!data.length) { c.innerHTML = '<p class="text-muted">Belum ada pesanan.</p>'; return; }
      c.innerHTML = '<h4>Riwayat Pesanan</h4>' + data.map(o => `
        <div class="card mb-3 bg-dark border-secondary text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <span>Order #${o.id} <small class="text-muted">(${o.order_date})</small></span>
              <span class="badge bg-${o.status === 'completed' ? 'success' : (o.status === 'cancelled' ? 'danger' : 'warning')}">${o.status}</span>
            </div>
            <div class="mt-2">Total: Rp ${Number(o.total).toLocaleString()}</div>
          </div>
        </div>
      `).join('');
    }).catch(console.error);
}

// =============================================
// NOTIFICATION SYSTEM
// =============================================

let notificationInterval = null;

function loadNotifications() {
  const user = getUser();
  if (!user) return;

  fetchJson(`${apiBase}/notifications.php?user_id=${user.id}&limit=10`)
    .then(res => {
      if (!res.success) return;

      updateNotificationBadge(res.unread_count);
      updateNotificationDropdown(res.notifications);
    })
    .catch(err => console.log('Notifications not loaded'));
}

function updateNotificationBadge(count) {
  const badges = document.querySelectorAll('.notification-badge');
  badges.forEach(badge => {
    if (count > 0) {
      badge.textContent = count > 9 ? '9+' : count;
      badge.style.display = 'inline-block';
    } else {
      badge.style.display = 'none';
    }
  });
}

function updateNotificationDropdown(notifications) {
  const dropdown = document.getElementById('notificationDropdown');
  if (!dropdown) return;

  if (notifications.length === 0) {
    dropdown.innerHTML = `
      <div class="text-center text-muted py-4 px-3">
        <i class="bi bi-bell-slash fs-3 d-block mb-2"></i>
        <small>Tidak ada notifikasi</small>
      </div>`;
    return;
  }

  const iconMap = {
    'order_status': 'bi-box-seam text-primary',
    'payment': 'bi-credit-card text-success',
    'promo': 'bi-tag text-warning',
    'review': 'bi-star text-warning',
    'system': 'bi-bell text-info'
  };

  dropdown.innerHTML = `
    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom border-secondary">
      <strong class="text-white">Notifikasi</strong>
      <button class="btn btn-link btn-sm p-0 text-muted" onclick="markAllNotificationsRead()">
        <small>Tandai semua dibaca</small>
      </button>
    </div>
    <div class="notification-list" style="max-height: 300px; overflow-y: auto;">
      ${notifications.map(n => `
        <a href="${n.link || '#'}" class="dropdown-item notification-item ${n.is_read ? '' : 'unread'}" 
           data-id="${n.id}" onclick="markNotificationRead(${n.id})">
          <div class="d-flex gap-2">
            <i class="bi ${iconMap[n.type] || iconMap['system']}"></i>
            <div class="flex-grow-1">
              <div class="fw-bold small ${n.is_read ? 'text-muted' : 'text-white'}">${escapeHtml(n.title)}</div>
              <small class="text-muted">${escapeHtml(n.message).substring(0, 50)}${n.message.length > 50 ? '...' : ''}</small>
              <div class="text-muted" style="font-size: 0.7rem;">${formatTimeAgo(n.created_at)}</div>
            </div>
          </div>
        </a>
      `).join('')}
    </div>
  `;
}

function markNotificationRead(id) {
  const user = getUser();
  if (!user) return;

  fetchJson(`${apiBase}/notifications.php`, {
    method: 'PUT',
    body: { user_id: user.id, id: id }
  }).then(() => loadNotifications());
}

function markAllNotificationsRead() {
  const user = getUser();
  if (!user) return;

  fetchJson(`${apiBase}/notifications.php`, {
    method: 'PUT',
    body: { user_id: user.id, mark_all: true }
  }).then(() => {
    loadNotifications();
    showToast('Semua notifikasi ditandai sudah dibaca');
  });
}

function formatTimeAgo(dateStr) {
  const date = new Date(dateStr);
  const now = new Date();
  const diff = Math.floor((now - date) / 1000);

  if (diff < 60) return 'Baru saja';
  if (diff < 3600) return `${Math.floor(diff / 60)} menit lalu`;
  if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`;
  if (diff < 604800) return `${Math.floor(diff / 86400)} hari lalu`;
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}

function startNotificationPolling() {
  // Load immediately
  loadNotifications();

  // Poll every 30 seconds
  if (notificationInterval) clearInterval(notificationInterval);
  notificationInterval = setInterval(loadNotifications, 30000);
}

function stopNotificationPolling() {
  if (notificationInterval) {
    clearInterval(notificationInterval);
    notificationInterval = null;
  }
}

// Create notification for order status change (called from other parts of app)
function createOrderNotification(userId, orderId, status) {
  const statusMessages = {
    'Dikemas': { title: 'Pesanan Dikemas', message: `Pesanan #${orderId} sedang dikemas dan akan segera dikirim.` },
    'Dikirim': { title: 'Pesanan Dikirim', message: `Pesanan #${orderId} sedang dalam perjalanan ke alamat Anda.` },
    'Selesai': { title: 'Pesanan Selesai', message: `Pesanan #${orderId} telah diterima. Terima kasih telah berbelanja!` }
  };

  const notif = statusMessages[status];
  if (!notif) return;

  fetchJson(`${apiBase}/notifications.php`, {
    method: 'POST',
    body: {
      user_id: userId,
      type: 'order_status',
      title: notif.title,
      message: notif.message,
      link: 'transactions.html'
    }
  });
}

// Mobile cart button handler - ensures offcanvas opens on touch devices
document.addEventListener('DOMContentLoaded', function () {
  // Update cart count on page load
  updateCartCount();

  // Add explicit click handler for mobile cart button
  const mobileCartBtn = document.querySelector('.mobile-header .cart-link');
  if (mobileCartBtn) {
    mobileCartBtn.addEventListener('click', function (e) {
      e.preventDefault();

      // Show cart content first
      showCart();

      // Open offcanvas using Bootstrap API
      const cartDrawer = document.getElementById('cartDrawer');
      if (cartDrawer) {
        const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(cartDrawer);
        offcanvas.show();
      }
    });
  }

  // Also handle desktop cart button the same way
  const desktopCartBtn = document.getElementById('cartButton');
  if (desktopCartBtn) {
    desktopCartBtn.addEventListener('click', function (e) {
      e.preventDefault();
      showCart();

      const cartDrawer = document.getElementById('cartDrawer');
      if (cartDrawer) {
        const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(cartDrawer);
        offcanvas.show();
      }
    });
  }
});

