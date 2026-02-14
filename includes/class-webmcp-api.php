<?php
/**
 * WebMCP API - Endpoints REST para las herramientas WebMCP
 */

if (!defined('ABSPATH')) {
    exit;
}

class WebMCP_API {

    private $namespace = 'webmcp/v1';

    public function __construct() {
        // Asegurar que WooCommerce inicie la sesión para el carrito
        add_action('rest_api_init', array($this, 'init_wc_session'), 9);
    }

    /**
     * Inicializar sesión de WooCommerce para endpoints REST
     */
    public function init_wc_session() {
        if (is_null(WC()->session)) {
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
        }

        if (is_null(WC()->cart)) {
            WC()->cart = new WC_Cart();
        }

        if (is_null(WC()->customer)) {
            WC()->customer = new WC_Customer(get_current_user_id(), true);
        }
    }

    public function register_routes() {
        // Productos (público, sin autenticación)
        register_rest_route($this->namespace, '/products/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'search_products'),
            'permission_callback' => '__return_true',
            'args' => array(
                'query' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Término de búsqueda',
                ),
                'category' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Slug de categoría',
                ),
                'min_price' => array(
                    'required' => false,
                    'type' => 'number',
                    'description' => 'Precio mínimo',
                ),
                'max_price' => array(
                    'required' => false,
                    'type' => 'number',
                    'description' => 'Precio máximo',
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 10,
                    'description' => 'Número máximo de resultados',
                ),
            ),
        ));

        register_rest_route($this->namespace, '/products/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_product'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route($this->namespace, '/products/categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_categories'),
            'permission_callback' => '__return_true',
        ));

        // Carrito
        register_rest_route($this->namespace, '/cart/add', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_to_cart'),
            'permission_callback' => '__return_true',
            'args' => array(
                'product_id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
                'quantity' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 1,
                ),
            ),
        ));

        register_rest_route($this->namespace, '/cart', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_cart'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route($this->namespace, '/cart/update', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_cart_item'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route($this->namespace, '/cart/remove', array(
            'methods' => 'POST',
            'callback' => array($this, 'remove_from_cart'),
            'permission_callback' => '__return_true',
        ));

        // Checkout
        register_rest_route($this->namespace, '/checkout/url', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_checkout_url'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Buscar productos
     */
    public function search_products($request) {
        $query_args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $request->get_param('limit') ?: 10,
        );

        // Búsqueda por texto
        if ($query = $request->get_param('query')) {
            $query_args['s'] = sanitize_text_field($query);
        }

        // Filtro por categoría
        if ($category = $request->get_param('category')) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($category),
                ),
            );
        }

        // Filtro por precio
        $min_price = $request->get_param('min_price');
        $max_price = $request->get_param('max_price');

        if ($min_price || $max_price) {
            $query_args['meta_query'] = array();

            if ($min_price) {
                $query_args['meta_query'][] = array(
                    'key' => '_price',
                    'value' => floatval($min_price),
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                );
            }

            if ($max_price) {
                $query_args['meta_query'][] = array(
                    'key' => '_price',
                    'value' => floatval($max_price),
                    'compare' => '<=',
                    'type' => 'NUMERIC',
                );
            }
        }

        $query = new WP_Query($query_args);
        $products = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());

                $products[] = $this->format_product($product);
            }
            wp_reset_postdata();
        }

        return rest_ensure_response(array(
            'success' => true,
            'products' => $products,
            'total' => $query->found_posts,
        ));
    }

    /**
     * Obtener un producto específico
     */
    public function get_product($request) {
        $product_id = $request->get_param('id');
        $product = wc_get_product($product_id);

        if (!$product) {
            return new WP_Error('product_not_found', 'Producto no encontrado', array('status' => 404));
        }

        return rest_ensure_response(array(
            'success' => true,
            'product' => $this->format_product($product, true),
        ));
    }

    /**
     * Obtener categorías de productos
     */
    public function get_categories($request) {
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
        ));

        $formatted_categories = array();
        foreach ($categories as $category) {
            $formatted_categories[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->count,
                'description' => $category->description,
            );
        }

        return rest_ensure_response(array(
            'success' => true,
            'categories' => $formatted_categories,
        ));
    }

    /**
     * Añadir producto al carrito
     */
    public function add_to_cart($request) {
        $product_id = $request->get_param('product_id');
        $quantity = $request->get_param('quantity') ?: 1;

        // Verificar que el producto existe
        $product = wc_get_product($product_id);
        if (!$product) {
            return new WP_Error('product_not_found', 'Producto no encontrado', array('status' => 404));
        }

        // Añadir al carrito
        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);

        if ($cart_item_key) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Producto añadido al carrito',
                'cart_item_key' => $cart_item_key,
                'cart' => $this->get_cart_data(),
            ));
        } else {
            return new WP_Error('cart_error', 'No se pudo añadir el producto al carrito', array('status' => 400));
        }
    }

    /**
     * Obtener contenido del carrito
     */
    public function get_cart($request) {
        return rest_ensure_response(array(
            'success' => true,
            'cart' => $this->get_cart_data(),
        ));
    }

    /**
     * Actualizar cantidad de un item del carrito
     */
    public function update_cart_item($request) {
        $cart_item_key = $request->get_param('cart_item_key');
        $quantity = $request->get_param('quantity');

        if (WC()->cart->set_quantity($cart_item_key, $quantity)) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Carrito actualizado',
                'cart' => $this->get_cart_data(),
            ));
        }

        return new WP_Error('cart_error', 'No se pudo actualizar el carrito', array('status' => 400));
    }

    /**
     * Eliminar producto del carrito
     */
    public function remove_from_cart($request) {
        $cart_item_key = $request->get_param('cart_item_key');

        if (WC()->cart->remove_cart_item($cart_item_key)) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Producto eliminado del carrito',
                'cart' => $this->get_cart_data(),
            ));
        }

        return new WP_Error('cart_error', 'No se pudo eliminar el producto', array('status' => 400));
    }

    /**
     * Obtener URL de checkout
     */
    public function get_checkout_url($request) {
        return rest_ensure_response(array(
            'success' => true,
            'checkout_url' => wc_get_checkout_url(),
            'cart' => $this->get_cart_data(),
        ));
    }

    /**
     * Formatear datos del producto
     */
    private function format_product($product, $detailed = false) {
        $data = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'permalink' => get_permalink($product->get_id()),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'price_html' => $product->get_price_html(),
            'on_sale' => $product->is_on_sale(),
            'in_stock' => $product->is_in_stock(),
            'stock_quantity' => $product->get_stock_quantity(),
            'short_description' => $product->get_short_description(),
            'image' => wp_get_attachment_image_url($product->get_image_id(), 'medium'),
            'categories' => $this->get_product_categories($product->get_id()),
        );

        if ($detailed) {
            $data['description'] = $product->get_description();
            $data['images'] = $this->get_product_images($product);
            $data['attributes'] = $product->get_attributes();
            $data['dimensions'] = array(
                'length' => $product->get_length(),
                'width' => $product->get_width(),
                'height' => $product->get_height(),
                'weight' => $product->get_weight(),
            );
        }

        return $data;
    }

    /**
     * Obtener categorías de un producto
     */
    private function get_product_categories($product_id) {
        $terms = get_the_terms($product_id, 'product_cat');
        if (!$terms || is_wp_error($terms)) {
            return array();
        }

        $categories = array();
        foreach ($terms as $term) {
            $categories[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            );
        }

        return $categories;
    }

    /**
     * Obtener imágenes del producto
     */
    private function get_product_images($product) {
        $images = array();

        // Imagen principal
        if ($product->get_image_id()) {
            $images[] = wp_get_attachment_image_url($product->get_image_id(), 'large');
        }

        // Galería
        $gallery_ids = $product->get_gallery_image_ids();
        foreach ($gallery_ids as $image_id) {
            $images[] = wp_get_attachment_image_url($image_id, 'large');
        }

        return $images;
    }

    /**
     * Obtener datos del carrito
     */
    private function get_cart_data() {
        $cart_items = array();

        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];

            $cart_items[] = array(
                'cart_item_key' => $cart_item_key,
                'product_id' => $cart_item['product_id'],
                'product_name' => $product->get_name(),
                'quantity' => $cart_item['quantity'],
                'price' => $product->get_price(),
                'line_total' => $cart_item['line_total'],
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
            );
        }

        return array(
            'items' => $cart_items,
            'subtotal' => WC()->cart->get_subtotal(),
            'total' => WC()->cart->get_total('raw'),
            'item_count' => WC()->cart->get_cart_contents_count(),
            'currency' => get_woocommerce_currency(),
            'currency_symbol' => get_woocommerce_currency_symbol(),
        );
    }
}
