<?php
if (!defined('ABSPATH')) exit;
if (false) {
    require_once __DIR__ . '/../../_wp_stubs.php';
}
?>
<div class="von-dashboard-wrapper">

    <!-- Title Header -->
    <div class="von-header">
        <div class="von-header-left">
            <div class="von-brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                </svg>
            </div>
            <div class="von-brand-copy">
                <span class="von-logo">VonSEO</span>
                <span class="von-brand-description"><?php esc_html_e('Performance-first SEO tools for fast, clean WordPress publishing.', 'vonseo'); ?></span>
            </div>
        </div>
        <div class="von-header-right">
            <a href="https://github.com/Vondereich" target="_blank" rel="noopener noreferrer" class="von-link-help"><?php esc_html_e('Help & Support', 'vonseo'); ?></a>
        </div>
    </div>

    <div class="von-admin-layout">
        <aside class="von-sidebar">
            <nav class="von-tabs-nav">
                <a href="#tab-general" class="von-tab-link active" onclick="openTab(event, 'tab-general')">
                    <span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e('General', 'vonseo'); ?>
                </a>
                <a href="#tab-social" class="von-tab-link" onclick="openTab(event, 'tab-social')">
                    <span class="dashicons dashicons-share"></span> <?php esc_html_e('Social Media', 'vonseo'); ?>
                </a>
                <a href="#tab-sitemap" class="von-tab-link" onclick="openTab(event, 'tab-sitemap')">
                    <span class="dashicons dashicons-networking"></span> <?php esc_html_e('XML Sitemap', 'vonseo'); ?>
                </a>
                <a href="#tab-redirects" class="von-tab-link" onclick="openTab(event, 'tab-redirects')">
                    <span class="dashicons dashicons-randomize"></span> <?php esc_html_e('Redirects', 'vonseo'); ?>
                </a>
                <a href="#tab-local" class="von-tab-link" onclick="openTab(event, 'tab-local')">
                    <span class="dashicons dashicons-location"></span> <?php esc_html_e('Local SEO', 'vonseo'); ?>
                </a>
                <a href="#tab-image" class="von-tab-link" onclick="openTab(event, 'tab-image')">
                    <span class="dashicons dashicons-format-image"></span> <?php esc_html_e('Image SEO', 'vonseo'); ?>
                </a>
                <a href="#tab-toc" class="von-tab-link" onclick="openTab(event, 'tab-toc')">
                    <span class="dashicons dashicons-editor-ul"></span> <?php esc_html_e('Table of Contents', 'vonseo'); ?>
                </a>
                <a href="#tab-tutorial" class="von-tab-link" onclick="openTab(event, 'tab-tutorial')">
                    <span class="dashicons dashicons-welcome-learn-more"></span> <?php esc_html_e('Tutorial', 'vonseo'); ?>
                </a>
                <a href="#tab-advanced" class="von-tab-link" onclick="openTab(event, 'tab-advanced')">
                    <span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e('Advanced', 'vonseo'); ?>
                </a>
                <a href="#tab-robots" class="von-tab-link" onclick="openTab(event, 'tab-robots')">
                    <span class="dashicons dashicons-index-card"></span> <?php esc_html_e('Robots.txt', 'vonseo'); ?>
                </a>
                <a href="#tab-llm" class="von-tab-link" onclick="openTab(event, 'tab-llm')">
                    <span class="dashicons dashicons-superhero"></span> <?php esc_html_e('Bot Optimization', 'vonseo'); ?>
                </a>
            </nav>

            <!-- Support Card -->
            <div class="von-support-card">
                <div class="von-support-copy">
                    <span class="von-support-kicker"><?php esc_html_e('Independent SEO tool', 'vonseo'); ?></span>
                    <h4><?php esc_html_e('Keep VonSEO Free', 'vonseo'); ?></h4>
                    <p><?php esc_html_e('Support ongoing maintenance and free updates for VonSEO.', 'vonseo'); ?></p>
                </div>
                <div class="von-center-row">
                    <a href="https://paypal.me/kurama87" target="_blank" rel="noopener noreferrer" class="von-btn-donate">
                        <span class="dashicons dashicons-coffee" aria-hidden="true"></span>
                        <span class="von-donate-label"><?php esc_html_e('Buy me a coffee', 'vonseo'); ?></span>
                    </a>
                </div>
            </div>
        </aside>

        <main class="von-main-content">
            <form method="post" action="options.php" class="von-settings-form">
                <?php settings_fields('vonseowp_options_group'); ?>
                <?php
                $vonseowp_options = get_option('vonseowp_settings');
                $vonseowp_indexnow_enabled = isset($vonseowp_options['enable_indexnow']) && in_array($vonseowp_options['enable_indexnow'], array(1, '1', true), true);
                $vonseowp_llms_enabled = !isset($vonseowp_options['enable_llms_txt']) || (int) $vonseowp_options['enable_llms_txt'] === 1;
                ?>

                <!-- Tab: General -->
                <div id="tab-general" class="von-tab-content" style="display: block;">
                    <div class="von-card">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('Site Identity', 'vonseo'); ?></h3>
                            <p class="von-card-desc"><?php esc_html_e('Configure how your site appears in search results.', 'vonseo'); ?></p>
                        </div>

                        <div class="von-card-body">
                            <div class="von-form-group">
                                <label><?php esc_html_e('Separator Character', 'vonseo'); ?></label>
                                <select name="vonseowp_settings[separator]" class="von-input von-select">
                                    <option value="-" <?php selected($vonseowp_options['separator'] ?? '-', '-'); ?>><?php esc_html_e('- (Dash)', 'vonseo'); ?></option>
                                    <option value="|" <?php selected($vonseowp_options['separator'] ?? '-', '|'); ?>><?php esc_html_e('| (Pipe)', 'vonseo'); ?></option>
                                    <option value="•" <?php selected($vonseowp_options['separator'] ?? '-', '•'); ?>><?php esc_html_e('• (Bullet)', 'vonseo'); ?></option>
                                    <option value="—" <?php selected($vonseowp_options['separator'] ?? '-', '—'); ?>><?php esc_html_e('— (Em Dash)', 'vonseo'); ?></option>
                                </select>
                                <div class="von-form-desc"><?php esc_html_e('Character displayed between post title and site name.', 'vonseo'); ?></div>
                            </div>

                            <div class="von-form-group">
                                <div class="label-row">
                                    <label><?php esc_html_e('Site Title', 'vonseo'); ?></label>
                                    <p class="description" style="margin:0; font-size:12px; color:#666;"><?php esc_html_e('Recommended length: ~60 characters.', 'vonseo'); ?></p>
                                </div>
                                <div class="input-wrap">
                                    <input type="text" name="vonseowp_settings[home_title]" id="vonseowp_home_title" value="<?php echo esc_attr($vonseowp_options['home_title'] ?? get_bloginfo('name')); ?>" class="von-input" placeholder="<?php esc_attr_e('My Awesome Site', 'vonseo'); ?>">
                                </div>
                            </div>

                            <div class="von-form-group">
                                <div class="label-row">
                                    <label><?php esc_html_e('Site Tagline', 'vonseo'); ?></label>
                                    <p class="description" style="margin:0; font-size:12px; color:#666;"><?php esc_html_e('Describe your site in 1-2 concise sentences.', 'vonseo'); ?></p>
                                </div>
                                <div class="input-wrap">
                                    <textarea name="vonseowp_settings[home_desc]" id="vonseowp_home_desc" class="von-input" rows="3"><?php echo esc_textarea($vonseowp_options['home_desc'] ?? get_bloginfo('description')); ?></textarea>
                                </div>
                                <div class="von-form-desc"><?php esc_html_e('Synchronized with WordPress General Settings.', 'vonseo'); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="von-card">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('Webmaster Tools', 'vonseo'); ?></h3>
                            <p class="von-card-desc"><?php esc_html_e('Verify your site with Google and Bing.', 'vonseo'); ?></p>
                        </div>
                        <div class="von-card-body">
                            <div class="von-form-group">
                                <label><?php esc_html_e('Google Verification Code', 'vonseo'); ?></label>
                                <input type="text" name="vonseowp_settings[google_verify]" value="<?php echo esc_attr($vonseowp_options['google_verify'] ?? ''); ?>" class="von-input">
                            </div>
                            <div class="von-form-group">
                                <label><?php esc_html_e('Bing Verification Code', 'vonseo'); ?></label>
                                <input type="text" name="vonseowp_settings[bing_verify]" value="<?php echo esc_attr($vonseowp_options['bing_verify'] ?? ''); ?>" class="von-input">
                            </div>
                            <fieldset class="von-form-group">
                                <legend><?php esc_html_e('SEO Content Types', 'vonseo'); ?></legend>
                                <input type="hidden" name="vonseowp_settings[seo_post_types][]" value="">
                                <?php
                                $vonseowp_saved_types = VonSEOWP_Content_Types::get_saved(is_array($vonseowp_options) ? $vonseowp_options : array());
                                $vonseowp_available_types = VonSEOWP_Content_Types::get_available();
                                $vonseowp_type_names = array_unique(array_merge(array_keys($vonseowp_available_types), $vonseowp_saved_types));
                                foreach ($vonseowp_type_names as $vonseowp_type_name) :
                                    $vonseowp_type_label = isset($vonseowp_available_types[$vonseowp_type_name])
                                        ? $vonseowp_available_types[$vonseowp_type_name]->labels->name
                                        : $vonseowp_type_name . ' (' . __('Unavailable', 'vonseo') . ')';
                                    ?>
                                    <label class="von-checkbox-row">
                                        <input type="checkbox" name="vonseowp_settings[seo_post_types][]" value="<?php echo esc_attr($vonseowp_type_name); ?>" <?php checked(in_array($vonseowp_type_name, $vonseowp_saved_types, true)); ?>>
                                        <span><?php echo esc_html($vonseowp_type_label); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                            <fieldset class="von-form-group">
                                <legend><?php esc_html_e('SEO Taxonomies', 'vonseo'); ?></legend>
                                <input type="hidden" name="vonseowp_settings[seo_taxonomies][]" value="">
                                <?php
                                $vonseowp_saved_taxonomies = VonSEOWP_Taxonomies::get_saved(is_array($vonseowp_options) ? $vonseowp_options : array());
                                $vonseowp_available_taxonomies = VonSEOWP_Taxonomies::get_available();
                                foreach (array_unique(array_merge(array_keys($vonseowp_available_taxonomies), $vonseowp_saved_taxonomies)) as $vonseowp_taxonomy_name) :
                                    $vonseowp_taxonomy_label = isset($vonseowp_available_taxonomies[$vonseowp_taxonomy_name])
                                        ? $vonseowp_available_taxonomies[$vonseowp_taxonomy_name]->labels->name
                                        : $vonseowp_taxonomy_name . ' (' . __('Unavailable', 'vonseo') . ')';
                                    ?>
                                    <label class="von-checkbox-row">
                                        <input type="checkbox" name="vonseowp_settings[seo_taxonomies][]" value="<?php echo esc_attr($vonseowp_taxonomy_name); ?>" <?php checked(in_array($vonseowp_taxonomy_name, $vonseowp_saved_taxonomies, true)); ?>>
                                        <span><?php echo esc_html($vonseowp_taxonomy_label); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                        </div>
                    </div>
                </div>

                <!-- Tab: Social -->
                <div id="tab-social" class="von-tab-content" style="display: none;">
                    <div class="von-card">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('Social Profiles', 'vonseo'); ?></h3>
                        </div>
                        <div class="von-card-body">
                            <div class="von-form-group">
                                <label><?php esc_html_e('Facebook URL', 'vonseo'); ?></label>
                                <input type="url" name="vonseowp_settings[facebook_url]" value="<?php echo esc_attr($vonseowp_options['facebook_url'] ?? ''); ?>" class="von-input">
                            </div>
                            <div class="von-form-group">
                                <label><?php esc_html_e('Twitter Username', 'vonseo'); ?></label>
                                <input type="text" name="vonseowp_settings[twitter_username]" value="<?php echo esc_attr($vonseowp_options['twitter_username'] ?? ''); ?>" class="von-input" placeholder="@username">
                            </div>
                        </div>
                    </div>

                    <div class="von-card">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('Open Graph Settings', 'vonseo'); ?></h3>
                        </div>
                        <div class="von-card-body">
                            <div class="von-form-group">
                                <label class="von-switch">
                                    <input type="checkbox" name="vonseowp_settings[enable_og]" value="1" <?php checked(1, $vonseowp_options['enable_og'] ?? 1); ?>>
                                    <span class="von-slider"></span>
                                </label>
                                <span class="von-switch-label"><?php esc_html_e('Enable Open Graph Meta Data', 'vonseo'); ?></span>
                                <div class="von-form-desc"><?php esc_html_e('Adds tags for Facebook, LinkedIn, etc.', 'vonseo'); ?></div>
                            </div>

                            <div class="von-form-group">
                                <label><?php esc_html_e('Default Image URL', 'vonseo'); ?></label>
                                <input type="url" name="vonseowp_settings[default_image]" value="<?php echo esc_attr($vonseowp_options['default_image'] ?? ''); ?>" class="von-input">
                                <div class="von-form-desc"><?php esc_html_e('Used if the post has no featured image.', 'vonseo'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Sitemap -->
                <div id="tab-sitemap" class="von-tab-content" style="display: none;">
                    <div class="von-card">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('XML Sitemap', 'vonseo'); ?></h3>
                        </div>
                        <div class="von-card-body">
                            <div class="von-form-group">
                                <label class="von-switch">
                                    <input type="checkbox" name="vonseowp_settings[enable_sitemap]" value="1" <?php checked(1, $vonseowp_options['enable_sitemap'] ?? 1); ?>>
                                    <span class="von-slider"></span>
                                </label>
                                <span class="von-switch-label"><?php esc_html_e('Enable XML Sitemap', 'vonseo'); ?></span>
                            </div>

                            <div class="von-form-group">
                                <p class="von-sitemap-link"><?php esc_html_e('Your sitemap is located at:', 'vonseo'); ?> <a href="<?php echo esc_url(home_url('/sitemap.xml')); ?>" target="_blank" rel="noopener noreferrer" class="von-link"><?php echo esc_html(home_url('/sitemap.xml')); ?></a></p>
                            </div>

                            <h4><?php esc_html_e('Include Post Types', 'vonseo'); ?></h4>
                            <div class="von-form-group">
                                <label class="von-checkbox-label">
                                    <input type="checkbox" name="vonseowp_settings[sitemap_posts]" value="1" <?php checked(1, $vonseowp_options['sitemap_posts'] ?? 1); ?>>
                                    <?php esc_html_e('Posts', 'vonseo'); ?>
                                </label>
                                <br>
                                <label class="von-checkbox-label">
                                    <input type="checkbox" name="vonseowp_settings[sitemap_pages]" value="1" <?php checked(1, $vonseowp_options['sitemap_pages'] ?? 1); ?>>
                                    <?php esc_html_e('Pages', 'vonseo'); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Redirects -->
                <div id="tab-redirects" class="von-tab-content" style="display: none;">
                    <div class="von-card">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('Redirection Manager', 'vonseo'); ?></h3>
                            <p class="von-card-desc"><?php esc_html_e('Format:', 'vonseo'); ?> <code>/old-url -> /new-url</code> <?php esc_html_e('(one per line)', 'vonseo'); ?></p>
                        </div>
                        <div class="von-card-body">
                            <div class="von-form-group">
                                <label class="von-switch">
                                    <input type="checkbox" name="vonseowp_settings[enable_404_log]" value="1" <?php checked(1, $vonseowp_options['enable_404_log'] ?? 0); ?>>
                                    <span class="von-slider"></span>
                                </label>
                                <span class="von-switch-label"><?php esc_html_e('Enable 404 Error Logging', 'vonseo'); ?></span>
                            </div>
                            <div class="von-form-group">
                                <label><?php esc_html_e('Redirect Rules', 'vonseo'); ?></label>
                                <textarea name="vonseowp_settings[redirects_list]" class="von-input von-code-area" rows="10" placeholder="/old-page -> /new-page"><?php echo esc_textarea($vonseowp_options['redirects_list'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <?php
                    $vonseowp_logs = get_option('vonseowp_404_log', array());
                    if (!empty($vonseowp_logs)):
                    ?>
                        <div class="von-card">
                            <div class="von-card-header">
                                <h3><?php esc_html_e('Recent 404 Errors', 'vonseo'); ?></h3>
                            </div>
                            <div class="von-card-body">
                                <table class="von-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('URL', 'vonseo'); ?></th>
                                            <th><?php esc_html_e('Hits', 'vonseo'); ?></th>
                                            <th><?php esc_html_e('Last Seen', 'vonseo'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $vonseowp_reversed_logs = is_array($vonseowp_logs) ? array_reverse($vonseowp_logs) : array();
                                        foreach ($vonseowp_reversed_logs as $vonseowp_url => $vonseowp_data):
                                        ?>
                                            <tr>
                                                <td><?php echo esc_html($vonseowp_url); ?></td>
                                                <td><?php echo esc_html($vonseowp_data['count']); ?></td>
                                                <td><?php echo esc_html($vonseowp_data['last_hit']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <p style="margin-top: 10px; font-size: 0.9em; color: #666;"><?php esc_html_e('* Showing last 50 unique 404s.', 'vonseo'); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab: Local SEO -->
                <div id="tab-local" class="von-tab-content" style="display: none;">
                    <div class="von-card">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('Local Business Info', 'vonseo'); ?></h3>
                            <p class="von-card-desc"><?php esc_html_e('Populate your Organization Schema with location data.', 'vonseo'); ?></p>
                        </div>
                        <div class="von-card-body">
                            <div class="von-form-group">
                                <label><?php esc_html_e('Business Name', 'vonseo'); ?></label>
                                <input type="text" name="vonseowp_settings[business_name]" value="<?php echo esc_attr($vonseowp_options['business_name'] ?? get_bloginfo('name')); ?>" class="von-input">
                            </div>
                            <div class="von-form-group">
                                <label><?php esc_html_e('Address', 'vonseo'); ?></label>
                                <input type="text" name="vonseowp_settings[address]" value="<?php echo esc_attr($vonseowp_options['address'] ?? ''); ?>" class="von-input" placeholder="<?php esc_attr_e('123 SEO Street', 'vonseo'); ?>">
                            </div>
                            <div class="von-form-group">
                                <label><?php esc_html_e('City', 'vonseo'); ?></label>
                                <input type="text" name="vonseowp_settings[city]" value="<?php echo esc_attr($vonseowp_options['city'] ?? ''); ?>" class="von-input">
                            </div>
                            <div class="von-form-group">
                                <label><?php esc_html_e('State / Province', 'vonseo'); ?></label>
                                <input type="text" name="vonseowp_settings[state]" value="<?php echo esc_attr($vonseowp_options['state'] ?? ''); ?>" class="von-input">
                            </div>
                            <div class="von-form-group">
                                <label><?php esc_html_e('Zip / Postal Code', 'vonseo'); ?></label>
                                <input type="text" name="vonseowp_settings[zip]" value="<?php echo esc_attr($vonseowp_options['zip'] ?? ''); ?>" class="von-input">
                            </div>
                            <div class="von-form-group">
                                <label><?php esc_html_e('Country', 'vonseo'); ?></label>
                                <input type="text" name="vonseowp_settings[country]" value="<?php echo esc_attr($vonseowp_options['country'] ?? ''); ?>" class="von-input" placeholder="US">
                            </div>
                            <div class="von-form-group">
                                <label><?php esc_html_e('Phone Number', 'vonseo'); ?></label>
                                <input type="text" name="vonseowp_settings[phone]" value="<?php echo esc_attr($vonseowp_options['phone'] ?? ''); ?>" class="von-input">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Image SEO -->
                <div id="tab-image" class="von-tab-content" style="display: none;">
                    <div class="von-card">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('Image SEO', 'vonseo'); ?></h3>
                            <p class="von-card-desc"><?php esc_html_e('Automatically optimize image attributes.', 'vonseo'); ?></p>
                        </div>
                        <div class="von-card-body">
                            <div class="von-form-group">
                                <label class="von-switch">
                                    <input type="checkbox" name="vonseowp_settings[auto_image_alt]" value="1" <?php checked(1, $vonseowp_options['auto_image_alt'] ?? 0); ?>>
                                    <span class="von-slider"></span>
                                </label>
                                <span class="von-switch-label"><?php esc_html_e('Auto-Generate ALT Tags', 'vonseo'); ?></span>
                                <div class="von-form-desc"><?php esc_html_e('Uses filename (e.g., "my-image.jpg" -> "My Image") if ALT is missing.', 'vonseo'); ?></div>
                            </div>
                            <div class="von-form-group">
                                <label class="von-switch">
                                    <input type="checkbox" name="vonseowp_settings[auto_image_title]" value="1" <?php checked(1, $vonseowp_options['auto_image_title'] ?? 0); ?>>
                                    <span class="von-slider"></span>
                                </label>
                                <span class="von-switch-label"><?php esc_html_e('Auto-Generate Title Tags', 'vonseo'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Table of Contents -->
                <div id="tab-toc" class="von-tab-content" style="display: none;">
                    <div class="von-card">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('Table of Contents', 'vonseo'); ?></h3>
                            <p class="von-card-desc"><?php esc_html_e('Automatically generate a table of contents for your posts.', 'vonseo'); ?></p>
                        </div>
                        <div class="von-card-body">
                            <div class="von-form-group">
                                <label class="von-switch">
                                    <input type="checkbox" name="vonseowp_settings[enable_toc]" value="1" <?php checked(1, $vonseowp_options['enable_toc'] ?? 0); ?>>
                                    <span class="von-slider"></span>
                                </label>
                                <span class="von-switch-label"><?php esc_html_e('Enable Automatic TOC', 'vonseo'); ?></span>
                                <div class="von-form-desc"><?php esc_html_e('Automatically injects a TOC into your posts.', 'vonseo'); ?></div>
                            </div>

                            <div class="von-form-group">
                                <label><?php esc_html_e('TOC Title', 'vonseo'); ?></label>
                                <input type="text" name="vonseowp_settings[toc_title]" value="<?php echo esc_attr($vonseowp_options['toc_title'] ?? __('Table of Contents', 'vonseo')); ?>" class="von-input" placeholder="<?php esc_attr_e('Table of Contents', 'vonseo'); ?>">
                            </div>

                            <div class="von-form-group">
                                <label><?php esc_html_e('TOC Position', 'vonseo'); ?></label>
                                <select name="vonseowp_settings[toc_position]" class="von-input von-select">
                                    <option value="before_first_heading" <?php selected($vonseowp_options['toc_position'] ?? 'before_first_heading', 'before_first_heading'); ?>><?php esc_html_e('Before first heading (Recommended)', 'vonseo'); ?></option>
                                    <option value="top" <?php selected($vonseowp_options['toc_position'] ?? 'before_first_heading', 'top'); ?>><?php esc_html_e('At the very top', 'vonseo'); ?></option>
                                </select>
                            </div>

                            <div class="von-form-group">
                                <label><?php esc_html_e('Minimum Headings', 'vonseo'); ?></label>
                                <input type="number" name="vonseowp_settings[toc_min_headings]" value="<?php echo esc_attr($vonseowp_options['toc_min_headings'] ?? 3); ?>" class="von-input" min="1" max="10">
                                <div class="von-form-desc"><?php esc_html_e('Only show TOC if the post has at least this many headings.', 'vonseo'); ?></div>
                            </div>

                            <div class="von-form-group">
                                <label><?php esc_html_e('Shortcode', 'vonseo'); ?></label>
                                <input type="text" class="von-input von-code-area" value="[vonseo_toc]" readonly>
                                <div class="von-form-desc"><?php esc_html_e('Use this shortcode to place the TOC anywhere in your content.', 'vonseo'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Tab: Tutorial -->
                <div id="tab-tutorial" class="von-tab-content" style="display: none;">
                    <div class="von-card">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('How to Use VonSEO', 'vonseo'); ?></h3>
                            <p class="von-card-desc"><?php esc_html_e('Setup, publishing and archive SEO.', 'vonseo'); ?></p>
                        </div>
                        <div class="von-card-body">
                            <div class="von-tutorial-grid">
                                <div class="von-tutorial-item">
                                    <h4><?php esc_html_e('1. XML Sitemaps', 'vonseo'); ?></h4>
                                    <p><?php
                                        /* translators: 1: XML Sitemap tab name, 2: Sitemap URL */
                                        printf(esc_html__('Enable it in the %1$s tab, then open %2$s to check the output. Submit that URL in your search-engine webmaster tools. A sitemap supports discovery but does not guarantee indexing.', 'vonseo'), '<strong>' . esc_html__('XML Sitemap', 'vonseo') . '</strong>', '<code>/sitemap.xml</code>');
                                        ?></p>
                                </div>
                                <div class="von-tutorial-item">
                                    <h4><?php esc_html_e('2. Redirection Manager', 'vonseo'); ?></h4>
                                    <p><?php
                                        /* translators: 1: Redirects tab name, 2: Example format */
                                        printf(esc_html__('Enable 404 logging in the %1$s tab when needed. Add a redirect using %2$s, save, then test the old URL. Choose a relevant replacement and avoid redirect chains.', 'vonseo'), '<strong>' . esc_html__('Redirects', 'vonseo') . '</strong>', '<code>/old-url -> /new-url</code>');
                                        ?></p>
                                </div>
                                <div class="von-tutorial-item">
                                    <h4><?php esc_html_e('3. Content Analysis', 'vonseo'); ?></h4>
                                    <p><?php esc_html_e('Choose SEO Content Types in General and save. Open an enabled post, page or custom post type, then enter a focus keyword and review the VonSEO fields. Save the content to persist your SEO title, description and social overrides.', 'vonseo'); ?></p>
                                </div>
                                <div class="von-tutorial-item">
                                    <h4><?php esc_html_e('4. Local SEO', 'vonseo'); ?></h4>
                                    <p><?php esc_html_e('Enter accurate business details in Local SEO only if they apply to your site. These details can appear in public structured data; do not enter private contact information.', 'vonseo'); ?></p>
                                </div>
                                <div class="von-tutorial-item">
                                    <h4><?php esc_html_e('5. Image SEO', 'vonseo'); ?></h4>
                                    <p><?php esc_html_e('Auto-Generate ALT Tags uses the filename when ALT text is missing. Review image descriptions yourself: a filename fallback cannot determine what an image actually shows.', 'vonseo'); ?></p>
                                </div>
                                <div class="von-tutorial-item">
                                    <h4><?php esc_html_e('6. Category and Tag SEO', 'vonseo'); ?></h4>
                                    <p><?php esc_html_e('Choose SEO Taxonomies in General and save. Categories and tags are selected by default. Open Posts > Categories or Posts > Tags, then add or edit a term. Set its SEO Title, Meta Description and optional Social Description, then save the term. Selected custom taxonomies use their own WordPress term screen.', 'vonseo'); ?></p>
                                </div>
                                <div class="von-tutorial-item">
                                    <h4><?php esc_html_e('7. Canonical and Noindex', 'vonseo'); ?></h4>
                                    <p><?php esc_html_e('Leave an archive Canonical URL empty for its native URL. Use an override only when another absolute HTTP(S) URL is the preferred page. Overrides apply to page one; paginated archives keep their own canonicals. Noindex and explicit-canonical terms are excluded from the sitemap, as are empty terms. Noindex is a crawler instruction, not access control.', 'vonseo'); ?></p>
                                </div>
                                <div class="von-tutorial-item">
                                    <h4><?php esc_html_e('8. Site Audit', 'vonseo'); ?></h4>
                                    <p><?php esc_html_e('Open VonSEO > SEO Audit and run the audit. Use Previous/Next to review 25 published content items per batch. This is a local settings/content check, not a search-engine ranking or indexing report; taxonomy term editors are not part of this audit list.', 'vonseo'); ?></p>
                                </div>
                                <div class="von-tutorial-item">
                                    <h4><?php esc_html_e('9. Optional IndexNow', 'vonseo'); ?></h4>
                                    <p><?php esc_html_e('Enable IndexNow in Advanced only if you want external URL notifications. Eligible published content is queued for at least 60 seconds and rechecked before delivery; WordPress cron or site traffic can delay it. Password-protected, noindex and unpublished content is excluded. Term-archive saves do not send IndexNow notifications.', 'vonseo'); ?></p>
                                </div>
                                <div class="von-tutorial-item">
                                    <h4><?php esc_html_e('10. Table of Contents', 'vonseo'); ?></h4>
                                    <p><?php esc_html_e('Configure the heading threshold and position in Table of Contents, or insert the [vonseo_toc] shortcode. The post editor can disable automatic insertion for individual content. Preview the page and check the heading links before publishing.', 'vonseo'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="von-tutorial-row">
                        <div class="von-card">
                            <div class="von-card-header">
                                <h3><?php esc_html_e('Setup Checklist', 'vonseo'); ?></h3>
                            </div>
                            <div class="von-card-body">
                                <ul class="von-tutorial-checklist">
                                    <li><span>1</span> <?php esc_html_e('Set your homepage title and description.', 'vonseo'); ?></li>
                                    <li><span>2</span> <?php esc_html_e('Select your SEO Content Types and SEO Taxonomies, then save.', 'vonseo'); ?></li>
                                    <li><span>3</span> <?php esc_html_e('Add your social media profiles.', 'vonseo'); ?></li>
                                    <li><span>4</span> <?php esc_html_e('Enable and inspect the XML sitemap; review archive canonical/noindex choices.', 'vonseo'); ?></li>
                                    <li><span>5</span> <?php esc_html_e('Configure local business info if you have a physical location.', 'vonseo'); ?></li>
                                </ul>
                            </div>
                        </div>

                        <div class="von-card">
                            <div class="von-card-header">
                                <h3><?php esc_html_e('Pro Tips', 'vonseo'); ?></h3>
                            </div>
                            <div class="von-card-body">
                                <div class="von-pro-tip">
                                    <strong><?php esc_html_e('Check Canonicals:', 'vonseo'); ?></strong> <?php esc_html_e('Prefer native self-referencing URLs unless you intentionally choose another canonical. Search engines decide whether to accept canonical hints.', 'vonseo'); ?>
                                </div>
                                <div class="von-pro-tip">
                                    <strong><?php esc_html_e('Review 404s:', 'vonseo'); ?></strong> <?php esc_html_e('Inspect logged errors and redirect only when a relevant replacement exists. Some missing URLs should remain 404 responses.', 'vonseo'); ?>
                                </div>
                                <div class="von-pro-tip">
                                    <strong><?php esc_html_e('Alt Text Matters:', 'vonseo'); ?></strong> <?php
                                                                                                            /* translators: 1: Example filename, 2: Example converted text */
                                                                                                            printf(esc_html__('Always use descriptive filenames for images. Our Image SEO will turn %1$s into %2$s automatically!', 'vonseo'), '<code>best-coffee-beans.jpg</code>', '<code>Best Coffee Beans</code>');
                                                                                                            ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="von-card">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('Frequently Asked Questions', 'vonseo'); ?></h3>
                        </div>
                        <div class="von-card-body">
                            <div class="von-faq-item">
                                <div class="faq-q"><?php esc_html_e('Where is my sitemap?', 'vonseo'); ?></div>
                                <div class="faq-a"><?php esc_html_e('Your sitemap is generated dynamically at /sitemap.xml. No physical file is created, keeping your server clean.', 'vonseo'); ?></div>
                            </div>
                            <div class="von-faq-item">
                                <div class="faq-q"><?php esc_html_e('Does a high SEO score guarantee rankings?', 'vonseo'); ?></div>
                                <div class="faq-a"><?php esc_html_e('No. The score summarizes local editorial checks. Use it to review useful titles, descriptions, structure and links, not as a ranking prediction or a reason to repeat keywords unnaturally.', 'vonseo'); ?></div>
                            </div>
                            <div class="von-faq-item">
                                <div class="faq-q"><?php esc_html_e('Will this slow down my site?', 'vonseo'); ?></div>
                                <div class="faq-a"><?php esc_html_e('VonSEO uses native WordPress storage and bounded audit/sitemap batches. Actual performance depends on your hosting, content, theme and other plugins.', 'vonseo'); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="von-card" id="vonseowp-seo-disclaimer">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('SEO & Indexing Disclaimer', 'vonseo'); ?></h3>
                        </div>
                        <div class="von-card-body">
                            <p><?php esc_html_e('VonSEO provides technical and editorial SEO tools. Installing or using this plugin does not guarantee that your website or any page will be crawled, indexed, ranked or displayed by Google or any other search engine. No indexing deadline, ranking position, traffic increase or rich result is promised.', 'vonseo'); ?></p>
                            <p><?php esc_html_e('Sitemaps, metadata, structured data, canonical hints and optional IndexNow notifications support SEO workflows; they do not force search engines to accept or index a URL. Search engines independently decide what to crawl, index and show. VonSEO scores are internal editorial checks, not Google scores or proof of indexing.', 'vonseo'); ?></p>
                            <p><a href="https://developers.google.com/search/docs/essentials" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Google Search Essentials', 'vonseo'); ?></a></p>
                        </div>
                    </div>

                    <div class="von-card" id="vonseowp-privacy-policy">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('Privacy & Data Policy', 'vonseo'); ?></h3>
                        </div>
                        <div class="von-card-body">
                            <h4><?php esc_html_e('Local data and public output', 'vonseo'); ?></h4>
                            <p><?php esc_html_e('VonSEO has no built-in telemetry or analytics tracking. It stores settings in WordPress options and SEO fields in post/term metadata, without custom database tables. Content analysis runs in your browser and Site Audit runs on your WordPress server. Public metadata, schema and business details are readable by visitors and crawlers; publish only information intended for the public.', 'vonseo'); ?></p>
                            <h4><?php esc_html_e('Optional local diagnostics and browser preferences', 'vonseo'); ?></h4>
                            <p><?php esc_html_e('If you enable 404 logging, VonSEO stores up to 50 requested URLs locally, with a hit count and last-hit time. URLs can include query strings and personal information; avoid sensitive URL parameters. This log does not store visitor IP addresses or user-agent strings. Turning logging off stops new entries but does not erase existing ones; deleting the plugin removes the local 404 log. The settings screen uses browser sessionStorage only to remember the active settings tab, not to identify visitors or send tracking data.', 'vonseo'); ?></p>
                            <h4><?php esc_html_e('Optional IndexNow requests', 'vonseo'); ?></h4>
                            <p><?php esc_html_e('IndexNow defaults to off unless you previously enabled it. When enabled, your server sends eligible canonical content URLs, site hostname, verification key and key-file location to Bing and Yandex. Those services also receive ordinary HTTP request information such as the server IP address. Draft text, post bodies and SEO descriptions are not sent. Turn IndexNow off in Advanced to stop future notifications; deactivation also clears queued events. A delivered request cannot be recalled.', 'vonseo'); ?></p>
                            <p><a href="https://www.indexnow.org/documentation" target="_blank" rel="noopener noreferrer"><?php esc_html_e('IndexNow documentation', 'vonseo'); ?></a> &middot; <a href="https://www.microsoft.com/en-us/privacy/privacystatement" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Microsoft privacy', 'vonseo'); ?></a> &middot; <a href="https://yandex.com/legal/confidential/en/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Yandex privacy', 'vonseo'); ?></a></p>
                            <h4><?php esc_html_e('Manual Competitor scans', 'vonseo'); ?></h4>
                            <p><?php esc_html_e('Only an authorized editor can request a Competitor scan for enabled content. Your server fetches the URL that editor enters; the destination can see the requested URL, server IP address and normal request headers. Editor drafts are not uploaded to that destination or an AI service. Results use a WordPress transient with a 12-hour expiry. The destination website has its own privacy policy.', 'vonseo'); ?></p>
                            <h4><?php esc_html_e('Manual validation and external links', 'vonseo'); ?></h4>
                            <p><?php esc_html_e('Schema testing buttons open Google or Schema.org and pass a page URL, falling back to the current editor URL if a permalink is unavailable. The external service receives normal browser request information; inspect the URL before submitting confidential addresses. Documentation and donation links also lead to third-party sites with their own privacy policies. These are user-initiated links, not background telemetry.', 'vonseo'); ?></p>
                            <h4><?php esc_html_e('Public llms.txt', 'vonseo'); ?></h4>
                            <p><?php esc_html_e('When enabled on a public site, llms.txt is a public text endpoint, not a private document or an access-control mechanism. Manually curated text and links are not checked individually for password/noindex eligibility. Do not put secrets or private links there. Disabling the endpoint or making the site non-public returns a 404, but cannot erase copies already fetched by third parties.', 'vonseo'); ?></p>
                            <h4><?php esc_html_e('Retention and removal', 'vonseo'); ?></h4>
                            <p><?php esc_html_e('Deactivation retains saved settings and SEO fields. Deleting VonSEO runs its settings and metadata cleanup, including taxonomy SEO fields. Competitor cache expiry is handled by WordPress transients. Site backups, hosting/server logs and data already received by external services are outside this cleanup. Adapt your public site privacy notice to the services and hosting you actually use; this plugin notice does not replace it.', 'vonseo'); ?></p>
                        </div>
                    </div>

                    <!-- Support Development -->
                    <div class="von-donate-section">
                        <div class="von-donate-body">
                            <span class="von-donate-eyebrow"><?php esc_html_e('Support independent development', 'vonseo'); ?></span>
                            <h2><?php esc_html_e('Support VonSEO development', 'vonseo'); ?></h2>
                            <p><?php esc_html_e('Your support helps fund maintenance, compatibility testing, and future updates.', 'vonseo'); ?></p>
                            <a href="https://paypal.me/kurama87" target="_blank" rel="noopener noreferrer" class="von-btn-donate von-btn-donate-large">
                                <span class="dashicons dashicons-coffee" aria-hidden="true"></span>
                                <span class="von-donate-label"><?php esc_html_e('Buy me a coffee', 'vonseo'); ?></span>
                            </a>
                        </div>
                    </div>

                </div>

                <!-- Tab: Advanced -->
                <div id="tab-advanced" class="von-tab-content" style="display: none;">
                    <div class="von-card">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('Breadcrumbs Generator', 'vonseo'); ?></h3>
                            <p class="von-card-desc"><?php esc_html_e('Display clean breadcrumbs in posts, pages, or theme templates using the new built-in generator.', 'vonseo'); ?></p>
                        </div>
                        <div class="von-card-body">
                            <div class="von-form-group">
                                <label><?php esc_html_e('Breadcrumb Separator', 'vonseo'); ?></label>
                                <input type="text" name="vonseowp_settings[breadcrumb_separator]" value="<?php echo esc_attr($vonseowp_options['breadcrumb_separator'] ?? '/'); ?>" class="von-input" maxlength="5">
                                <div class="von-form-desc"><?php esc_html_e('Short values like "/", ">", or "|" work best.', 'vonseo'); ?></div>
                            </div>

                            <div class="von-form-group">
                                <label><?php esc_html_e('Shortcode', 'vonseo'); ?></label>
                                <input type="text" class="von-input von-code-area" value="[vonseowp_breadcrumbs]" readonly>
                            </div>

                            <div class="von-form-group">
                                <label><?php esc_html_e('Theme Function', 'vonseo'); ?></label>
                                <textarea class="von-input von-code-area" rows="3" readonly><?php echo esc_textarea("<?php if ( function_exists( 'vonseowp_breadcrumbs' ) ) { vonseowp_breadcrumbs(); } ?>"); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section: URL Structure -->
                    <div class="von-card">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('URL Structure & Permalinks', 'vonseo'); ?></h3>
                            <p class="von-card-desc"><?php esc_html_e('Advanced URL manipulation tools. Use with caution.', 'vonseo'); ?></p>
                        </div>
                        <div class="von-card-body">
                            <div class="von-form-group">
                                <label class="von-switch">
                                    <input type="checkbox" name="vonseowp_settings[remove_category_base]" value="1" <?php checked(1, $vonseowp_options['remove_category_base'] ?? 0); ?>>
                                    <span class="von-slider"></span>
                                </label>
                                <span class="von-switch-label"><?php esc_html_e('Remove Category Base (Url Stub)', 'vonseo'); ?></span>
                                <div class="von-form-desc"><?php
                                                            /* translators: 1: URL stub, 2: Important tag */
                                                            printf(esc_html__('Removes %1$s from category archive URLs. %2$s Go to Settings > Permalinks and just click "Save" after changing this option.', 'vonseo'), '<code>/category/</code>', '<br><strong>' . esc_html__('Important:', 'vonseo') . '</strong>');
                                                            ?></div>
                            </div>

                            <div class="von-form-group">
                                <label class="von-switch">
                                    <input type="checkbox" name="vonseowp_settings[redirect_attachments]" value="1" <?php checked(1, $vonseowp_options['redirect_attachments'] ?? 1); ?>>
                                    <span class="von-slider"></span>
                                </label>
                                <span class="von-switch-label"><?php esc_html_e('Redirect Attachment URLs', 'vonseo'); ?></span>
                                <div class="von-form-desc"><?php
                                                            /* translators: %s: Example attachment URL */
                                                            printf(esc_html__('Redirects image attachment pages (e.g. %s) to the parent post. Highly recommended for SEO.', 'vonseo'), '<code>site.com/image-name/</code>');
                                                            ?></div>
                            </div>

                            <div class="von-form-group">
                                <label class="von-switch">
                                    <input type="checkbox" name="vonseowp_settings[enable_rss_footer]" value="1" <?php checked(1, $vonseowp_options['enable_rss_footer'] ?? 1); ?>>
                                    <span class="von-slider"></span>
                                </label>
                                <span class="von-switch-label"><?php esc_html_e('Enable RSS Footer Protection', 'vonseo'); ?></span>
                                <div class="von-form-desc"><?php esc_html_e('Auto-append a backlink to the original post in your RSS feed to prevent content theft.', 'vonseo'); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Indexing -->
                    <div class="von-card">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('IndexNow', 'vonseo'); ?></h3>
                            <p class="von-card-desc"><?php esc_html_e('Deferred notifications require a working WordPress cron runner.', 'vonseo'); ?></p>
                        </div>
                        <div class="von-card-body">
                            <div class="von-form-group">
                                <label class="von-switch">
                                    <input type="checkbox" name="vonseowp_settings[enable_indexnow]" value="1" <?php checked($vonseowp_indexnow_enabled); ?>>
                                    <span class="von-slider"></span>
                                </label>
                                <span class="von-switch-label"><?php esc_html_e('Enable IndexNow (Bing & Yandex)', 'vonseo'); ?></span>
                                <div class="von-form-desc">
                                    <?php esc_html_e('Automatically notifies search engines about new content.', 'vonseo'); ?> <a href="https://www.indexnow.org/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Learn more', 'vonseo'); ?></a>.
                                    <div style="margin-top: 8px; background: #f1f5f9; padding: 10px; border-radius: 6px; font-size: 0.85em;">
                                        <strong><?php esc_html_e('IndexNow Status:', 'vonseo'); ?></strong> <span style="color: <?php echo esc_attr($vonseowp_indexnow_enabled ? '#16a34a' : '#64748b'); ?>; font-weight: bold;"><?php echo esc_html($vonseowp_indexnow_enabled ? __('Enabled', 'vonseo') : __('Disabled', 'vonseo')); ?></span><br>
                                        <strong><?php esc_html_e('Key:', 'vonseo'); ?></strong> <code><?php echo esc_html(get_option('vonseowp_indexnow_key', esc_html__('Auto-Generated on save', 'vonseo'))); ?></code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: System Health -->
                    <div class="von-card" style="border-left: 4px solid #3b82f6;">
                        <div class="von-card-header">
                            <h3><span class="dashicons dashicons-heart" style="color: #ef4444;"></span> <?php esc_html_e('System Health & Compatibility', 'vonseo'); ?></h3>
                            <p class="von-card-desc"><?php esc_html_e('Technical status of your SEO environment.', 'vonseo'); ?></p>
                        </div>
                        <div class="von-card-body">
                            <div class="von-health-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                <?php
                                $vonseowp_health_items = array(
                                    array(
                                        'label'  => __('PHP Version', 'vonseo'),
                                        'status' => version_compare(PHP_VERSION, '7.4', '>='),
                                        'value'  => PHP_VERSION,
                                        'desc'   => __('Required: 7.4+', 'vonseo')
                                    ),
                                    array(
                                        'label'  => __('Permalinks', 'vonseo'),
                                        'status' => get_option('permalink_structure') !== '',
                                        'value'  => get_option('permalink_structure') ?: __('Plain', 'vonseo'),
                                        'desc'   => __('Required for Sitemap & LLM', 'vonseo')
                                    ),
                                    array(
                                        'label'  => __('DOM & XPath', 'vonseo'),
                                        'status' => class_exists('DOMDocument') && class_exists('DOMXPath'),
                                        'value'  => class_exists('DOMDocument') ? __('Available', 'vonseo') : __('Missing', 'vonseo'),
                                        'desc'   => __('Required for Competitor Analysis', 'vonseo')
                                    ),
                                    array(
                                        'label'  => __('Remote Requests', 'vonseo'),
                                        'status' => function_exists('curl_version') || ini_get('allow_url_fopen'),
                                        'value'  => function_exists('curl_version') ? __('cURL Ready', 'vonseo') : __('Fopen Ready', 'vonseo'),
                                        'desc'   => __('Required for IndexNow', 'vonseo')
                                    )
                                );

                                foreach ($vonseowp_health_items as $vonseowp_health_item) :
                                    $vonseowp_health_icon = $vonseowp_health_item['status'] ? 'yes-alt' : 'warning';
                                    $vonseowp_health_color = $vonseowp_health_item['status'] ? '#16a34a' : '#dc2626';
                                ?>
                                    <div class="von-health-item" style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 5px;">
                                            <span style="font-size: 12px; font-weight: 600; color: #64748b;"><?php echo esc_html($vonseowp_health_item['label']); ?></span>
                                            <span class="dashicons dashicons-<?php echo esc_attr($vonseowp_health_icon); ?>" style="color: <?php echo esc_attr($vonseowp_health_color); ?>; font-size: 18px; width: 18px; height: 18px;"></span>
                                        </div>
                                        <div style="font-weight: 700; color: #1e293b;"><?php echo esc_html($vonseowp_health_item['value']); ?></div>
                                        <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;"><?php echo esc_html($vonseowp_health_item['desc']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Robots.txt -->
                <div id="tab-robots" class="von-tab-content" style="display: none;">
                    <div class="von-card">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('Advanced Robots.txt Editor', 'vonseo'); ?></h3>
                            <p class="von-card-desc"><?php esc_html_e('Control how search engine crawlers access your site.', 'vonseo'); ?></p>
                        </div>
                        <div class="von-card-body">
                            <div class="von-form-group">
                                <label><?php esc_html_e('Robots.txt Content', 'vonseo'); ?></label>
                                <textarea name="vonseowp_settings[robots_txt]" id="vonseowp_robots_txt" class="von-input von-code-area" rows="15"><?php
                                                                                                                                                    echo esc_textarea($vonseowp_options['robots_txt'] ?? VonSEOWP_Frontend::get_default_robots_txt());
                                                                                                                                                    ?></textarea>
                                <div class="von-form-desc"><?php esc_html_e('This content will override the default WordPress robots.txt output.', 'vonseo'); ?></div>
                            </div>

                            <div class="von-center-row">
                                <button type="button" class="von-btn-secondary" id="von-reset-robots">
                                    <span class="dashicons dashicons-undo"></span> <?php esc_html_e('Reset to Recommended (Pro Rules)', 'vonseo'); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="von-card">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('Robots.txt Guide', 'vonseo'); ?></h3>
                        </div>
                        <div class="von-card-body">
                            <div class="von-pro-tip">
                                <strong><?php esc_html_e('User-agent: *', 'vonseo'); ?></strong> <?php esc_html_e('means the rule applies to all crawlers.', 'vonseo'); ?>
                            </div>
                            <div class="von-pro-tip">
                                <strong><?php esc_html_e('Disallow: /path/', 'vonseo'); ?></strong> <?php esc_html_e('blocks crawlers from visiting that specific folder.', 'vonseo'); ?>
                            </div>
                            <div class="von-pro-tip">
                                <strong><?php esc_html_e('Sitemap:', 'vonseo'); ?></strong> <?php esc_html_e('helping Google find your sitemap quickly is an SEO best practice.', 'vonseo'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: AI & LLM -->
                <div id="tab-llm" class="von-tab-content" style="display: none;">
                    <div class="von-card">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('AI & LLM Optimization (llms.txt)', 'vonseo'); ?></h3>
                            <p class="von-card-desc"><?php esc_html_e('Help AI models like OpenAI, Claude, and Perplexity understand your site better.', 'vonseo'); ?></p>
                        </div>
                        <div class="von-card-body">
                            <div class="von-form-group">
                                <label class="von-switch">
                                    <input type="checkbox" name="vonseowp_settings[enable_llms_txt]" value="1" <?php checked(1, $vonseowp_options['enable_llms_txt'] ?? 1); ?>>
                                    <span class="von-slider"></span>
                                </label>
                                <span class="von-switch-label"><?php esc_html_e('Enable llms.txt', 'vonseo'); ?></span>
                                <div class="von-form-desc"><?php esc_html_e('Expose a curated Markdown file at /llms.txt for AI crawlers.', 'vonseo'); ?></div>
                            </div>

                            <div class="von-llm-info-box" style="background: #f0f9ff; border: 1px solid #bae6fd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <div>
                                        <strong style="color: #0369a1; display: block; margin-bottom: 4px;"><?php esc_html_e('Live View', 'vonseo'); ?></strong>
                                        <a href="<?php echo esc_url(home_url('/llms.txt')); ?>" target="_blank" rel="noopener noreferrer" class="von-link" style="font-weight: bold;">
                                            <?php echo esc_html(home_url('/llms.txt')); ?> <span class="dashicons dashicons-external" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                        </a>
                                    </div>
                                    <div style="text-align: right;">
                                        <span class="von-badge-premium" style="background: <?php echo esc_attr($vonseowp_llms_enabled ? '#0ea5e9' : '#94a3b8'); ?>;"><?php echo esc_html($vonseowp_llms_enabled ? __('AEO Enabled', 'vonseo') : __('AEO Disabled', 'vonseo')); ?></span>
                                    </div>
                                </div>
                                <p style="margin: 10px 0 0; font-size: 13px; color: #0c4a6e;">
                                    <span class="dashicons dashicons-info" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                    <?php
                                    /* translators: %s: Settings page link */
                                    printf(esc_html__('If the page above results in a 404, please go to %s and click "Save Changes" to activate the new URL.', 'vonseo'), '<strong>' . esc_html__('Settings > Permalinks', 'vonseo') . '</strong>');
                                    ?>
                                </p>
                            </div>

                            <div class="von-form-group">
                                <label><?php esc_html_e('llms.txt Content (Markdown)', 'vonseo'); ?></label>
                                <textarea name="vonseowp_settings[llms_txt_content]" id="vonseowp_llms_txt" class="von-input von-code-area" rows="15" placeholder="<?php esc_attr_e('# My Site Name...', 'vonseo'); ?>"><?php
                                                                                                                                                                                                                        echo esc_textarea($vonseowp_options['llms_txt_content'] ?? "# " . get_bloginfo('name') . "\n\n> " . get_bloginfo('description') . "\n\n## Key Resources\n\n- [Home Page](" . home_url('/') . "): Main entry point.\n- [XML Sitemap](" . home_url('/sitemap.xml') . "): Full index of pages.\n\n## About\nCreated for AI Engine Optimization (AEO).");
                                                                                                                                                                                                                        ?></textarea>
                                <div class="von-form-desc"><?php esc_html_e('Use Markdown format. This helps LLMs digest your site structure efficiently.', 'vonseo'); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="von-card">
                        <div class="von-card-header">
                            <h3><?php esc_html_e('What is llms.txt?', 'vonseo'); ?></h3>
                        </div>
                        <div class="von-card-body">
                            <div class="von-pro-tip">
                                <strong><?php esc_html_e('AI Engine Optimization (AEO):', 'vonseo'); ?></strong> <?php esc_html_e('Similar to SEO, but for AI search engines.', 'vonseo'); ?>
                            </div>
                            <div class="von-pro-tip">
                                <strong><?php esc_html_e('Clean Context:', 'vonseo'); ?></strong> <?php esc_html_e('Providing Markdown instead of raw HTML saves AI "tokens" and prevents hallucinations.', 'vonseo'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="von-center-row" style="margin-top: 20px;">
                        <button type="button" class="von-btn-secondary" id="von-reset-llm">
                            <span class="dashicons dashicons-undo"></span> <?php esc_html_e('Reset to Recommended (AEO Template)', 'vonseo'); ?>
                        </button>
                    </div>
                </div>

                <div class="von-form-actions">
                    <button type="submit" class="von-btn-primary"><?php esc_html_e('Save Changes', 'vonseo'); ?></button>
                </div>
            </form>
        </main>
    </div> <!-- .von-admin-layout -->
</div> <!-- .von-dashboard-wrapper -->
