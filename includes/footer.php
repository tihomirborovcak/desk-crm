        </main>
    </div>

    <!-- Loading overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner">🇭🇷</div>
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

    // Prikaži loader kod klika na sve linkove koji vode na drugu stranicu
    document.querySelectorAll('a[href]').forEach(function(link) {
        link.addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            // Preskoči # linkove, javascript: linkove i eksterne linkove
            if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('http') || this.hasAttribute('target')) {
                return;
            }
            showLoader();
        });
    });

    function showLoader() {
        document.getElementById('loadingOverlay').classList.add('active');
    }

    function hideLoader() {
        document.getElementById('loadingOverlay').classList.remove('active');
    }

    // Sakrij loader ako se korisnik vrati nazad (browser back)
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) {
            hideLoader();
        }
    });
    </script>
</body>
</html>
