<?php
/**
 * WebMCP Admin - Página de administración
 */

if (!defined('ABSPATH')) {
    exit;
}

class WebMCP_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('WebMCP Settings', 'webmcp-wp'),
            __('WebMCP', 'webmcp-wp'),
            'manage_options',
            'webmcp-settings',
            array($this, 'render_settings_page'),
            'dashicons-networking',
            56
        );
    }

    public function register_settings() {
        register_setting('webmcp_options', 'webmcp_enabled', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ));
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Guardar configuración si se envió el formulario
        if (isset($_POST['webmcp_save_settings'])) {
            check_admin_referer('webmcp_settings_save');

            update_option('webmcp_enabled', isset($_POST['webmcp_enabled']) ? 1 : 0);

            echo '<div class="notice notice-success is-dismissible"><p>' .
                 __('Configuración guardada correctamente.', 'webmcp-wp') . '</p></div>';
        }

        $enabled = get_option('webmcp_enabled', 1);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('WebMCP para WooCommerce', 'webmcp-wp'); ?></h1>

            <div class="card" style="max-width: 800px;">
                <h2><?php echo esc_html__('Acerca de WebMCP', 'webmcp-wp'); ?></h2>
                <p>
                    <?php echo esc_html__('WebMCP es un estándar web desarrollado por Google y Microsoft que permite exponer las funcionalidades de tu tienda WooCommerce a agentes de IA mediante la API navigator.modelContext del navegador.', 'webmcp-wp'); ?>
                </p>
                <p>
                    <strong><?php echo esc_html__('Requisitos:', 'webmcp-wp'); ?></strong>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li>Google Chrome 146 o superior</li>
                        <li>Activar flag: <code>chrome://flags/#enable-experimental-web-platform-features</code></li>
                        <li>WooCommerce activo</li>
                    </ul>
                </p>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('webmcp_settings_save'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="webmcp_enabled">
                                <?php echo esc_html__('Activar WebMCP', 'webmcp-wp'); ?>
                            </label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       id="webmcp_enabled"
                                       name="webmcp_enabled"
                                       value="1"
                                       <?php checked($enabled, 1); ?>>
                                <?php echo esc_html__('Habilitar las herramientas WebMCP en el sitio', 'webmcp-wp'); ?>
                            </label>
                            <p class="description">
                                <?php echo esc_html__('Cuando está activado, tu tienda expondrá herramientas para buscar productos, añadir al carrito y realizar compras mediante agentes de IA.', 'webmcp-wp'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Guardar cambios', 'webmcp-wp'), 'primary', 'webmcp_save_settings'); ?>
            </form>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php echo esc_html__('Herramientas disponibles', 'webmcp-wp'); ?></h2>
                <p><?php echo esc_html__('Este plugin expone las siguientes herramientas WebMCP:', 'webmcp-wp'); ?></p>

                <table class="widefat" style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Herramienta', 'webmcp-wp'); ?></th>
                            <th><?php echo esc_html__('Descripción', 'webmcp-wp'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>search_products</code></td>
                            <td><?php echo esc_html__('Buscar productos por texto, categoría y rango de precio', 'webmcp-wp'); ?></td>
                        </tr>
                        <tr class="alternate">
                            <td><code>get_product</code></td>
                            <td><?php echo esc_html__('Obtener detalles completos de un producto específico', 'webmcp-wp'); ?></td>
                        </tr>
                        <tr>
                            <td><code>get_categories</code></td>
                            <td><?php echo esc_html__('Listar todas las categorías de productos', 'webmcp-wp'); ?></td>
                        </tr>
                        <tr class="alternate">
                            <td><code>add_to_cart</code></td>
                            <td><?php echo esc_html__('Añadir un producto al carrito de compra', 'webmcp-wp'); ?></td>
                        </tr>
                        <tr>
                            <td><code>get_cart</code></td>
                            <td><?php echo esc_html__('Ver el contenido actual del carrito', 'webmcp-wp'); ?></td>
                        </tr>
                        <tr class="alternate">
                            <td><code>update_cart_item</code></td>
                            <td><?php echo esc_html__('Actualizar la cantidad de un producto en el carrito', 'webmcp-wp'); ?></td>
                        </tr>
                        <tr>
                            <td><code>remove_from_cart</code></td>
                            <td><?php echo esc_html__('Eliminar un producto del carrito', 'webmcp-wp'); ?></td>
                        </tr>
                        <tr class="alternate">
                            <td><code>get_checkout_url</code></td>
                            <td><?php echo esc_html__('Obtener la URL para proceder al checkout', 'webmcp-wp'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php echo esc_html__('Cómo probar', 'webmcp-wp'); ?></h2>
                <ol>
                    <li><?php echo esc_html__('Asegúrate de tener Chrome 146+ con el flag experimental activado', 'webmcp-wp'); ?></li>
                    <li><?php echo esc_html__('Abre la consola de desarrollador (F12)', 'webmcp-wp'); ?></li>
                    <li><?php echo esc_html__('Ejecuta:', 'webmcp-wp'); ?> <code>console.log(window.navigator.modelContext)</code></li>
                    <li><?php echo esc_html__('Si ves un objeto, WebMCP está disponible en tu navegador', 'webmcp-wp'); ?></li>
                </ol>
            </div>

            <div style="margin-top: 20px; padding: 15px; background: #f0f6fc; border-left: 4px solid #0073aa;">
                <strong><?php echo esc_html__('Documentación:', 'webmcp-wp'); ?></strong>
                <ul style="margin: 10px 0 0 20px;">
                    <li><a href="https://webmcp.dev/" target="_blank">WebMCP Official Site</a></li>
                    <li><a href="https://github.com/webmachinelearning/webmcp" target="_blank">WebMCP Specification on GitHub</a></li>
                </ul>
            </div>
        </div>
        <?php
    }
}
