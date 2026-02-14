# WebMCP para WooCommerce

Plugin de WordPress que expone las funcionalidades de WooCommerce a agentes de IA mediante **WebMCP** (Model Context Protocol para navegadores).

## ¿Qué es WebMCP?

WebMCP es un nuevo estándar web desarrollado por Google y Microsoft que permite a los sitios web exponer sus funcionalidades como "herramientas" (tools) que los agentes de IA pueden usar directamente desde el navegador mediante la API `navigator.modelContext`.

A diferencia del MCP tradicional (que funciona en el backend), WebMCP opera completamente en el lado del cliente, permitiendo que los agentes de IA interactúen con tu sitio web de forma estructurada.

## Características

✅ **Búsqueda de productos** - Busca por texto, categoría y rango de precio
✅ **Información detallada** - Obtén detalles completos de cualquier producto
✅ **Gestión de carrito** - Añade, actualiza y elimina productos del carrito
✅ **Checkout directo** - Procede al checkout para completar la compra
✅ **Confirmaciones de usuario** - Solicita confirmación antes de acciones importantes
✅ **API REST completa** - Endpoints optimizados para WooCommerce

## Requisitos

- WordPress 6.0 o superior
- WooCommerce 7.0 o superior
- PHP 7.4 o superior
- **Google Chrome 146+** con experimental web platform features habilitado

### Habilitar WebMCP en Chrome

1. Abre Chrome Canary 146 o superior
2. Navega a: `chrome://flags/#enable-experimental-web-platform-features`
3. Activa "Experimental Web Platform features"
4. Reinicia el navegador

## Instalación

1. Descarga el plugin o clona este repositorio
2. Copia la carpeta `webmcp-wp` a `/wp-content/plugins/`
3. Activa el plugin desde el panel de WordPress
4. Ve a **WebMCP** en el menú de administración

## Configuración

Una vez activado, el plugin automáticamente:

- Registra todas las herramientas WebMCP en el navegador
- Crea endpoints REST API para WooCommerce
- Carga el script JavaScript en todas las páginas del sitio

Puedes desactivar temporalmente las herramientas desde **WordPress Admin → WebMCP**.

## Herramientas disponibles

El plugin expone las siguientes herramientas WebMCP:

### 🔍 Productos

#### `search_products`
Busca productos en la tienda.

**Parámetros:**
- `query` (string, opcional) - Término de búsqueda
- `category` (string, opcional) - Slug de categoría
- `min_price` (number, opcional) - Precio mínimo
- `max_price` (number, opcional) - Precio máximo
- `limit` (number, opcional) - Máximo de resultados (default: 10)

**Ejemplo de uso:**
```javascript
// Buscar camisetas
search_products({ query: "camiseta", limit: 5 })

// Buscar productos en oferta bajo 50€
search_products({ max_price: 50, limit: 10 })

// Buscar en categoría específica
search_products({ category: "electronics" })
```

#### `get_product`
Obtiene información detallada de un producto.

**Parámetros:**
- `product_id` (number, requerido) - ID del producto

**Ejemplo:**
```javascript
get_product({ product_id: 123 })
```

#### `get_categories`
Lista todas las categorías de productos.

**Ejemplo:**
```javascript
get_categories({})
```

### 🛒 Carrito

#### `add_to_cart`
Añade un producto al carrito (con confirmación del usuario).

**Parámetros:**
- `product_id` (number, requerido) - ID del producto
- `quantity` (number, opcional) - Cantidad (default: 1)

**Ejemplo:**
```javascript
add_to_cart({ product_id: 123, quantity: 2 })
```

#### `get_cart`
Muestra el contenido actual del carrito.

**Ejemplo:**
```javascript
get_cart({})
```

#### `update_cart_item`
Actualiza la cantidad de un producto en el carrito.

**Parámetros:**
- `cart_item_key` (string, requerido) - Clave del item
- `quantity` (number, requerido) - Nueva cantidad

