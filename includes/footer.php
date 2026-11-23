<footer class="site-footer">
    <div class="section-wrap footer-main">
        <div class="footer-brand">
            <a class="brand" href="<?= htmlspecialchars($assetPrefix ?? '', ENT_QUOTES, 'UTF-8') ?>#home" aria-label="PDF Toolkit home">
                <span class="brand-mark"><svg aria-hidden="true"><use href="#icon-mark"></use></svg></span>
                <span class="brand-name">PDF <strong>Toolkit</strong></span>
            </a>
            <p>Everything you need to work with PDF documents. Thoughtfully designed, privacy first.</p>
        </div>
        <div class="footer-column"><h2>Explore</h2><a href="<?= htmlspecialchars($assetPrefix ?? '', ENT_QUOTES, 'UTF-8') ?>#tools">All tools</a><a href="<?= htmlspecialchars($assetPrefix ?? '', ENT_QUOTES, 'UTF-8') ?>#how-it-works">How it works</a><a href="<?= htmlspecialchars($assetPrefix ?? '', ENT_QUOTES, 'UTF-8') ?>#categories">Tool categories</a></div>
        <div class="footer-column"><h2>PDF tools</h2><a href="<?= htmlspecialchars($assetPrefix ?? '', ENT_QUOTES, 'UTF-8') ?>#convert-pdf">Convert PDF</a><a href="<?= htmlspecialchars($assetPrefix ?? '', ENT_QUOTES, 'UTF-8') ?>#edit-pdf">Edit PDF</a><a href="<?= htmlspecialchars($assetPrefix ?? '', ENT_QUOTES, 'UTF-8') ?>#optimize-pdf">Compress PDF</a></div>
        <div class="footer-column"><h2>Information</h2><a href="<?= htmlspecialchars($assetPrefix ?? '', ENT_QUOTES, 'UTF-8') ?>#privacy-note">Privacy</a><a href="<?= htmlspecialchars($assetPrefix ?? '', ENT_QUOTES, 'UTF-8') ?>#pricing">Pricing</a><span>Contact details coming soon</span></div>
    </div>
    <div class="section-wrap footer-bottom"><span>© <?= date('Y') ?> PDF Toolkit. Built for your documents.</span><span class="footer-local"><span></span> No external file APIs</span></div>
</footer>
<div class="toast" id="toast" role="status" aria-live="polite" hidden></div>
<?php if (!isset($disableAppJs) || $disableAppJs !== true): ?>
    <script src="<?= htmlspecialchars($assetPrefix ?? '', ENT_QUOTES, 'UTF-8') ?>assets/js/app.js" defer></script>
<?php endif; ?>
</body>
</html>
