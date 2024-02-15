<?php
/**
 * Plugin Name: WP FAQ Section Creator
 * Description: Ce plugin crée automatiquement une section FAQ dans WordPress, permettant d'entrer des questions et réponses sous forme de posts, avec la possibilité de créer et d'associer des catégories à ces Q/R.
 * Version: 1.0
 * Author: Targetweb
 * Text Domain: wp-faq-section-creator
 */

 function wp_faq_section_creator_check_algolia_dependency() {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    if (!is_plugin_active('algolia-search/algolia-search.php')) {
        add_action('admin_notices', 'wp_faq_section_creator_algolia_admin_notice');
    }
}
add_action('admin_init', 'wp_faq_section_creator_check_algolia_dependency');

function wp_faq_section_creator_algolia_admin_notice(){
    ?>
    <div class="notice notice-warning is-dismissible">
        <p><?php _e('Pour que le plugin WP FAQ Section Creator fonctionne correctement, l\'extension <strong>Algolia Search</strong> doit être activée et configurée.', 'wp-faq-section-creator'); ?></p>
    </div>
    <?php
}

if (!function_exists('create_faq_post_type')) {
    function create_faq_post_type() {
        register_post_type('faq',
            array(
                'labels' => array(
                    'name' => __('FAQs', 'wp-faq-section-creator'),
                    'singular_name' => __('FAQ', 'wp-faq-section-creator'),
                ),
                'public' => true,
                'has_archive' => true,
                'rewrite' => array('slug' => 'faqs'),
                'show_in_rest' => true, // Pour Gutenberg editor
                'menu_icon' => 'dashicons-editor-help', // Utilise un Dashicon WordPress
                'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'comments'),
                'taxonomies' => array('category'), // Pour prendre en charge les catégories
            )
        );
    }
    add_action('init', 'create_faq_post_type');
}

if (!function_exists('add_faq_taxonomies')) {
    function add_faq_taxonomies() {
        register_taxonomy_for_object_type('category', 'faq');
    }
    add_action('init', 'add_faq_taxonomies');
}

if (!function_exists('add_faq_to_category_archives')) {
    function add_faq_to_category_archives($query) {
        if (!is_admin() && $query->is_main_query() && (is_category() || is_tag())) {
            $query->set('post_type', array('post', 'faq')); // Inclut les FAQs dans les archives de catégories/tags
        }
    }
    add_action('pre_get_posts', 'add_faq_to_category_archives');
}

if (!function_exists('refresh_faq_permalinks_on_publish')) {
    function refresh_faq_permalinks_on_publish($post_id, $post) {
        if ('faq' === $post->post_type) {
            flush_rewrite_rules();
        }
    }
    add_action('wp_insert_post', 'refresh_faq_permalinks_on_publish', 10, 2);
}

register_activation_hook(__FILE__, 'wp_faq_create_algolia_folder_and_file');

function wp_faq_create_algolia_folder_and_file() {
    $plugin_directory = plugin_dir_path(__FILE__); // Chemin de votre plugin
    $source_file = $plugin_directory . 'custom-instantsearch-template.php'; // Chemin complet du fichier source dans votre plugin

    $theme_directory = get_theme_root() . '/' . get_template(); // Chemin du thème actif
    $algolia_directory = $theme_directory . '/algolia'; // Chemin du dossier algolia
    $target_file_path = $algolia_directory . '/instantsearch.php'; // Chemin du fichier cible dans le dossier algolia

    // Crée le dossier algolia s'il n'existe pas
    if (!file_exists($algolia_directory)) {
        wp_mkdir_p($algolia_directory);
    }

    // Copie le contenu du fichier source vers le fichier cible si le fichier source existe
    if (file_exists($source_file)) {
        $file_content = file_get_contents($source_file);
        file_put_contents($target_file_path, $file_content);
    } else {
        error_log('Le fichier source custom-instantsearch-template.php est introuvable dans le plugin.');
    }
}
