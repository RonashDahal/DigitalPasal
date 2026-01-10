            </main>
        </div>
    </div>
</body>
</html>
<script>
function toggleAdminSidebar() {
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    if (sidebar.classList.contains('sidebar-hidden')) {
        sidebar.classList.remove('sidebar-hidden');
        sidebar.classList.add('sidebar-visible');
        overlay.classList.remove('hidden');
    } else {
        sidebar.classList.add('sidebar-hidden');
        sidebar.classList.remove('sidebar-visible');
        overlay.classList.add('hidden');
    }
}
</script>