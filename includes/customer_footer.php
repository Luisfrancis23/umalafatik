<?php
/** Customer dashboard closing shell. */
$page_scripts = $page_scripts ?? [];
?>
    </main>
  </div>
</div>
<script src="<?= asset('js/main.js') . '?v=1' ?>"></script>
<script src="<?= asset('js/dashboard.js') . '?v=1' ?>"></script>
<?php foreach ($page_scripts as $script): ?>
<script src="<?= e(asset('js/' . ltrim($script, '/'))) . '?v=1' ?>"></script>
<?php endforeach; ?>
</body>
</html>
