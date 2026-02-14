<?php
/**
 * Plugin Name: WebMCP for WooCommerce
 * Plugin URI: https://github.com/ablancodev/webmcp-wp
 * Description: WebMCP para WooCommerce - Expone funcionalidades de la tienda a agentes IA mediante WebMCP (navigator.modelContext)
 * Version: 1.0.0
 * Author: ablancodev
 * Author URI: https://ablancodev.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: webmcp-wp
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WEBMCP_VERSION', '1.0.0');
define('WEBMCP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WEBMCP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WEBMCP_PLUGIN_FILE', __FILE__);

/**
 * Verificar que WooCommerce está activo
 */
function webmcp_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>';
            echo __('WebMCP para WooCommerce requiere que WooCommerce esté instalado y activo.', 'webmcp-wp');
            echo '</p></div>';
        });
        return false;
    }
    return true;
}

/**
 * Cargar archivos del plugin
 */
require_once WEBMCP_PLUGIN_DIR . 'includes/class-webmcp-api.php';
require_once WEBMCP_PLUGIN_DIR . 'includes/class-webmcp-admin.php';

/**
 * Clase principal del plugin
 */
class WebMCP_WP {

    private static $instance = null;
    private $api;
    private $admin;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (!webmcp_check_woocommerce()) {
            return;
        }

        // Cargar traducciones
        load_plugin_textdomain('webmcp-wp', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Inicializar componentes
        $this->api = new WebMCP_API();
        $this->admin = new WebMCP_Admin();

        // Enqueue scripts en frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Registrar REST API endpoints
        add_action('rest_api_init', array($this->api, 'register_routes'));

        // Registrar shortcode de debug
        add_shortcode('webmcp_status', array($this, 'shortcode_status'));
    }

    /**
     * Shortcode para mostrar el estado de WebMCP
     * Uso: [webmcp_status]
     */
    public function shortcode_status($atts) {
        ob_start();
        ?>
        <div class="webmcp-status-widget" style="background: #f8f9fa; border: 2px solid #dee2e6; border-radius: 8px; padding: 20px; margin: 20px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
            <h3 style="margin-top: 0; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;">
                🤖 Estado de WebMCP
            </h3>

            <div style="display: grid; gap: 15px;">
                <div>
                    <strong>Plugin WebMCP:</strong>
                    <span style="color: #27ae60; font-weight: bold;">✅ Activo</span>
                </div>

                <div>
                    <strong>Versión:</strong>
                    <code><?php echo esc_html(WEBMCP_VERSION); ?></code>
                </div>

                <div>
                    <strong>Tienda:</strong>
                    <?php echo esc_html(get_bloginfo('name')); ?>
                </div>

                <div>
                    <strong>Herramientas registradas:</strong>
                    <span style="background: #e3f2fd; padding: 2px 8px; border-radius: 3px; font-weight: bold;">8 tools</span>
                </div>

                <div>
                    <strong>API REST:</strong>
                    <code><?php echo esc_url(rest_url('webmcp/v1/')); ?></code>
                </div>

                <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 10px;">
                    <strong>📋 Para verificar en el navegador:</strong>
                    <ol style="margin: 10px 0 0 20px; padding: 0;">
                        <li>Abre la consola de desarrollador (F12)</li>
                        <li>Ejecuta: <code style="background: #2c3e50; color: #ecf0f1; padding: 2px 6px; border-radius: 3px;">webmcpDebug.checkStatus()</code></li>
                        <li>Para listar herramientas: <code style="background: #2c3e50; color: #ecf0f1; padding: 2px 6px; border-radius: 3px;">webmcpDebug.listTools()</code></li>
                    </ol>
                </div>

                <div style="background: #d1ecf1; padding: 15px; border-radius: 5px; border-left: 4px solid #0c5460;">
                    <strong>⚠️ Requisitos del navegador:</strong>
                    <ul style="margin: 10px 0 0 20px; padding: 0;">
                        <li>Chrome 146+ o Chrome Canary</li>
                        <li>Activar flag experimental: <code>chrome://flags/#enable-experimental-web-platform-features</code></li>
                    </ul>
                </div>

                <div style="margin-top: 15px;">
                    <button onclick="if(typeof webmcpDebug !== 'undefined') { webmcpDebug.checkStatus(); alert('Revisa la consola (F12) para ver el estado completo'); } else { alert('WebMCP no está cargado. Verifica que el navegador soporte WebMCP.'); }"
                            style="background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px;">
                        🔍 Verificar Estado en Consola
                    </button>
                </div>
            </div>
        </div>

        <script>
            console.log('%c📊 Shortcode [webmcp_status] cargado', 'color: #3498db; font-weight: bold');
        </script>
        <?php
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        // Solo cargar si WebMCP está habilitado
        if (!get_option('webmcp_enabled', 1)) {
            return;
        }

        // Enqueue el script principal de WebMCP
        wp_enqueue_script(
            'webmcp-woocommerce',
            WEBMCP_PLUGIN_URL . 'assets/js/webmcp-woocommerce.js',
            array(),
            WEBMCP_VERSION,
            true
        );

        // Pasar datos a JavaScript
        wp_localize_script('webmcp-woocommerce', 'webmcpData', array(
            'restUrl' => rest_url('webmcp/v1/'),
            'shopName' => get_bloginfo('name'),
            'shopUrl' => home_url(),
            'currency' => get_woocommerce_currency(),
            'currencySymbol' => get_woocommerce_currency_symbol(),
        ));
    }

    public function activate() {
        // Verificar requisitos
        if (!webmcp_check_woocommerce()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('Este plugin requiere WooCommerce. Por favor instala y activa WooCommerce primero.', 'webmcp-wp'));
        }

        // Crear opciones por defecto
        add_option('webmcp_enabled', 1);

        // Flush rewrite rules para REST API
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

// Inicializar el plugin
function webmcp_wp() {
    return WebMCP_WP::get_instance();
}

// Arrancar
webmcp_wp();
