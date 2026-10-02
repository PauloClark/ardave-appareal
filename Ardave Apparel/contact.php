<?php
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid CSRF token. Please try again.';
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if ($phone === '') $errors[] = 'Mobile number is required.';
    if ($subject === '') $errors[] = 'Subject is required.';
    if ($message === '') $errors[] = 'Message is required.';

    if (empty($errors)) {
        // insert into notifications as a contact message for admin
        $stmt = $pdo->prepare('INSERT INTO notifications (customer_id,type,message) VALUES (?,?,?)');
        $note = "Contact form:\nName: {$name}\nEmail: {$email}\nPhone: {$phone}\nSubject: {$subject}\nMessage: {$message}";
        $stmt->execute([null, 'contact', $note]);

        // send a simple email to admin (best-effort)
        $admin = getenv('ADMIN_EMAIL') ?: 'no-reply@ardaveapparel.local';
        $body = "New contact form submission:\n\n" . $note;
        send_mail($admin, 'Contact Form: ' . $subject, $body);

        $success = 'Thank you, your message has been sent. We will get back to you shortly.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Contact Us - Ardave Apparel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <style>.map-placeholder{background:#e9ecef;height:220px;border-radius:6px}</style>
</head>
<body>
  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <main class="container page-panel py-5">
    <div class="row g-4">
      <div class="col-md-6">
        <h2>Contact Us</h2>
        <p>Ardave Apparel — Since 2021</p>
        <p>Find us on <a href="https://www.facebook.com/ardaveappareldavao" target="_blank">Facebook</a>, <a href="https://www.instagram.com/ardaveapparel/?hl=en" target="_blank">Instagram</a>, and <a href="https://www.tiktok.com/@ardavechannel?lang=en" target="_blank">TikTok</a>.</p>

        <?php if ($success): ?>
          <div class="alert alert-success"><?= h($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger"><ul><?php foreach ($errors as $e) echo '<li>'.h($e).'</li>'; ?></ul></div>
        <?php endif; ?>

        <form method="post" novalidate>
          <input type="hidden" name="csrf_token" value="<?= h(get_csrf_token()) ?>">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input name="name" class="form-control" required value="<?= h($_POST['name'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input name="email" type="email" class="form-control" required value="<?= h($_POST['email'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Mobile Number</label>
            <input name="phone" class="form-control" required value="<?= h($_POST['phone'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Subject</label>
            <input name="subject" class="form-control" required value="<?= h($_POST['subject'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea name="message" class="form-control" rows="5" required><?= h($_POST['message'] ?? '') ?></textarea>
          </div>
          <div>
            <button class="btn btn-dark">Send Message</button>
          </div>
        </form>
      </div>

      <div class="col-md-6">
        <h4>Our Location</h4>
        <div class="map-placeholder mb-3">
          <!-- Optional: embed Google Maps iframe -->
        </div>

        <h5>Contact Information</h5>
        <p><strong>Ardave Apparel</strong><br>South San Juan Centro, Agdao, Davao City, Davao del Sur<br>Phone: <a href="tel:09664614504">0966 461 4504</a><br>
        <a href="https://maps.app.goo.gl/PGPsC5tu89zu642dA" target="_blank">View location on Google Maps</a></p>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

