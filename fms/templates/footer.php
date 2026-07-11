</main> 
    </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    /** * 1. SIDEBAR & OVERLAY LOGIC 
     */
    const sidebarToggler = document.querySelector('.sidebar-toggler');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.page-overlay');

    const toggleSidebar = () => {
        if (sidebar && overlay) {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            // Mencegah scroll pada body saat sidebar aktif di mobile
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : 'auto';
        }
    };

    if (sidebarToggler) {
        sidebarToggler.addEventListener('click', (e) => {
            e.preventDefault();
            toggleSidebar();
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }

    /** * 2. SMART AUTO-ACTIVE MENU 
     * Memastikan menu tetap aktif meskipun ada query string (?page=1)
     */
    const currentPath = window.location.pathname.split("/").pop();
    const navLinks = document.querySelectorAll('.sidebar .nav-link');

    navLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        
        // Pencocokan yang lebih cerdas (tidak kaku pada string utuh)
        if (linkPath && linkPath.includes(currentPath) && currentPath !== "") {
            link.classList.add('active');
            
            // Handle Submenu (Dropdown)
            let parentCollapse = link.closest('.collapse');
            if (parentCollapse) {
                const triggerId = parentCollapse.id;
                const triggerLink = document.querySelector(`a[href="#${triggerId}"]`);
                
                if (triggerLink) {
                    triggerLink.classList.remove('collapsed');
                    triggerLink.setAttribute('aria-expanded', 'true');
                    triggerLink.classList.add('parent-active'); // Style tambahan untuk parent
                    parentCollapse.classList.add('show');
                }
            }
        }
    });

    /** * 3. INITIALIZE ICONS 
     * Cek apakah pustaka lucide sudah termuat untuk menghindari error JS
     */
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<footer class="text-center py-3 border-top mt-auto bg-light">
    <small class="text-muted">
        &copy; <?= date('Y'); ?> <strong>PT REAL DATA SOLUSINDO</strong>. 
        All rights reserved. <span class="d-none d-md-inline">| v2.1 Production</span>
    </small>
</footer>

</body>
</html>