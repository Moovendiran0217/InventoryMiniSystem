const productList = document.querySelector('#product-list');
const lowStockList = document.querySelector('#low-stock-list');
const productCount = document.querySelector('#product-count');
const orderForm = document.querySelector('#order-form');
const notice = document.querySelector('#notice');
const historyForm = document.querySelector('#history-form');
const historyResults = document.querySelector('#history-results');
let products = [];

const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
}[character]));

const amount = (value) => Number(value).toFixed(2);

function showNotice(message, isError = false) {
    notice.textContent = message;
    notice.classList.toggle('error', isError);
    notice.hidden = false;
    window.clearTimeout(showNotice.timeout);
    showNotice.timeout = window.setTimeout(() => {
        notice.hidden = true;
    }, 5000);
}

async function getJson(url, options = {}) {
    const response = await fetch(url, {
        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        ...options,
    });
    const result = await response.json();

    if (!response.ok) {
        const validationMessages = Object.values(result.errors ?? {}).flat();
        throw new Error(validationMessages[0] ?? result.message ?? 'The request could not be completed.');
    }

    return result;
}

function renderProducts() {
    productCount.textContent = `${products.length} products`;

    if (products.length === 0) {
        productList.innerHTML = '<div class="empty-state">No products found. Run the database seeder to add sample products.</div>';
        return;
    }

    productList.innerHTML = products.map((product) => `
        <div class="product-row">
            <div>
                <div class="product-name">${escapeHtml(product.name)}</div>
                <div class="product-code">${escapeHtml(product.code)} · ${Number(product.tax_percentage).toFixed(2)}% tax</div>
            </div>
            <div class="product-price">
                ${amount(product.unit_price)}
                <span class="stock-tag">${product.stock_on_hand} in stock</span>
            </div>
            <input class="quantity-input" type="number" min="0" max="${product.stock_on_hand}" value="0"
                aria-label="Quantity of ${escapeHtml(product.name)}" data-quantity="${product.id}"
                ${product.stock_on_hand === 0 ? 'disabled' : ''}>
        </div>
    `).join('');

    productList.querySelectorAll('[data-quantity]').forEach((input) => {
        input.addEventListener('input', updateEstimate);
    });
}

function renderLowStock(items) {
    if (items.length === 0) {
        lowStockList.innerHTML = '<div class="empty-state">All products are at or above the low-stock threshold.</div>';
        return;
    }

    lowStockList.innerHTML = `
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Product</th><th>Code</th><th>On hand</th></tr></thead>
                <tbody>${items.map((product) => `
                    <tr>
                        <td>${escapeHtml(product.name)}</td>
                        <td>${escapeHtml(product.code)}</td>
                        <td>${product.stock_on_hand}</td>
                    </tr>
                `).join('')}</tbody>
            </table>
        </div>
    `;
}

async function refreshCatalog() {
    try {
        const [catalog, lowStock] = await Promise.all([
            getJson('/api/products'),
            getJson('/api/products/low-stock'),
        ]);

        products = catalog.data;
        renderProducts();
        renderLowStock(lowStock.data);
    } catch (error) {
        showNotice(error.message, true);
        productList.innerHTML = '<div class="empty-state">Unable to load the catalog.</div>';
    }
}

function updateEstimate() {
    let subtotal = 0;
    let tax = 0;

    productList.querySelectorAll('[data-quantity]').forEach((input) => {
        const quantity = Number(input.value);
        const product = products.find((item) => item.id === Number(input.dataset.quantity));

        if (quantity > 0 && product) {
            const lineSubtotal = Number(product.unit_price) * quantity;
            subtotal += lineSubtotal;
            tax += Math.round(lineSubtotal * Number(product.tax_percentage) + Number.EPSILON) / 100;
        }
    });

    document.querySelector('#estimate-subtotal').textContent = amount(subtotal);
    document.querySelector('#estimate-tax').textContent = amount(tax);
    document.querySelector('#estimate-total').textContent = amount(subtotal + tax);
}

orderForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const items = [...productList.querySelectorAll('[data-quantity]')]
        .map((input) => ({ product_id: Number(input.dataset.quantity), quantity: Number(input.value) }))
        .filter((item) => item.quantity > 0);

    if (items.length === 0) {
        showNotice('Add at least one product to the order.', true);
        return;
    }

    const button = orderForm.querySelector('button[type="submit"]');
    button.disabled = true;

    try {
        const order = await getJson('/api/orders', {
            method: 'POST',
            body: JSON.stringify({
                customer: {
                    name: document.querySelector('#customer-name').value.trim(),
                    email: document.querySelector('#customer-email').value.trim(),
                },
                items,
            }),
        });

        showNotice(`Order #${order.data.id} created. Total: ${amount(order.data.grand_total)}.`);
        orderForm.reset();
        updateEstimate();
        await refreshCatalog();
    } catch (error) {
        showNotice(error.message, true);
    } finally {
        button.disabled = false;
    }
});

historyForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const email = document.querySelector('#history-email').value.trim();
    historyResults.innerHTML = '<div class="loading">Loading order history…</div>';

    try {
        const result = await getJson(`/api/customers/orders?email=${encodeURIComponent(email)}`);
        const orders = result.data;

        historyResults.innerHTML = orders.length === 0
            ? '<div class="empty-state">No orders found.</div>'
            : orders.map((order) => `
                <div class="history-order">
                    <div class="history-meta">
                        <span>Order #${order.id} · ${new Date(order.created_at).toLocaleDateString()}</span>
                        <span class="history-total">${amount(order.grand_total)}</span>
                    </div>
                    <div class="history-lines">${order.items.map((item) => `${item.quantity} × ${escapeHtml(item.product_name)}`).join('<br>')}</div>
                </div>
            `).join('');
    } catch (error) {
        historyResults.innerHTML = '<div class="empty-state">No customer found for this email.</div>';
        showNotice(error.message, true);
    }
});

refreshCatalog();
