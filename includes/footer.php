        </div> <!-- End Content -->
    </div> <!-- End Main Content -->

    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <?php if (isset($additionalScripts)): ?>
        <?php foreach ($additionalScripts as $script): ?>
            <script src="<?php echo BASE_URL . $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>