#### `remove_from_cart`
Elimina un producto del carrito (con confirmación).

**Parámetros:**
- `cart_item_key` (string, requerido) - Clave del item

### 💳 Checkout

#### `proceed_to_checkout`
Redirige al checkout para completar la compra (con confirmación).

**Ejemplo:**
```javascript
proceed_to_checkout({})
```

## Uso con Agentes de IA

Una vez instalado y configurado, los agentes de IA compatibles con WebMCP podrán interactuar con tu tienda WooCommerce. Por ejemplo:

**Usuario:** "Busca camisetas rojas"
**Agente:** *Llama a `search_products({ query: "camiseta roja" })`*

**Usuario:** "Añade la primera al carrito"
**Agente:** *Llama a `add_to_cart({ product_id: 123 })` y solicita confirmación*

**Usuario:** "Procede al checkout"
**Agente:** *Llama a `proceed_to_checkout()` y redirige al usuario*

## API REST Endpoints

El plugin crea los siguientes endpoints REST:

```
GET  /wp-json/webmcp/v1/products/search
GET  /wp-json/webmcp/v1/products/{id}
GET  /wp-json/webmcp/v1/products/categories
POST /wp-json/webmcp/v1/cart/add
GET  /wp-json/webmcp/v1/cart
POST /wp-json/webmcp/v1/cart/update
POST /wp-json/webmcp/v1/cart/remove
GET  /wp-json/webmcp/v1/checkout/url
```

## Probar WebMCP

Para verificar que WebMCP está funcionando:

1. Abre la consola de desarrollador (F12)
2. Ejecuta: `console.log(window.navigator.modelContext)`
3. Si ves un objeto, WebMCP está disponible
4. Verifica que las herramientas están registradas:
   ```javascript
   console.log('WebMCP cargado correctamente')
   ```

## Seguridad

- ✅ Todas las acciones destructivas (añadir al carrito, eliminar, checkout) requieren confirmación del usuario
- ✅ Usa WordPress nonces para validar peticiones
- ✅ Respeta permisos y capacidades de WooCommerce
- ✅ No expone datos sensibles de usuarios o pedidos

## Desarrollo

### Estructura del proyecto

```
webmcp-wp/
├── webmcp-wp.php                    # Plugin principal
├── includes/
│   ├── class-webmcp-api.php         # API REST endpoints
│   └── class-webmcp-admin.php       # Página de administración
├── assets/
│   └── js/
│       └── webmcp-woocommerce.js    # Registro de herramientas WebMCP
└── README.md
```

### Extender el plugin

Puedes añadir tus propias herramientas WebMCP:

```javascript
// En tu propio archivo JS
if ('modelContext' in window.navigator) {
    window.navigator.modelContext.registerTool({
        name: "mi_herramienta",
        description: "Descripción de mi herramienta",
        inputSchema: {
            type: "object",
            properties: {
                param1: { type: "string", description: "Parámetro 1" }
            },
            required: ["param1"]
        },
        async execute(params, agent) {
            // Tu lógica aquí
            return {
                content: [
                    { type: "text", text: "Resultado" }
                ]
            };
        }
    });
}
```

## Recursos

- [WebMCP Official Site](https://webmcp.dev/)
- [WebMCP Specification](https://github.com/webmachinelearning/webmcp)
- [WooCommerce REST API](https://woocommerce.github.io/woocommerce-rest-api-docs/)

## Licencia

GPL v2 or later

## Changelog

### 1.0.0 (2026-02-13)
- ✨ Lanzamiento inicial
- ✅ 8 herramientas WebMCP implementadas
- ✅ API REST completa para WooCommerce
- ✅ Confirmaciones de usuario para acciones importantes
- ✅ Panel de administración

## Soporte

Para reportar bugs o solicitar funcionalidades, abre un issue en el repositorio.

---

**Nota:** WebMCP es una tecnología experimental actualmente disponible solo en Chrome 146+ con flags experimentales. La API puede cambiar en futuras versiones del navegador.
