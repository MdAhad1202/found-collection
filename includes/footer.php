</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-highlight active sidebar link
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href && (href === currentPath || currentPath.includes(href.split('?')[0]))) {
            link.classList.add('active');
        }
    });
});

// Alert auto-dismiss after 5s
setTimeout(() => {
    document.querySelectorAll('.alert-dismissible').forEach(el => {
        if (window.bootstrap && window.bootstrap.Alert) {
            new bootstrap.Alert(el).close();
        }
    });
}, 5000);
</script>
</body>
</html>