# 🔧 Solución de Problemas - WebMCP

## Cambios Realizados para Solucionar Problemas de Nonce y Sesión

### ✅ Problema 1: Nonces Innecesarios
**Antes:** El código enviaba nonces de WordPress que no se validaban
**Ahora:** Eliminados los nonces. Los endpoints son públicos y usan cookies de sesión de WooCommerce

### ✅ Problema 2: Sesión de WooCommerce no Iniciada
**Antes:** WooCommerce no iniciaba sesión en endpoints REST
**Ahora:** `init_wc_session()` asegura que WooCommerce inicie sesión, carrito y cliente

### ✅ Problema 3: Credenciales no Incluidas
**Antes:** Fetch no incluía cookies automáticamente
**Ahora:** `credentials: 'same-origin'` incluye cookies de sesión de WooCommerce

## 🧪 Cómo Probar

### Opción 1: Página de Test de Endpoints

1. **Copia el archivo de prueba:**
   ```bash
   cp test-endpoints.html /Applications/XAMPP/xamppfiles/htdocs/woo/
   ```

2. **Abre en el navegador:**
   ```
   http://localhost/woo/test-endpoints.html
   ```

3. **Prueba cada endpoint:**
   - Buscar productos
   - Ver producto por ID
   - Ver carrito
   - Añadir al carrito

### Opción 2: Probar Directamente en el Navegador

1. **Abre tu sitio WooCommerce**

2. **Abre la consola (F12)**

3. **Prueba los endpoints manualmente:**

```javascript
// Verificar que webmcpDebug existe
console.log(webmcpDebug);

// Buscar productos
fetch(webmcpDebug.shopInfo.restUrl + 'products/search?limit=5', {
    credentials: 'include'
})
.then(r => r.json())
.then(d => console.log('Productos:', d));

// Ver carrito
fetch(webmcpDebug.shopInfo.restUrl + 'cart', {
    credentials: 'include'
})
.then(r => r.json())
.then(d => console.log('Carrito:', d));

// Ver categorías
fetch(webmcpDebug.shopInfo.restUrl + 'products/categories', {
    credentials: 'include'
})
.then(r => r.json())
.then(d => console.log('Categorías:', d));
```

### Opción 3: Usar cURL

```bash
# Buscar productos
curl "http://localhost/woo/wp-json/webmcp/v1/products/search?limit=3"

# Ver categorías
curl "http://localhost/woo/wp-json/webmcp/v1/products/categories"

# Ver carrito (requiere cookies de sesión)
curl -c cookies.txt "http://localhost/woo/wp-json/webmcp/v1/cart"

# Añadir al carrito
curl -b cookies.txt -X POST \
  -H "Content-Type: application/json" \
  -d '{"product_id":1,"quantity":1}' \
  "http://localhost/woo/wp-json/webmcp/v1/cart/add"
```

## 🐛 Errores Comunes y Soluciones

### Error: "rest_no_route"

**Problema:** Los endpoints no están registrados

**Solución:**
1. Ve a **Ajustes → Enlaces permanentes** en WordPress
2. Haz clic en "Guardar cambios" (flush rewrite rules)
3. Verifica que puedes acceder a: `http://localhost/woo/wp-json/`

### Error: "Call to a member function on null"

**Problema:** WooCommerce no está inicializado

**Solución:**
- Verifica que WooCommerce está activo
- Desactiva y reactiva el plugin WebMCP
- Limpia caché de WordPress si usas algún plugin de caché

### Error: "Unexpected token < in JSON"

**Problema:** PHP está devolviendo HTML en lugar de JSON (probablemente un error)

**Solución:**
1. Activa el modo debug de WordPress:
   - Edita `wp-config.php`
   - Cambia `define('WP_DEBUG', false);` a `define('WP_DEBUG', true);`
2. Revisa los logs de errores de PHP
3. Verifica la consola del navegador

### Los productos no se buscan

**Problema:** No hay productos en WooCommerce

**Solución:**
- Crea algunos productos de prueba en WooCommerce
- Asegúrate de que estén publicados (no en borrador)

### El carrito siempre aparece vacío

**Problema:** Las cookies de sesión no se están guardando

**Solución:**
1. Asegúrate de que estás en el mismo dominio (no CORS)
2. Verifica que las cookies están habilitadas en el navegador
3. Comprueba que WooCommerce puede crear sesiones:
   ```php
   // En wp-config.php, NO tengas esto:
   define('WP_CACHE', true); // Puede interferir con sesiones
   ```

### Error 403 o 401

**Problema:** Problema de permisos

**Solución:**
- Los endpoints son públicos (`permission_callback => __return_true`)
- Si sigues viendo 403, verifica que no haya un firewall bloqueando
- Desactiva temporalmente plugins de seguridad

## 🔍 Verificación de Estado

### Check 1: WordPress REST API Funciona

```bash
curl http://localhost/woo/wp-json/
```

Deberías ver JSON con información de WordPress.

### Check 2: Namespace WebMCP Registrado

```bash
curl http://localhost/woo/wp-json/ | grep webmcp
```

Deberías ver rutas que contienen "webmcp/v1".

### Check 3: Endpoint de Productos Funciona

```bash
curl http://localhost/woo/wp-json/webmcp/v1/products/search
```

Deberías ver:
```json
{
  "success": true,
  "products": [...],
  "total": ...
}
```

### Check 4: WooCommerce Session Funciona

En la consola del navegador:
```javascript
fetch(webmcpDebug.shopInfo.restUrl + 'cart', {credentials: 'include'})
  .then(r => r.json())
  .then(d => console.log(d))
```

Deberías ver:
```json
{
  "success": true,
  "cart": {
    "items": [],
    "item_count": 0,
    ...
  }
}
```

## 📝 Logs Útiles

### Activar Debug en WordPress

En `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Los logs se guardarán en: `wp-content/debug.log`

### Ver Errores en la Consola

En Chrome (F12):
- **Console:** Errores de JavaScript
- **Network:** Peticiones HTTP (busca las que van a `/wp-json/webmcp/v1/`)
- **Application → Cookies:** Verifica cookies de WooCommerce (busca `wp_woocommerce_session_`)

## ✅ Checklist de Verificación

- [ ] WordPress funcionando correctamente
- [ ] WooCommerce activo con productos
- [ ] Plugin WebMCP activado
- [ ] Chrome 146+ con flag experimental habilitado
- [ ] Enlaces permanentes guardados (flush rewrite rules)
- [ ] Cookies habilitadas en el navegador
- [ ] `http://localhost/woo/wp-json/` accesible
- [ ] `webmcpDebug` definido en la consola
- [ ] Al menos un producto de prueba en WooCommerce

## 🆘 Última Opción: Reinstalar

Si nada funciona:

1. **Desactiva el plugin**
2. **Borra la carpeta del plugin**
3. **Reinstala el plugin**
4. **Actívalo de nuevo**
5. **Ve a Ajustes → Enlaces permanentes → Guardar cambios**
6. **Prueba de nuevo**

---

¿Sigues teniendo problemas? Revisa:
1. Los logs de error de WordPress (`wp-content/debug.log`)
2. Los logs de error de PHP (XAMPP: `xampp/logs/php_error_log`)
3. La consola del navegador (F12)
