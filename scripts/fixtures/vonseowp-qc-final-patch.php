<?php
/** Classic editor fixture only in the owned disposable QC installation. */
if (!defined('ABSPATH')) exit;
global $wpdb;
if (!preg_match('/^vonseoqc250_[a-f0-9]{8}_$/D', $wpdb->base_prefix)) return;
add_filter('use_block_editor_for_post', '__return_false');
