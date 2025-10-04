<?php
/**
 * Contact Page
 * 
 * This page displays a contact form for users to send messages to the site administrators
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Process form submission
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name)) {
        $error = 'Please enter your name.';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (empty($subject)) {
        $error = 'Please enter a subject.';
    } elseif (empty($message)) {
        $error = 'Please enter your message.';
    } else {
        // In a real application, you would send an email here
        // For demonstration purposes, we'll just show a success message
        
        // Example of how you might send an email:
        // mail('admin@example.com', 'Contact Form: ' . $subject, $message, 'From: ' . $email);
        
        $success = true;
        
        // Clear form data after successful submission
        $name = $email = $subject = $message = '';
    }
}

$pageTitle = 'Contact Us';
include __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Contact Us</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <p>Thank you for your message! We will get back to you as soon as possible.</p>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h2>Get in Touch</h2>
                            <p>We'd love to hear from you! Please fill out the form with your information and we'll respond as soon as possible.</p>
                            
                            <div class="mt-4">
                                <h4>Contact Information</h4>
                                <p><i class="bi bi-envelope"></i> Email: contact@example.com</p>
                                <p><i class="bi bi-telephone"></i> Phone: (123) 456-7890</p>
                                <p><i class="bi bi-geo-alt"></i> Address: 123 Forum Street, Web City, Internet 12345</p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Your Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Send Message</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>