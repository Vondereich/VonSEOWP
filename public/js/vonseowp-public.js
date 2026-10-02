/**
 * VonSEO Public JS
 */
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.vonseo-toc-toggle');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const container = this.closest('.vonseo-toc-container');
            const list = container && container.querySelector('.vonseo-toc-list');
            if (!list) return;
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            
            list.hidden = isExpanded;
            this.setAttribute('aria-expanded', String(!isExpanded));
            this.textContent = this.getAttribute(isExpanded ? 'data-show-label' : 'data-hide-label');
        });
    });
});
