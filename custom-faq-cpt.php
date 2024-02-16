<?php
/**
 * Plugin Name: WP FAQ Section Creator
 * Description: Ce plugin crée automatiquement une section FAQ dans WordPress, permettant d'entrer des questions et réponses sous forme de posts, avec la possibilité de créer et d'associer des catégories à ces Q/R.
 * Version: 1.0
 * Author: Targetweb
 * Text Domain: wp-faq-section-creator
 */

 
//Permet de créer un type de post pour les FAQs
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
                'menu_icon' => 'dashicons-editor-help',
                'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'comments'),
                'taxonomies' => array('category'), // Pour prendre en charge les catégories
            )
        );
    }
    add_action('init', 'create_faq_post_type');
}

//Permet de créer une taxonomie pour les FAQs
if (!function_exists('add_faq_taxonomies')) {
    function add_faq_taxonomies() {
        register_taxonomy_for_object_type('category', 'faq');
    }
    add_action('init', 'add_faq_taxonomies');
}

//Permet de créer une page d'archive pour les FAQs
if (!function_exists('add_faq_to_category_archives')) {
    function add_faq_to_category_archives($query) {
        if (!is_admin() && $query->is_main_query() && (is_category() || is_tag())) {
            $query->set('post_type', array('post', 'faq')); // Inclut les FAQs dans les archives de catégories/tags
        }
    }
    add_action('pre_get_posts', 'add_faq_to_category_archives');
}


//Permet de refresh les permalinks a chaque création d'une nouvelle FAQ
if (!function_exists('refresh_faq_permalinks_on_publish')) {
    function refresh_faq_permalinks_on_publish($post_id, $post) {
        if ('faq' === $post->post_type) {
            flush_rewrite_rules();
        }
    }
    add_action('wp_insert_post', 'refresh_faq_permalinks_on_publish', 10, 2);
}

register_activation_hook(__FILE__, 'wp_faq_create_algolia_folder_and_file');

// Crée un dossier algolia et un fichier instantsearch.php dans le thème actif pour personnaliser l'affichage des résultats de recherche
function wp_faq_create_algolia_folder_and_file() {
    $plugin_directory = plugin_dir_path(__FILE__); // Chemin du plugin
    $source_file = $plugin_directory . 'custom-instantsearch-template.php'; // Chemin complet du fichier source dans le plugin

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

// Ajoute un menu dans le tableau de bord pour accéder au tableau de bord du plugin
function wp_faq_section_creator_add_admin_menu() {
    add_menu_page(
        __('WP FAQ Dashboard', 'wp-faq-section-creator'),
        __('WP FAQ', 'wp-faq-section-creator'), 
        'manage_options',
        'wp-faq-section-creator-dashboard', 
        'wp_faq_section_creator_dashboard_page', 
        'dashicons-welcome-learn-more', 
        
    );
}

// Affiche le contenu de la page du tableau de bord du plugin
function wp_faq_section_creator_dashboard_page() {
    global $pagenow;

    // S'assurer que les notifications ne s'affichent que sur la page du tableau de bord de votre plugin.
    if ($pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == 'wp-faq-section-creator-dashboard') {
        if (is_plugin_active('wp-search-with-algolia/algolia.php')) {
            $application_id = get_option('algolia_application_id');
            $search_api_key = get_option('algolia_search_api_key');
            $admin_api_key = get_option('algolia_api_key');

            if (!empty($application_id) && !empty($search_api_key) && !empty($admin_api_key)) {
                echo '<div class="notice notice-success is-dismissible"><p>Vous pouvez utiliser toutes les fonctionnalités du plugin, car Algolia Search est bien installé et configuré sur votre site.</p></div>';
            } else {
                echo '<div class="notice notice-warning is-dismissible"><p>Algolia Search est installé mais n\'est pas correctement configuré. Veuillez entrer votre Application ID, votre Search-Only API Key et votre Admin API Key dans les réglages d\'Algolia.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Algolia Search n\'est pas installé ou activé. Veuillez installer et activer Algolia Search pour utiliser toutes les fonctionnalités du plugin.</p></div>';
        }
    } ?>

    <div>
        <p>Configurez les identifiants de votre compte Algolia. Vous pouvez les trouver dans la <a href="https://www.algolia.com/account/api-keys/all" target="_blank">section Clés API</a> de votre tableau de bord Algolia.</p>
        <p>Une fois que vous avez fourni votre ID d'application Algolia et votre clé API, ce plugin pourra communiquer en toute sécurité avec les serveurs Algolia.</p>
        <p>Pas encore de compte Algolia ? <a href="https://www.algolia.com/users/sign_up">Suivez ce lien</a> pour en créer un gratuitement en quelques minutes !</p>
    </div>

    <div>
        <h1 style="color: #333;">Bienvenue dans le Dashboard de WP FAQ Section Creator</h1>
        <p style="color: #555; font-size: 16px;">
            Cette extension est conçue pour optimiser la gestion de vos sections FAQ, en tirant parti des capacités avancées du plugin <strong>Algolia Search</strong>. En intégrant notre plugin avec Algolia Search, vous bénéficiez d'une solution de recherche puissante directement intégrée à votre site WordPress.
        </p>
        <p style="color: #555; font-size: 16px;">
            Notre plugin enrichit votre site d'une gestion dynamique des Q/R (Questions/Réponses), facilitant ainsi la publication, l'organisation et l'affichage des contenus de la FAQ.
        </p>
        <p style="color: #555; font-size: 16px;">
            Pour activer et profiter pleinement des fonctionnalités de recherche avancée offertes par Algolia, vous devez d'abord créer un compte sur la plateforme Algolia. L'inscription est <strong>gratuite</strong> et vous permettra d'obtenir les clés d'identification nécessaires à la configuration du plugin Algolia Search sur votre site WordPress.
        </p>
        <p style="color: #555; font-size: 16px;">
            Une fois vos clés récupérées, suivez les instructions fournies pour configurer correctement le plugin Algolia Search et lier votre compte Algolia à votre site. Cette étape cruciale assure une intégration fluide entre les deux plugins, garantissant ainsi une expérience de recherche exceptionnelle pour vos utilisateurs.
        </p>
        <p style="color: #555; font-size: 16px;">
        Pour intégrer une solution de recherche avancée sur votre site, l'utilisation de la barre de recherche standard fournie par WordPress est tout à fait suffisante.    </p>
        <p style="color: #555; font-size: 16px;">
        Pour commencer à enrichir votre section FAQ, vous n'avez qu'à créer vos contenus de questions et réponses directement depuis la nouvelle section "FAQs", qui a été ajoutée à votre tableau de bord WordPress.
        </p>
    </div>

    <?php
}

// Ajoute une entrée de menu dans le tableau de bord pour accéder au tableau de bord du plugin
add_action('admin_menu', 'wp_faq_section_creator_add_admin_menu');

