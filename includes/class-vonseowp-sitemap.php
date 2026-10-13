<?php
/**
 * XML Sitemap Generator
 */
if (!defined('ABSPATH')) exit;

class VonSEOWP_Sitemap {

    public function __construct() {
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_filter('wp_sitemaps_enabled', array($this, 'filter_core_sitemaps'));
        add_action('template_redirect', array($this, 'render_sitemap'));
        add_action('vonseowp_flush_sitemap', 'flush_rewrite_rules');
    }

    public function add_rewrite_rules(): void {
        add_rewrite_rule('sitemap\.xml$', 'index.php?vonseowp_sitemap=1', 'top');
        add_rewrite_rule('sitemap_index\.xml$', 'index.php?vonseowp_sitemap=1', 'top');
    }

    public function add_query_vars(array $vars): array {
        $vars[] = 'vonseowp_sitemap';
        $vars[] = 'vonseowp_sitemap_page';
        return $vars;
    }

    public function filter_core_sitemaps(bool $enabled): bool {
        $options = get_option('vonseowp_settings', array());
        $vonseo_enabled = !isset($options['enable_sitemap']) || (int) $options['enable_sitemap'] === 1;
        return $vonseo_enabled ? false : $enabled;
    }

    public function render_sitemap(): void {
        if (!get_query_var('vonseowp_sitemap')) {
            return;
        }

        $options = get_option('vonseowp_settings', array());
        $options = is_array($options) ? $options : array();
        $enabled = isset($options['enable_sitemap']) ? (bool) $options['enable_sitemap'] : true;

        if (!$enabled) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
            include(get_query_template('404'));
            exit;
        }

        $paged = get_query_var('vonseowp_sitemap_page');
        $total_posts = $this->get_total_entries();
        if ($paged !== '' && ((!is_string($paged) && !is_int($paged)) || !ctype_digit((string) $paged)
            || (int) $paged < 1 || (int) $paged > max(1, (int) ceil($total_posts / 1000)))) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
            exit;
        }
        
        // If no page is specified, check if we need an index
        if ($paged === '') {
            if ($total_posts > 1000) {
                $this->output_index($total_posts);
                exit;
            }
        }

        $this->output_xml($options);
        exit;
    }

    private function get_total_entries(): int {
        if (!get_option('blog_public')) return 0;
        $options = get_option('vonseowp_settings', array());
        $options = is_array($options) ? $options : array();
        $count = $this->include_homepage() ? 1 : 0;
        $args = $this->get_content_query($options);
        if (!empty($args['post_type'])) {
            $args['fields'] = 'ids';
            $args['posts_per_page'] = 1;
            $args['no_found_rows'] = false;
            $query = new WP_Query($args);
            $count += (int) $query->found_posts;
        }
        return $count;
    }

    private function include_homepage(): bool {
        if (!get_option('blog_public')) return false;
        if (get_option('show_on_front') !== 'page') return true;
        $front = get_post((int) get_option('page_on_front'));
        return $front && $front->post_status === 'publish' && $front->post_password === ''
            && get_post_meta($front->ID, '_vonseowp_noindex', true) !== '1';
    }

    private function get_content_query(array $options): array {
        $args = array(
            'post_type' => VonSEOWP_Content_Types::get_sitemap_types($options),
            'post_status' => 'publish',
            'has_password' => false,
            // Filter before pagination; only this plugin's noindex key is queried.
            'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required indexability filter; bounded sitemap output, ID-only count query, no custom storage.
                'relation' => 'OR',
                array('key' => '_vonseowp_noindex', 'compare' => 'NOT EXISTS'),
                array('key' => '_vonseowp_noindex', 'value' => '1', 'compare' => '!='),
            ),
            'orderby' => array('modified' => 'DESC', 'ID' => 'DESC'),
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
        );
        if (get_option('show_on_front') === 'page') {
            $args['post__not_in'] = array((int) get_option('page_on_front')); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Exactly one static homepage ID; prevents duplicate URLs and counts.
        }
        return $args;
    }

    private function output_index(int $total): void {
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        $pages = ceil($total / 1000);
        $base_url = home_url('/sitemap.xml');

        for ($i = 1; $i <= $pages; $i++) {
            echo '<sitemap>';
            echo '<loc>' . esc_url(add_query_arg('vonseowp_sitemap_page', $i, $base_url)) . '</loc>';
            echo '<lastmod>' . esc_html(gmdate('c')) . '</lastmod>';
            echo '</sitemap>';
        }

        echo '</sitemapindex>';
    }

    private function output_xml(array $options = array()): void {
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        if (!get_option('blog_public')) {
            echo '</urlset>';
            return;
        }

        $paged = max(1, (int) get_query_var('vonseowp_sitemap_page', 1));
        $home = $this->include_homepage();
        if ($home && $paged === 1) {
            echo '<url>';
            echo '<loc>' . esc_url(home_url('/')) . '</loc>';
            echo '<priority>1.0</priority>';
            echo '<changefreq>daily</changefreq>';
            echo '</url>';
        }

        $args = $this->get_content_query($options);
        if (empty($args['post_type'])) {
            echo '</urlset>';
            return;
        }
        // The homepage occupies one slot on the first page, not every page.
        $args['posts_per_page'] = ($home && $paged === 1) ? 999 : 1000;
        $args['offset'] = max(0, ($paged - 1) * 1000 - ($home ? 1 : 0));
        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                echo '<url>';
                echo '<loc>' . esc_url(get_permalink()) . '</loc>';
                echo '<lastmod>' . esc_html(get_the_modified_date('c')) . '</lastmod>';
                echo '<priority>0.8</priority>';
                echo '</url>';
            }
            wp_reset_postdata();
        }

        echo '</urlset>';
    }
}
