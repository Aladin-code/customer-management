<?php
require_once 'config.php';

// Get customer data if editing
$customer = null;
if (isset($_GET['email'])) {
    $email = sanitizeInput($_GET['email']);
    $conn = getDBConnection();

    $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $customer = $result->fetch_assoc();
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Information Entry Form</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/screenshare.css">
</head>
<body>
    <div class="container">
        <h1>Customer Information Entry Form</h1>

        <form id="customer-form" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="lastname">Last Name *</label>
                <input type="text" id="lastname" name="lastname" value="<?php echo $customer ? htmlspecialchars($customer['lastname']) : ''; ?>" required>
                <div id="lastname-error" class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="firstname">First Name *</label>
                <input type="text" id="firstname" name="firstname" value="<?php echo $customer ? htmlspecialchars($customer['firstname']) : ''; ?>" required>
                <div id="firstname-error" class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" value="<?php echo $customer ? htmlspecialchars($customer['email']) : ''; ?>" required>
                <div id="email-error" class="error-message"></div>
                <div id="email-success" class="success-message"></div>
            </div>

            <div class="form-group">
                <label for="city">City *</label>
                <input type="text" id="city" name="city" value="<?php echo $customer ? htmlspecialchars($customer['city']) : ''; ?>" required>
                <div id="city-error" class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="country">Country *</label>
                <select id="country" name="country" required>
                    <option value="">Select a country</option>
                    <option value="United States" <?php echo ($customer && $customer['country'] === 'United States') ? 'selected' : ''; ?>>United States</option>
                    <option value="Canada" <?php echo ($customer && $customer['country'] === 'Canada') ? 'selected' : ''; ?>>Canada</option>
                    <option value="Japan" <?php echo ($customer && $customer['country'] === 'Japan') ? 'selected' : ''; ?>>Japan</option>
                    <option value="United Kingdom" <?php echo ($customer && $customer['country'] === 'United Kingdom') ? 'selected' : ''; ?>>United Kingdom</option>
                    <option value="France" <?php echo ($customer && $customer['country'] === 'France') ? 'selected' : ''; ?>>France</option>
                    <option value="Germany" <?php echo ($customer && $customer['country'] === 'Germany') ? 'selected' : ''; ?>>Germany</option>
                </select>
            </div>

            <div class="form-group">
                <label for="image">Customer Picture (JPEG only)</label>
                <div class="file-upload" onclick="document.getElementById('image').click()">
                    <input type="file" id="image" name="image" accept=".jpg,.jpeg" style="display: none;">
                    <p>Click to select a JPEG image</p>
                    <button type="button" id="upload-btn" onclick="event.stopPropagation(); FileUpload.uploadFile()">Upload</button>
                </div>
                <input type="hidden" id="image-path" name="image_path" value="<?php echo $customer ? htmlspecialchars($customer['image_path']) : ''; ?>">
                <div id="image-preview">
                    <?php if ($customer && $customer['image_path'] && file_exists($customer['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($customer['image_path']); ?>" alt="Customer Image" class="preview-image">
                        <p style="margin-top: 10px; color: #666;">Current image</p>
                    <?php endif; ?>
                </div>
                <div id="image-error" class="error-message"></div>
                <div id="image-success" class="success-message"></div>
            </div>

            <div class="button-group">
                <button type="button" class="btn btn-primary" onclick="saveCustomer()">Save</button>
                <button type="button" class="btn btn-secondary" onclick="cancelChanges()">Cancel</button>
            </div>
        </form>

        <div style="margin-top: 30px; text-align: center;">
            <p><a href="review.php" style="color: #667eea; text-decoration: none;">Go to Customer Review Page</a></p>
        </div>
    </div>

    <script src="js/validation.js"></script>
    <script src="js/screenshare.js"></script>
</body>
</html>