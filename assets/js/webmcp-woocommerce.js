/**
 * WebMCP for WooCommerce
 * Expone funcionalidades de WooCommerce mediante WebMCP (navigator.modelContext)
 */

(function() {
    'use strict';

    // Verificar si WebMCP está disponible
    if (!('modelContext' in window.navigator)) {
        console.warn('WebMCP no está disponible en este navegador. Necesitas Chrome 146+ con experimental web platform features habilitado.');
        return;
    }

    console.log('🤖 WebMCP para WooCommerce inicializando...');

    // Función helper para hacer llamadas a la API REST
    async function apiCall(endpoint, method = 'GET', data = null) {
        const url = webmcpData.restUrl + endpoint;
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin', // Importante: incluye cookies de sesión de WooCommerce
        };

        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);

            // Intentar parsear JSON
            let result;
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                result = await response.json();
            } else {
                const text = await response.text();
                throw new Error(`Respuesta no es JSON: ${text.substring(0, 200)}`);
            }

            if (!response.ok) {
                throw new Error(result.message || result.code || 'Error en la petición');
            }

            return result;
        } catch (error) {
            console.error('Error en API call:', error);
            throw error;
        }
    }

    // Registrar todas las herramientas WebMCP
    window.navigator.modelContext.provideContext({
        tools: [
            // ===== HERRAMIENTAS DE PRODUCTOS =====

            {
                name: "search_products",
                description: "Buscar productos en la tienda. Puedes buscar por texto, filtrar por categoría y rango de precio.",
                inputSchema: {
                    type: "object",
                    properties: {
                        query: {
                            type: "string",
                            description: "Término de búsqueda (nombre, descripción, etc.)"
                        },
                        category: {
                            type: "string",
                            description: "Slug de la categoría para filtrar (ej: 'electronics', 'clothing')"
                        },
                        min_price: {
                            type: "number",
                            description: "Precio mínimo"
                        },
                        max_price: {
                            type: "number",
                            description: "Precio máximo"
                        },
                        limit: {
                            type: "number",
                            description: "Número máximo de resultados (por defecto: 10)",
                            default: 10
                        }
                    }
                },
                async execute(params, agent) {
                    try {
                        const queryParams = new URLSearchParams();

                        if (params.query) queryParams.append('query', params.query);
                        if (params.category) queryParams.append('category', params.category);
                        if (params.min_price) queryParams.append('min_price', params.min_price);
                        if (params.max_price) queryParams.append('max_price', params.max_price);
                        if (params.limit) queryParams.append('limit', params.limit);

                        const result = await apiCall('products/search?' + queryParams.toString());

                        const summary = `Encontrados ${result.total} productos. Mostrando ${result.products.length}.`;
                        const productList = result.products.map(p =>
                            `- ${p.name}: ${p.price_html} (ID: ${p.id})${p.in_stock ? '' : ' [AGOTADO]'}`
                        ).join('\n');

                        return {
                            content: [
                                {
                                    type: "text",
                                    text: `${summary}\n\n${productList}\n\nDatos completos: ${JSON.stringify(result, null, 2)}`
                                }
                            ]
                        };
                    } catch (error) {
                        throw new Error(`Error buscando productos: ${error.message}`);
                    }
                }
            },

            {
                name: "get_product",
                description: "Obtener información detallada de un producto específico por su ID.",
                inputSchema: {
                    type: "object",
                    properties: {
                        product_id: {
                            type: "number",
                            description: "ID del producto"
                        }
                    },
                    required: ["product_id"]
                },
                async execute(params, agent) {
                    try {
                        const result = await apiCall(`products/${params.product_id}`);
                        const product = result.product;

                        const details = `
Producto: ${product.name}
Precio: ${product.price_html}
${product.on_sale ? '¡EN OFERTA!' : ''}
Stock: ${product.in_stock ? 'Disponible' : 'Agotado'}${product.stock_quantity ? ` (${product.stock_quantity} unidades)` : ''}

Descripción corta:
${product.short_description}

Categorías: ${product.categories.map(c => c.name).join(', ')}

URL: ${product.permalink}

Datos completos: ${JSON.stringify(product, null, 2)}`;

                        return {
                            content: [
                                {
                                    type: "text",
                                    text: details
                                }
                            ]
                        };
                    } catch (error) {
                        throw new Error(`Error obteniendo producto: ${error.message}`);
                    }
                }
            },

            {
                name: "get_categories",
                description: "Listar todas las categorías de productos disponibles en la tienda.",
                inputSchema: {
                    type: "object",
                    properties: {}
                },
                async execute(params, agent) {
                    try {
                        const result = await apiCall('products/categories');

                        const categoryList = result.categories.map(c =>
                            `- ${c.name} (slug: ${c.slug}) - ${c.count} productos`
                        ).join('\n');

                        return {
                            content: [
                                {
                                    type: "text",
                                    text: `Categorías disponibles:\n\n${categoryList}\n\nDatos: ${JSON.stringify(result.categories, null, 2)}`
                                }
                            ]
                        };
                    } catch (error) {
                        throw new Error(`Error obteniendo categorías: ${error.message}`);
                    }
                }
            },

            // ===== HERRAMIENTAS DE CARRITO =====

            {
                name: "add_to_cart",
                description: "Añadir un producto al carrito de compra. Requiere confirmación del usuario.",
                inputSchema: {
                    type: "object",
                    properties: {
                        product_id: {
                            type: "number",
                            description: "ID del producto a añadir"
                        },
                        quantity: {
                            type: "number",
                            description: "Cantidad a añadir (por defecto: 1)",
                            default: 1
                        }
                    },
                    required: ["product_id"]
                },
                async execute(params, agent) {
                    try {
                        // Primero obtener info del producto
                        const productInfo = await apiCall(`products/${params.product_id}`);
                        const product = productInfo.product;

                        // Solicitar confirmación del usuario
                        const confirmed = await agent.requestUserInteraction(
                            async () => {
                                return new Promise((resolve) => {
                                    const quantity = params.quantity || 1;
                                    const message = `¿Añadir ${quantity}x "${product.name}" al carrito por ${product.price_html}?`;
                                    const userConfirmed = confirm(message);
                                    resolve(userConfirmed);
                                });
                            }
                        );

                        if (!confirmed) {
                            throw new Error("El usuario canceló la operación.");
                        }

                        // Añadir al carrito
                        const result = await apiCall('cart/add', 'POST', {
                            product_id: params.product_id,
                            quantity: params.quantity || 1
                        });

                        return {
                            content: [
                                {
                                    type: "text",
                                    text: `✅ ${result.message}\n\nCarrito actual:\n- ${result.cart.item_count} artículos\n- Total: ${result.cart.currency_symbol}${result.cart.total}\n\nDatos: ${JSON.stringify(result.cart, null, 2)}`
                                }
                            ]
                        };
                    } catch (error) {
                        throw new Error(`Error añadiendo al carrito: ${error.message}`);
                    }
                }
            },

            {
                name: "get_cart",
                description: "Ver el contenido actual del carrito de compra.",
                inputSchema: {
                    type: "object",
                    properties: {}
                },
                async execute(params, agent) {
                    try {
                        const result = await apiCall('cart');
                        const cart = result.cart;

                        if (cart.item_count === 0) {
                            return {
                                content: [
                                    {
                                        type: "text",
                                        text: "El carrito está vacío."
                                    }
                                ]
                            };
                        }

                        const itemsList = cart.items.map(item =>
                            `- ${item.product_name} x${item.quantity} = ${cart.currency_symbol}${item.line_total}`
                        ).join('\n');

                        return {
                            content: [
                                {
                                    type: "text",
                                    text: `Carrito de compra:\n\n${itemsList}\n\nSubtotal: ${cart.currency_symbol}${cart.subtotal}\nTotal: ${cart.currency_symbol}${cart.total}\n\nDatos completos: ${JSON.stringify(cart, null, 2)}`
                                }
                            ]
                        };
                    } catch (error) {
                        throw new Error(`Error obteniendo carrito: ${error.message}`);
                    }
                }
            },

            {
                name: "update_cart_item",
                description: "Actualizar la cantidad de un producto en el carrito.",
                inputSchema: {
                    type: "object",
                    properties: {
                        cart_item_key: {
                            type: "string",
                            description: "Clave del item en el carrito"
                        },
                        quantity: {
                            type: "number",
                            description: "Nueva cantidad"
                        }
                    },
                    required: ["cart_item_key", "quantity"]
                },
                async execute(params, agent) {
                    try {
                        const result = await apiCall('cart/update', 'POST', params);

                        return {
                            content: [
                                {
                                    type: "text",
                                    text: `✅ ${result.message}\n\nCarrito actualizado: ${result.cart.item_count} artículos, Total: ${result.cart.currency_symbol}${result.cart.total}`
                                }
                            ]
                        };
                    } catch (error) {
                        throw new Error(`Error actualizando carrito: ${error.message}`);
                    }
                }
            },

            {
                name: "remove_from_cart",
                description: "Eliminar un producto del carrito.",
                inputSchema: {
                    type: "object",
                    properties: {
                        cart_item_key: {
                            type: "string",
                            description: "Clave del item en el carrito a eliminar"
                        }
                    },
                    required: ["cart_item_key"]
                },
                async execute(params, agent) {
                    try {
                        const confirmed = await agent.requestUserInteraction(
                            async () => {
                                return new Promise((resolve) => {
                                    const userConfirmed = confirm("¿Eliminar este producto del carrito?");
                                    resolve(userConfirmed);
                                });
                            }
                        );

                        if (!confirmed) {
                            throw new Error("El usuario canceló la operación.");
                        }

                        const result = await apiCall('cart/remove', 'POST', params);

                        return {
                            content: [
                                {
                                    type: "text",
                                    text: `✅ ${result.message}\n\nCarrito: ${result.cart.item_count} artículos, Total: ${result.cart.currency_symbol}${result.cart.total}`
                                }
                            ]
                        };
                    } catch (error) {
                        throw new Error(`Error eliminando del carrito: ${error.message}`);
                    }
                }
            },

            // ===== CHECKOUT =====

            {
                name: "proceed_to_checkout",
                description: "Obtener la URL del checkout para finalizar la compra. El usuario será redirigido para completar el pago.",
                inputSchema: {
                    type: "object",
                    properties: {}
                },
                async execute(params, agent) {
                    try {
                        const result = await apiCall('checkout/url');

                        const confirmed = await agent.requestUserInteraction(
                            async () => {
                                return new Promise((resolve) => {
                                    const message = `Proceder al checkout para completar la compra de ${result.cart.item_count} artículos por ${result.cart.currency_symbol}${result.cart.total}?`;
                                    const userConfirmed = confirm(message);
                                    resolve(userConfirmed);
                                });
                            }
                        );

                        if (confirmed) {
                            window.location.href = result.checkout_url;
                            return {
                                content: [
                                    {
                                        type: "text",
                                        text: `Redirigiendo al checkout...`
                                    }
                                ]
                            };
                        } else {
                            return {
                                content: [
                                    {
                                        type: "text",
                                        text: `Checkout cancelado. URL disponible: ${result.checkout_url}`
                                    }
                                ]
                            };
                        }
                    } catch (error) {
                        throw new Error(`Error en checkout: ${error.message}`);
                    }
                }
            }
        ]
    });

    console.log('✅ WebMCP para WooCommerce registrado correctamente.');
    console.log(`📦 Tienda: ${webmcpData.shopName}`);
    console.log('🛠️ Herramientas disponibles: search_products, get_product, get_categories, add_to_cart, get_cart, update_cart_item, remove_from_cart, proceed_to_checkout');

    // Exponer información de debug globalmente
    window.webmcpDebug = {
        isAvailable: true,
        toolsRegistered: [
            'search_products',
            'get_product',
            'get_categories',
            'add_to_cart',
            'get_cart',
            'update_cart_item',
            'remove_from_cart',
            'proceed_to_checkout'
        ],
        shopInfo: {
            name: webmcpData.shopName,
            url: webmcpData.shopUrl,
            currency: webmcpData.currency,
            currencySymbol: webmcpData.currencySymbol,
            restUrl: webmcpData.restUrl
        },
        // Función helper para listar herramientas
        listTools: function() {
            console.group('🛠️ WebMCP Tools para WooCommerce');
            this.toolsRegistered.forEach((tool, index) => {
                console.log(`${index + 1}. ${tool}`);
            });
            console.groupEnd();
            return this.toolsRegistered;
        },
        // Función para verificar el estado
        checkStatus: function() {
            console.group('📊 Estado de WebMCP');
            console.log('✅ WebMCP disponible:', 'modelContext' in window.navigator);
            console.log('📦 Tienda:', this.shopInfo.name);
            console.log('🔧 Herramientas registradas:', this.toolsRegistered.length);
            console.log('🌐 API REST:', this.shopInfo.restUrl);
            console.groupEnd();
        }
    };

    // Mostrar mensaje de ayuda
    console.group('💡 Comandos útiles');
    console.log('Para ver el estado:', '%cwebmcpDebug.checkStatus()', 'color: #0066cc; font-weight: bold');
    console.log('Para listar herramientas:', '%cwebmcpDebug.listTools()', 'color: #0066cc; font-weight: bold');
    console.log('Para ver info:', '%cwebmcpDebug', 'color: #0066cc; font-weight: bold');
    console.groupEnd();

})();
