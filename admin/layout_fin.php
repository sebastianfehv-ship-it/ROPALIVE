    </main>
</div>
<script src="<?php echo h($adminBasePath); ?>assets/js/confirmaciones.js" defer></script>
<script>
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarCollapse = document.getElementById('sidebar-collapse');
    const adminShell = document.querySelector('.admin-shell');
    const sidebarStorageKey = 'ropalive-admin-sidebar-collapsed';

    const setSidebarCollapsed = (collapsed) => {
        adminShell.classList.toggle('sidebar-collapsed', collapsed);
        localStorage.setItem(sidebarStorageKey, String(collapsed));
        sidebarToggle.setAttribute('aria-expanded', String(!collapsed));
        sidebarToggle.setAttribute('aria-label', collapsed ? 'Desplegar menú' : 'Plegar menú');
    };

    setSidebarCollapsed(localStorage.getItem(sidebarStorageKey) === 'true');
    sidebarToggle.addEventListener('click', () => setSidebarCollapsed(false));
    sidebarCollapse.addEventListener('click', () => setSidebarCollapsed(true));
</script>
</body>
</html>
