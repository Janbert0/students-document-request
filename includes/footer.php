<?php
require_once __DIR__ . '/auth.php';
?>
<?php if (getCurrentUser()): ?>
      </main>
    </div>
  </div>
<?php endif; ?>
<script src="<?= e(BASE_URL) ?>/assets/js/main.js" defer></script>
<?php foreach (($pageScripts ?? []) as $script): ?>
<script src="<?= e(BASE_URL) ?>/assets/js/<?= e($script) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
