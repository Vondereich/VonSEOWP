(function($) {
    // Ensure inlineEditPost is available
    if (typeof inlineEditPost === 'undefined') {
        return;
    }

    // We hook into the editPost function of the inlineEditPost object
    const wp_inline_edit = inlineEditPost.edit;

    inlineEditPost.edit = function(id) {
        // Call the original function
        wp_inline_edit.apply(this, arguments);

        // Handle both post ID (number) and the clicked element (object)
        let post_id = 0;
        if (typeof id === 'object') {
            post_id = parseInt(this.getId(id));
        } else {
            post_id = parseInt(id);
        }

        if (post_id > 0) {
            const edit_row = $('#edit-' + post_id);
            const post_row = $('#post-' + post_id);

            // Get our data from the hidden div in the column
            const seo_data = post_row.find('.vonseo-quick-edit-data');
            
            if (seo_data.length) {
                // attr(), not data(): jQuery's data() converts numeric or JSON-like text into other types.
                const title = seo_data.attr('data-title') || '';
                const desc = seo_data.attr('data-desc') || '';
                const keywords = seo_data.attr('data-keywords') || '';

                // Populate the fields with safety fallbacks
                edit_row.find('input[name="vonseowp_title"]').val(title);
                edit_row.find('textarea[name="vonseowp_description"]').val(desc);
                edit_row.find('input[name="vonseowp_keywords"]').val(keywords);
            }
        }
    };
})(jQuery);
