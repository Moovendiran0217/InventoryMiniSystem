<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Counter Desk · {{ config('app.name', 'Inventory Mini System') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <main class="page-shell">
            <header class="topbar">
                <div class="brand">
                    <span class="brand-mark" aria-hidden="true">⌂</span>
                    <span>Counter Desk</span>
                </div>
                <span class="topbar-note">Inventory &amp; orders workspace</span>
            </header>

            <section class="hero">
                <div>
                    <p class="eyebrow">Retail counter · {{ now()->format('l, F j') }}</p>
                    <h1>Good business<br>starts here.</h1>
                    <p class="hero-copy">Build customer orders, keep an eye on available stock, and check recent purchase history from one simple workspace.</p>
                </div>
                <div class="hero-stat">
                    <strong id="product-count">—</strong>
                    <span>products in catalog</span>
                </div>
            </section>

            <div class="dashboard-grid">
                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Create an order</h2>
                            <p class="panel-subtitle">Customer details and selected product quantities</p>
                        </div>
                        <span class="pill">Stock checked at checkout</span>
                    </div>

                    <form id="order-form">
                        <div class="field-grid">
                            <div class="field">
                                <label for="customer-name">Customer name</label>
                                <input id="customer-name" name="name" autocomplete="name" placeholder="e.g. Alex Buyer" required maxlength="255">
                            </div>
                            <div class="field">
                                <label for="customer-email">Customer email</label>
                                <input id="customer-email" name="email" type="email" autocomplete="email" placeholder="alex@example.com" required maxlength="255">
                            </div>
                        </div>

                        <p class="section-label">Catalog · enter quantities to add items</p>
                        <div class="product-list" id="product-list">
                            <div class="loading">Loading products…</div>
                        </div>

                        <div class="order-footer">
                            <div class="totals" aria-live="polite">
                                <span>Subtotal <span id="estimate-subtotal">0.00</span> · Tax <span id="estimate-tax">0.00</span></span>
                                <strong>Total <span id="estimate-total">0.00</span></strong>
                            </div>
                            <button class="primary-button" type="submit">Create order</button>
                        </div>
                    </form>
                </section>

                <aside>
                    <section class="panel">
                        <div class="panel-heading">
                            <div>
                                <h2>Customer history</h2>
                                <p class="panel-subtitle">Look up orders using the customer's email</p>
                            </div>
                        </div>
                        <form class="history-search" id="history-form">
                            <input id="history-email" type="email" placeholder="customer@example.com" required aria-label="Customer email">
                            <button class="secondary-button" type="submit">Search</button>
                        </form>
                        <div id="history-results" aria-live="polite">
                            <div class="empty-state">Order history will appear here.</div>
                        </div>
                    </section>

                    <section class="panel">
                        <div class="panel-heading">
                            <div>
                                <h2>Low stock</h2>
                                <p class="panel-subtitle">Products below the configured threshold</p>
                            </div>
                            <span class="pill">Restock watch</span>
                        </div>
                        <div id="low-stock-list">
                            <div class="loading">Checking inventory…</div>
                        </div>
                    </section>
                </aside>
            </div>
        </main>
        <div class="notice" id="notice" role="status" aria-live="polite" hidden></div>
    </body>
</html>
