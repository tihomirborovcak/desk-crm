        </main>
    </div>

    <!-- Loading overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Pričekaj malo...</div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/desk-crm/sw.js');
    }

    // Prikaži loader kod submitanja formi
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            showLoader();
        });
    });

    // Prikaži loader kod klika na linkove koji rade neku akciju
    document.querySelectorAll('a[href*="delete"], a[href*="action="]').forEach(function(link) {
        link.addEventListener('click', function() {
            showLoader();
        });
    });

    function showLoader() {
        document.getElementById('loadingOverlay').classList.add('active');
    }

    function hideLoader() {
        document.getElementById('loadingOverlay').classList.remove('active');
    }
    </script>
</body>
</html>
