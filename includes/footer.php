<footer class="site-footer mt-5">
    <div class="site-footer-inner">
        <div class="container">
            <div class="row gy-4 align-items-start">

                <!-- Brand -->
                <div class="col-md-4">
                    <div class="footer-brand">
                        <img src="images/phppickemlogo.png" alt="<?php echo APP_NAME; ?> Logo" class="footer-logo mb-2">
                        <div class="footer-tagline"><?php echo APP_NAME; ?> <?php echo htmlspecialchars(SEASON_YEAR); ?></div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-md-4">
                    <h6 class="footer-heading">Quick Links</h6>
                    <ul class="footer-links">
                        <li><a href="./"><i class="fa-solid fa-house fa-fw me-1"></i>Home</a></li>
                        <?php if (!empty($_SESSION['logged']) && $_SESSION['logged'] === 'yes'): ?>
                            <li><a href="entry_form.php"><i class="fa-solid fa-clipboard-list fa-fw me-1"></i>Entry Form</a></li>
                        <?php endif; ?>
                        <li><a href="results.php"><i class="fa-solid fa-chart-bar fa-fw me-1"></i>Results</a></li>
                        <li><a href="standings.php"><i class="fa-solid fa-ranking-star fa-fw me-1"></i>Standings</a></li>
                        <li><a href="schedules.php"><i class="fa-solid fa-calendar-days fa-fw me-1"></i>Schedules</a></li>
                        <li><a href="rules.php"><i class="fa-solid fa-book fa-fw me-1"></i>Rules / Help</a></li>
                    </ul>
                </div>

                <!-- Copyright + Actions -->
                <div class="col-md-4 d-flex flex-column align-items-md-end gap-3">
                    <a href="#" class="btn btn-sm btn-footer-top">
                        <i class="fa-solid fa-arrow-up me-1"></i>Back to Top
                    </a>
                    <?php if (isset($config['show_donation_button']) && $config['show_donation_button']): ?>
                        <a href="donate.php" class="btn btn-sm btn-footer-donate">
                            <i class="fa-solid fa-heart me-1"></i>Support
                        </a>
                    <?php endif; ?>
                    <div class="footer-copy">
                        <?php if (!empty($config['custom_footer_text'])): ?>
                            <?php echo $config['custom_footer_text']; ?>
                        <?php else: ?>
                            &copy; 2013 Kevin Roth &mdash; <a href="license.html">MIT License</a><br>
                            &copy; 2025&ndash;<?php echo date('Y'); ?> <a href="https://it.megocollector.com">Paul Combs</a> &mdash; <a href="license.html">MIT License</a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</footer>

<!-- Include Bootstrap 5 JavaScript Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  (function () {
    var storageKey = 'phppickem-theme';
    var root = document.documentElement;
    var toggle = document.getElementById('themeToggle');

    function getCookie(name) {
      var match = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
      return match ? decodeURIComponent(match.pop()) : null;
    }

    function setCookie(name, value) {
      var cookie = name + '=' + encodeURIComponent(value) + '; Path=/; Max-Age=31536000; SameSite=Lax';
      if (window.location && window.location.protocol === 'https:') {
        cookie += '; Secure';
      }
      document.cookie = cookie;
    }

    function preferredTheme() {
      return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
        ? 'dark' : 'light';
    }

    function setTheme(theme) {
      root.setAttribute('data-theme', theme);
      setCookie(storageKey, theme);
      if (toggle) {
        var isDark = theme === 'dark';
        toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        toggle.innerHTML = isDark
          ? '<i class="fa-solid fa-sun"></i> Light'
          : '<i class="fa-solid fa-moon"></i> Dark';
      }
    }

    var saved = getCookie(storageKey);
    setTheme(saved || preferredTheme());

    if (toggle) {
      toggle.addEventListener('click', function () {
        var current = root.getAttribute('data-theme');
        setTheme(current === 'dark' ? 'light' : 'dark');
      });
    }
  })();
</script>
</body>
</html>
