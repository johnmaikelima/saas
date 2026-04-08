</main>

<footer class="bg-dark text-muted text-center py-2 mt-auto" style="font-size:0.8rem;">
    <?= e(APP_NAME) ?> v<?= APP_VERSION ?> &copy; <?= date('Y') ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= baseUrl('assets/js/app.js') ?>"></script>
<?php if (isset($extraJs)): foreach($extraJs as $js): ?>
    <script src="<?= baseUrl($js) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
