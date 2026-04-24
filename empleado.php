<?php
require __DIR__ . '/api/auth.php';
requireLogin();

$user = getSessionUser();
$fullName = $user ? htmlspecialchars(trim(($user['nombre'] ?? '') . ' ' . ($user['apellidos'] ?? ''))) : '';
?>
<!DOCTYPE html>
<html class="light" lang="es">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>IUBOLAB - Stock Químico</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="icon" href="imagenes/icono_circulo.png" type="image/png">
    <link rel="icon" type="image/png" sizes="32x32" href="imagenes/icono_circulo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="imagenes/icono_circulo.png">
    <link rel="apple-touch-icon" href="imagenes/icono_circulo.png">
    <link href="https://fonts.googleapis.com/css2?family=Argentum+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#5c068c",
                        "background-light": "#f8f6f6",
                        "background-dark": "#221610",
                    },
                    fontFamily: {
                        "display": ["Argentum Sans", "sans-serif"]
                    }
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Argentum Sans', sans-serif; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark min-h-screen text-slate-900 dark:text-slate-100">
    <div class="relative flex min-h-screen w-full flex-col overflow-x-hidden">
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 border-b border-solid border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 md:px-10 py-4 fixed top-0 left-0 right-0 z-50">
            <div class="flex items-center gap-3 flex-wrap">
                <img alt="Logo" class="h-10 w-auto object-contain" src="imagenes/instituto-biorganica-agonzalez-original.png" />
                <h2 class="text-slate-900 dark:text-slate-100 text-lg font-bold leading-tight tracking-[-0.015em] border-l border-slate-300 dark:border-slate-700 pl-4">IUBOLAB / Control de Stock</h2>
                <?php if ($fullName): ?>
                    <span class="text-sm text-slate-500 dark:text-slate-400 pl-4">Hola, <?php echo $fullName; ?></span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-3 w-full md:w-auto justify-end">
                <a href="#" onclick="logout(); return false;" aria-label="Cerrar sesión" title="Cerrar sesión" class="flex shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl h-11 w-11 border border-primary bg-white dark:bg-slate-900 text-primary text-sm font-bold leading-normal tracking-[0.015em] hover:bg-primary hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-base">power_settings_new</span>
                </a>
            </div>
        </header>

        <main class="flex-1 flex justify-center pt-36 md:pt-28 pb-10 px-4 md:px-0">
            <div class="w-full max-w-[980px] flex flex-col gap-6">
                <div class="text-center">
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-slate-100">Inventario de Productos Químicos</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Busca un producto y actualiza su cantidad en stock.</p>
                </div>

                <section class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6">
                    <div class="flex flex-col md:flex-row gap-3 md:items-center md:justify-between">
                        <div class="w-full md:max-w-lg">
                            <label for="searchInput" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Buscador</label>
                            <input id="searchInput" type="text" placeholder="Ej: acetona, etanol..." class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring-primary focus:border-primary" />
                        </div>
                        <div id="resultsCount" class="text-sm text-slate-500 dark:text-slate-400"></div>
                    </div>

                    <div id="statusBox" class="hidden mt-4 text-sm rounded-lg px-3 py-2"></div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left border-b border-slate-200 dark:border-slate-800">
                                    <th class="py-3 pr-4">Producto</th>
                                    <th class="py-3 pr-4">Cantidad</th>
                                    <th class="py-3 pr-4">Unidad</th>
                                    <th class="py-3 pr-4">Actualizar</th>
                                </tr>
                            </thead>
                            <tbody id="productsBody"></tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        const productsBody = document.getElementById('productsBody');
        const searchInput = document.getElementById('searchInput');
        const resultsCount = document.getElementById('resultsCount');
        const statusBox = document.getElementById('statusBox');

        function showStatus(message, variant = 'info') {
            const palette = {
                info: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
                success: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                error: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
            };
            statusBox.className = `mt-4 text-sm rounded-lg px-3 py-2 ${palette[variant] || palette.info}`;
            statusBox.textContent = message;
            statusBox.classList.remove('hidden');
        }

        async function loadProducts(query = '') {
            if (!query) {
                productsBody.innerHTML = '';
                resultsCount.textContent = '';
                statusBox.classList.add('hidden');
                return;
            }
            try {
                const resp = await fetch(`api/chemical_products.php?q=${encodeURIComponent(query)}`, { credentials: 'same-origin' });
                const data = await resp.json();
                if (!resp.ok) {
                    throw new Error(data.error || 'No se pudieron cargar los productos');
                }
                renderRows(Array.isArray(data.items) ? data.items : []);
            } catch (err) {
                renderRows([]);
                showStatus(err.message || 'Error al cargar productos', 'error');
            }
        }

        function renderRows(items) {
            resultsCount.textContent = `${items.length} producto(s)`;
            if (!items.length) {
                productsBody.innerHTML = `
                    <tr>
                        <td colspan="4" class="py-6 text-center text-slate-500 dark:text-slate-400">
                            No se encontraron productos.
                        </td>
                    </tr>
                `;
                return;
            }

            productsBody.innerHTML = items.map((item) => `
                <tr class="border-b border-slate-100 dark:border-slate-800">
                    <td class="py-3 pr-4 font-semibold">${escapeHtml(item.nombre)}</td>
                    <td class="py-3 pr-4">${Number(item.cantidad)}</td>
                    <td class="py-3 pr-4">${escapeHtml(item.unidad)}</td>
                    <td class="py-3 pr-4">
                        <form class="flex items-center gap-2 update-form" data-product-id="${Number(item.id)}" data-product-name="${escapeHtmlAttr(item.nombre)}">
                            <input
                                type="number"
                                min="0"
                                required
                                value="${Number(item.cantidad)}"
                                class="w-28 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring-primary focus:border-primary"
                                name="new_quantity"
                            />
                            <button type="submit" class="rounded-lg border border-primary text-primary px-3 py-2 font-semibold hover:bg-primary hover:text-white transition-colors">
                                Guardar
                            </button>
                        </form>
                    </td>
                </tr>
            `).join('');

            bindUpdateForms();
        }

        function bindUpdateForms() {
            document.querySelectorAll('.update-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const productId = Number(form.dataset.productId);
                    const productName = form.dataset.productName || 'producto';
                    const input = form.querySelector('input[name="new_quantity"]');
                    const newQuantity = Number(input.value);

                    if (!Number.isInteger(newQuantity) || newQuantity < 0) {
                        showStatus('La cantidad debe ser un número entero mayor o igual a 0', 'error');
                        return;
                    }

                    try {
                        const resp = await fetch('api/update_product_stock.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'same-origin',
                            body: JSON.stringify({ product_id: productId, new_quantity: newQuantity })
                        });
                        const data = await resp.json();
                        if (!resp.ok) {
                            throw new Error(data.error || 'No se pudo actualizar');
                        }
                        showStatus(`Stock actualizado para ${productName}: ${data.old_quantity} → ${data.new_quantity}`, 'success');
                        await loadProducts(searchInput.value.trim());
                    } catch (err) {
                        showStatus(err.message || 'Error al guardar cambios', 'error');
                    }
                });
            });
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function escapeHtmlAttr(value) {
            return escapeHtml(value).replace(/`/g, '&#096;');
        }

        function debounce(fn, delay) {
            let timer;
            return (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => fn(...args), delay);
            };
        }

        searchInput.addEventListener('input', debounce(() => {
            loadProducts(searchInput.value.trim());
        }, 250));

        function logout() {
            window.location.href = 'api/logout.php';
        }

        productsBody.innerHTML = '';
        resultsCount.textContent = '';
    </script>
</body>
</html>
