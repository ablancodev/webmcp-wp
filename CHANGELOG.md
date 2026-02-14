# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto se adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [1.0.0] - 2026-02-13

### Añadido
- ✨ Implementación inicial del plugin WebMCP para WooCommerce
- ✅ 8 herramientas WebMCP completamente funcionales:
  - `search_products` - Búsqueda de productos con filtros
  - `get_product` - Detalles de producto
  - `get_categories` - Listado de categorías
  - `add_to_cart` - Añadir al carrito con confirmación
  - `get_cart` - Ver carrito
  - `update_cart_item` - Actualizar cantidad
  - `remove_from_cart` - Eliminar del carrito con confirmación
  - `proceed_to_checkout` - Ir al checkout con confirmación
- 🔌 API REST completa con endpoints para todas las operaciones
- 🎨 Panel de administración en WordPress
- 📚 Documentación completa en README.md
- 🔒 Confirmaciones de usuario para acciones importantes
- ✅ Validación con WordPress nonces
- 🌐 Soporte para múltiples idiomas (i18n ready)

### Características de seguridad
- Confirmación de usuario antes de añadir al carrito
- Confirmación antes de eliminar productos
- Confirmación antes de proceder al checkout
- Validación de nonces en todas las peticiones REST
- Sanitización de todos los inputs

## [Unreleased]

### Planeado
- Soporte para productos variables
- Integración con cupones y descuentos
- Herramientas para gestión de favoritos/wishlist
- Soporte para comparar productos
- Analytics de interacciones con WebMCP
- Tests unitarios y de integración
