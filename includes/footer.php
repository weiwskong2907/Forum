</main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Blog & Forum. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="<?php echo BASE_URL; ?>/privacy.php" class="text-decoration-none text-dark me-3">Privacy Policy</a>
                    <a href="<?php echo BASE_URL; ?>/terms.php" class="text-decoration-none text-dark me-3">Terms of Service</a>
                    <a href="<?php echo BASE_URL; ?>/contact.php" class="text-decoration-none text-dark">Contact Us</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/custom.js"></script>
    <?php if (basename($_SERVER['PHP_SELF']) === 'profile.php'): ?>
    <script src="<?php echo BASE_URL; ?>/assets/js/profile-image-cropper.js"></script>
    <?php endif; ?>
</body>
</html>