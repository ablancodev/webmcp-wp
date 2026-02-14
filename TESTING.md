# 🧪 Guía de Testing y Verificación de WebMCP

## Cómo verificar que WebMCP está funcionando correctamente

### Método 1: En cualquier página de tu sitio WordPress

1. **Abre tu sitio** en Chrome 146+ con WebMCP habilitado
2. **Abre la Consola de Desarrollador** (F12 o Click derecho → Inspeccionar)
3. **Ejecuta estos comandos:**

```javascript
// ✅ Paso 1: Verificar que WebMCP está disponible en el navegador
console.log('modelContext' in window.navigator);
// Debe devolver: true

// ✅ Paso 2: Ver el estado completo del plugin
webmcpDebug.checkStatus();

// ✅ Paso 3: Listar todas las herramientas registradas
webmcpDebug.listTools();

// ✅ Paso 4: Ver toda la información
console.log(webmcpDebug);
```

### Método 2: Usando la página de prueba

1. **Copia el archivo** `test-webmcp.html` a la raíz de tu sitio WordPress
2. **Navega a:** `http://tu-sitio.local/test-webmcp.html`
3. **Verás un dashboard** con:
   - ✅ Estado del navegador
   - ✅ Estado de WebMCP
   - ✅ Lista de herramientas registradas
   - ✅ Botones de prueba funcional

### Método 3: Inspeccionar el código fuente

1. **Abre cualquier página** de tu sitio
2. **Ver código fuente** (Ctrl+U o Click derecho → Ver código fuente)
3. **Busca por:** `webmcp-woocommerce.js`

Deberías ver algo como:

```html
<script src="http://tu-sitio.local/wp-content/plugins/webmcp-wp/assets/js/webmcp-woocommerce.js?ver=1.0.0" id="webmcp-woocommerce-js"></script>
```

4. **También busca:** `webmcpData` para ver los datos pasados al JavaScript:

```html
<script id="webmcp-woocommerce-js-before">
var webmcpData = {
  "ajaxUrl": "http://...",
  "restUrl": "http://.../wp-json/webmcp/v1/",
  "nonce": "...",
  "shopName": "Tu Tienda",
  ...
};
</script>
```

### Método 4: Verificar en la pestaña Network (Red)

1. **Abre DevTools** (F12) → Pestaña **Network**
2. **Recarga la página** (F5)
3. **Filtra por:** `webmcp`
4. Deberías ver:
   - ✅ `webmcp-woocommerce.js` cargado (Status: 200)

### Método 5: Verificar la API REST directamente

Abre en el navegador o usa curl:

```bash
# Listar productos
http://tu-sitio.local/wp-json/webmcp/v1/products/search

# Ver categorías
http://tu-sitio.local/wp-json/webmcp/v1/products/categories

# Ver carrito
http://tu-sitio.local/wp-json/webmcp/v1/cart
```

## ✅ Checklist de Verificación

- [ ] Chrome 146+ instalado
- [ ] Flag experimental activado: `chrome://flags/#enable-experimental-web-platform-features`
- [ ] Plugin WebMCP activado en WordPress
- [ ] WooCommerce activado
- [ ] Al abrir consola, ves mensaje: "✅ WebMCP para WooCommerce registrado correctamente"
- [ ] `window.navigator.modelContext` existe
- [ ] `webmcpDebug` existe y tiene 8 herramientas
- [ ] El script `webmcp-woocommerce.js` se carga en todas las páginas

## 🔍 Comandos útiles de la Consola

```javascript
// Ver si WebMCP está disponible
'modelContext' in window.navigator

// Ver estado completo
webmcpDebug.checkStatus()

// Listar herramientas (debería mostrar 8)
webmcpDebug.listTools()

// Ver información de la tienda
console.table(webmcpDebug.shopInfo)

// Ver todas las herramientas registradas
console.table(webmcpDebug.toolsRegistered)
```

## 🐛 Solución de Problemas

### ❌ "modelContext is not defined"

**Problema:** El navegador no soporta WebMCP

**Solución:**
1. Usa Chrome 146+ o Chrome Canary
2. Activa el flag: `chrome://flags/#enable-experimental-web-platform-features`
3. Reinicia el navegador completamente

### ❌ "webmcpDebug is not defined"

**Problema:** El script del plugin no se cargó

**Solución:**
1. Verifica que el plugin está activado
2. Verifica que WebMCP está habilitado en **WordPress Admin → WebMCP**
3. Limpia la caché del navegador (Ctrl+Shift+R)
4. Verifica que WooCommerce está activo

### ❌ "No tools registered"

**Problema:** Las herramientas no se registraron

**Solución:**
1. Abre la consola y busca errores en rojo
2. Verifica que `window.navigator.modelContext.provideContext` existe
3. Recarga la página

### ❌ Errores 404 en API REST

**Problema:** Los endpoints REST no están disponibles

**Solución:**
1. Ve a **Ajustes → Enlaces permanentes** en WordPress
2. Haz click en "Guardar cambios" (flush rewrite rules)
3. Verifica que puedes acceder a: `http://tu-sitio.local/wp-json/`

## 📊 Output Esperado

Cuando todo funciona correctamente, en la consola deberías ver:

```
🤖 WebMCP para WooCommerce inicializando...
✅ WebMCP para WooCommerce registrado correctamente.
📦 Tienda: Tu Tienda
🛠️ Herramientas disponibles: search_products, get_product, get_categories, add_to_cart, get_cart, update_cart_item, remove_from_cart, proceed_to_checkout
💡 Comandos útiles
  Para ver el estado: webmcpDebug.checkStatus()
  Para listar herramientas: webmcpDebug.listTools()
  Para ver info: webmcpDebug
```

## 🧪 Pruebas Funcionales

Una vez verificado que está cargado, puedes probar manualmente en la consola:

```javascript
// Ejemplo: Buscar productos (solo si tienes acceso al agent parameter)
// Nota: Normalmente esto lo hace un agente de IA, no manualmente

// Verificar que las herramientas existen
console.log(webmcpDebug.toolsRegistered.includes('search_products')); // true
```

Para pruebas completas, usa la **página de test** (`test-webmcp.html`) que incluye botones para probar cada funcionalidad.
