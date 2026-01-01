</main>
    
    <footer style="background: #2c3e50; color: white; padding: 40px 0; margin-top: 60px;">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5 style="margin-bottom: 20px; font-weight: 600;">🏀 Basketball Learning</h5>
                    <p style="color: #bdc3c7;">Професійне онлайн-навчання баскетболу для всіх рівнів підготовки</p>
                </div>
                <div class="col-md-4">
                    <h5 style="margin-bottom: 20px; font-weight: 600;">Швидкі посилання</h5>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 10px;"><a href="<?= BASE_URL ?>/courses.php" style="color: #bdc3c7; text-decoration: none;">Курси</a></li>
                        <li style="margin-bottom: 10px;"><a href="<?= BASE_URL ?>/trainers.php" style="color: #bdc3c7; text-decoration: none;">Тренери</a></li>
                        <li style="margin-bottom: 10px;"><a href="<?= BASE_URL ?>/about.php" style="color: #bdc3c7; text-decoration: none;">Про нас</a></li>
                        <li style="margin-bottom: 10px;"><a href="<?= BASE_URL ?>/contact.php" style="color: #bdc3c7; text-decoration: none;">Контакти</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 style="margin-bottom: 20px; font-weight: 600;">Контакти</h5>
                    <p style="color: #bdc3c7; margin-bottom: 10px;">
                        <i class="fas fa-envelope me-2"></i>info@basketball-learning.com
                    </p>
                    <p style="color: #bdc3c7; margin-bottom: 10px;">
                        <i class="fas fa-phone me-2"></i>+380 (123) 456-78-90
                    </p>
                    <div style="margin-top: 20px;">
                        <a href="#" style="color: white; font-size: 1.5rem; margin-right: 15px;"><i class="fab fa-facebook"></i></a>
                        <a href="#" style="color: white; font-size: 1.5rem; margin-right: 15px;"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="color: white; font-size: 1.5rem; margin-right: 15px;"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            <hr style="border-color: #34495e; margin: 30px 0;">
            <div class="text-center" style="color: #bdc3c7;">
                <p>&copy; <?= date('Y') ?> Basketball Learning. Всі права захищені.</p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Автоматичне закриття alerts через 5 секунд
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>