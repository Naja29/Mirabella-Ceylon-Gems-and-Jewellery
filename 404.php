<?php
http_response_code(404);
$pageTitle   = '404 - Page Not Found | Mirabella Ceylon';
$pageDesc    = 'The page you are looking for could not be found.';
$activePage  = '';
$headerClass = 'is-solid';
$extraCSS    = [];
include 'includes/header.php';
?>

<style>
.error-page {
  min-height: 72vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  background: var(--off-white);
}
.error-page__inner {
  text-align: center;
  max-width: 560px;
}
.error-page__code {
  font-family: var(--font-serif);
  font-size: clamp(80px, 18vw, 160px);
  font-weight: 700;
  line-height: 1;
  color: var(--gold);
  opacity: .18;
  letter-spacing: -4px;
  margin-bottom: -20px;
}
.error-page__icon {
  font-size: 52px;
  color: var(--gold);
  margin-bottom: 24px;
}
.error-page__title {
  font-family: var(--font-serif);
  font-size: clamp(26px, 5vw, 38px);
  color: var(--text);
  margin-bottom: 16px;
}
.error-page__text {
  color: var(--text-soft);
  font-size: 16px;
  line-height: 1.7;
  margin-bottom: 36px;
}
.error-page__actions {
  display: flex;
  gap: 14px;
  justify-content: center;
  flex-wrap: wrap;
}
.error-page__links {
  margin-top: 48px;
  padding-top: 32px;
  border-top: 1px solid var(--border);
}
.error-page__links p {
  color: var(--text-soft);
  font-size: 14px;
  margin-bottom: 16px;
}
.error-page__links-grid {
  display: flex;
  gap: 12px;
  justify-content: center;
  flex-wrap: wrap;
}
.error-page__link {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 18px;
  border: 1px solid var(--border);
  border-radius: 6px;
  color: var(--text-soft);
  text-decoration: none;
  font-size: 14px;
  transition: border-color .2s, color .2s;
}
.error-page__link:hover {
  border-color: var(--gold);
  color: var(--gold-dark);
}
</style>

<div class="error-page">
  <div class="error-page__inner">
    <div class="error-page__code">404</div>
    <div class="error-page__icon"><i class="fas fa-gem"></i></div>
    <h1 class="error-page__title">This gem is missing</h1>
    <p class="error-page__text">
      The page you're looking for seems to have wandered off — perhaps like a rare padparadscha sapphire, it's simply not in our collection right now.
    </p>
    <div class="error-page__actions">
      <a href="index.php" class="btn btn--gold"><i class="fas fa-home"></i> Back to Home</a>
      <a href="shop.php"  class="btn btn--outline-gold"><i class="fas fa-gem"></i> Browse Collections</a>
    </div>
    <div class="error-page__links">
      <p>Or try one of these pages:</p>
      <div class="error-page__links-grid">
        <a href="shop.php?cat=blue-sapphire" class="error-page__link"><i class="fas fa-gem"></i> Blue Sapphires</a>
        <a href="shop.php?cat=padparadscha"  class="error-page__link"><i class="fas fa-gem"></i> Padparadscha</a>
        <a href="shop.php?cat=jewellery"     class="error-page__link"><i class="fas fa-ring"></i> Jewellery</a>
        <a href="cart.php"                   class="error-page__link"><i class="fas fa-shopping-bag"></i> Your Cart</a>
        <a href="contact.php"                class="error-page__link"><i class="fas fa-envelope"></i> Contact Us</a>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
