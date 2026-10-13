<?php
/**
 * LLMs.txt Support (AI Engine Optimization)
 */
if (!defined('ABSPATH')) exit;

if (false) {
    require_once dirname(__DIR__) . '/_wp_stubs.php';
}

class VonSEOWP_LLM {

    public function __construct() {
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'render_llms_txt'), 0);
    }

    /**
     * Add rewrite rule for /llms.txt
     */
    public function add_rewrite_rules(): void {
        add_rewrite_rule('llms\.txt$', 'index.php?vonseowp_llm=1', 'top');
    }

    /**
     * Register query var
     */
    public function add_query_vars(array $vars): array {
        $vars[] = 'vonseowp_llm';
        return $vars;
    }

    /**
     * Render the llms.txt content
     */
    public function render_llms_txt(): void {
        if (get_query_var('vonseowp_llm')) {
            $options = get_option('vonseowp_settings', array());
            $options = is_array($options) ? $options : array();
            $enabled = isset($options['enable_llms_txt']) ? (bool)$options['enable_llms_txt'] : true;

            if (!$enabled || !get_option('blog_public')) {
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                nocache_headers();
                header('X-Robots-Tag: noindex, nofollow');
                include(get_query_template('404'));
                exit;
            }

            $content = !empty($options['llms_txt_content']) ? $options['llms_txt_content'] : $this->get_default_template();

            header('Content-Type: text/plain; charset=utf-8');
            header('X-Robots-Tag: index, follow');
            header('X-Content-Type-Options: nosniff');
            nocache_headers();
            
            // Escaping with wp_kses_post for security audit compliance
            echo wp_kses_post($content);
            exit;
        }
    }

    /**
     * Get default llms.txt template
     */
    private function get_default_template(): string {
        $site_name = get_bloginfo('name');
        $site_desc = get_bloginfo('description');
        $home_url  = home_url('/');
        $sitemap   = home_url('/sitemap.xml');

        $template = "# {$site_name}\n\n";
        $template .= "> {$site_desc}\n\n";
        $template .= "## " . __('Key Resources', 'vonseo') . "\n\n";
        $template .= "- [" . __('Home Page', 'vonseo') . "]({$home_url}): " . __('Main entry point for the website.', 'vonseo') . "\n";
        $template .= "- [" . __('XML Sitemap', 'vonseo') . "]({$sitemap}): " . __('Detailed index of all public pages for crawling.', 'vonseo') . "\n\n";
        $template .= "## " . __('About', 'vonseo') . "\n\n";
        $template .= __('This file provides a curated summary of site content for search crawlers and specialized data agents.', 'vonseo');

        return apply_filters('vonseowp_llms_txt_default_template', $template);
    }
}
