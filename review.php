<?php
require_once 'config.php';

$customer = null;
$error = null;

// Get customer data from email parameter
if (isset($_GET['email'])) {
    $email = sanitizeInput($_GET['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        $conn = getDBConnection();

        $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
        } else {
            $error = "No customer found with email: " . htmlspecialchars($email);
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Information Review</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/screenshare.css">
</head>
<body>
    <div class="container">
        <h1>Customer Information Review</h1>

        <?php if (!isset($_GET['email'])): ?>
            <div class="alert alert-info">
                <h3>How to use this page:</h3>
                <p>Add an email parameter to the URL to look up a customer:</p>
                <p><strong>Example:</strong> <code>review.php?email=john.doe@example.com</code></p>
            </div>

            <div class="form-group">
                <label for="email-lookup">Enter customer email to lookup:</label>
                <div style="display: flex; gap: 10px;">
                    <input type="email" id="email-lookup" placeholder="customer@example.com" style="flex: 1;">
                    <button type="button" class="btn btn-primary" onclick="lookupCustomer()">Lookup</button>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <h3>Sample customers for testing:</h3>
                <ul>
                    <li><a href="review.php?email=john.doe@example.com">john.doe@example.com</a></li>
                    <li><a href="review.php?email=jane.smith@example.com">jane.smith@example.com</a></li>
                    <li><a href="review.php?email=hiroshi.tanaka@example.com">hiroshi.tanaka@example.com</a></li>
                </ul>
            </div>

        <?php elseif ($error): ?>
            <div class="alert alert-error">
                <h3>Error</h3>
                <p><?php echo $error; ?></p>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <a href="review.php" class="btn btn-secondary">Try Another Email</a>
                <a href="index.php" class="btn btn-primary">Add New Customer</a>
            </div>

        <?php elseif ($customer): ?>
            <div class="alert alert-success">
                <p>Customer found! Displaying information for: <strong><?php echo htmlspecialchars($customer['email']); ?></strong></p>
            </div>

            <div class="customer-info">
                <h2>Customer Details</h2>

                <div class="customer-row">
                    <span class="customer-label">Customer ID:</span>
                    <span class="customer-value"><?php echo htmlspecialchars($customer['id']); ?></span>
                </div>

                <div class="customer-row">
                    <span class="customer-label">Last Name:</span>
                    <span class="customer-value"><?php echo htmlspecialchars($customer['lastname']); ?></span>
                </div>

                <div class="customer-row">
                    <span class="customer-label">First Name:</span>
                    <span class="customer-value"><?php echo htmlspecialchars($customer['firstname']); ?></span>
                </div>

                <div class="customer-row">
                    <span class="customer-label">Email:</span>
                    <span class="customer-value"><?php echo htmlspecialchars($customer['email']); ?></span>
                </div>

                <div class="customer-row">
                    <span class="customer-label">City:</span>
                    <span class="customer-value"><?php echo htmlspecialchars($customer['city']); ?></span>
                </div>

                <div class="customer-row">
                    <span class="customer-label">Country:</span>
                    <span class="customer-value"><?php echo htmlspecialchars($customer['country']); ?></span>
                </div>

                <div class="customer-row">
                    <span class="customer-label">Created:</span>
                    <span class="customer-value"><?php echo date('F j, Y g:i A', strtotime($customer['created_at'])); ?></span>
                </div>

                <div class="customer-row">
                    <span class="customer-label">Last Updated:</span>
                    <span class="customer-value"><?php echo date('F j, Y g:i A', strtotime($customer['updated_at'])); ?></span>
                </div>

                <?php if ($customer['image_path'] && file_exists($customer['image_path'])): ?>
                    <div class="customer-row">
                        <span class="customer-label">Photo:</span>
                        <div class="customer-value">
                            <img src="<?php echo htmlspecialchars($customer['image_path']); ?>" alt="Customer Photo" class="customer-image">
                        </div>
                    </div>
                <?php else: ?>
                    <div class="customer-row">
                        <span class="customer-label">Photo:</span>
                        <span class="customer-value" style="font-style: italic; color: #666;">No photo uploaded</span>
                    </div>
                <?php endif; ?>
            </div>

            <div style="text-align: center; margin: 20px 0;">
                <a href="index.php?email=<?php echo urlencode($customer['email']); ?>" class="btn btn-primary">Edit Customer</a>
                <a href="review.php" class="btn btn-secondary">Lookup Another</a>
            </div>

        <?php endif; ?>

        <!-- Mini Pocket Calculator Section -->
        <div class="calculator-section">
            <h2>Mini Pocket Calculator</h2>
            <p>Use this calculator for quick calculations while reviewing customer information.</p>

            <div class="calculator-container">
                <div class="result-section">
                    <label for="calculator-result">Result:</label>
                    <input type="text" id="calculator-result" class="result-field" readonly placeholder="Result will appear here">
                </div>

                <div>
                    <h4>Calculator Display:</h4>
                    <iframe src="calculator-display.html" class="calculator-iframe" width="250" height="60"></iframe>
                </div>

                <div>
                    <h4>Calculator Buttons:</h4>
                    <iframe src="calculator-buttons.html" class="calculator-iframe" width="200" height="200"></iframe>
                </div>
            </div>
        </div>


        <div style="text-align: center; margin-top: 30px;">
            <p><a href="index.php" style="color: #667eea; text-decoration: none;">Go to Customer Entry Form</a></p>
        </div>
    </div>

    <script src="js/validation.js"></script>
    <script src="js/calculator.js"></script>
    <script src="js/screenshare.js"></script>
    <script>
        function lookupCustomer() {
            const email = document.getElementById('email-lookup').value.trim();
            if (!email) {
                alert('Please enter an email address');
                return;
            }

            if (!FormValidator.validateEmail(email)) {
                alert('Please enter a valid email address');
                return;
            }

            window.location.href = `review.php?email=${encodeURIComponent(email)}`;
        }

        // Allow Enter key to trigger lookup
        document.getElementById('email-lookup')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                lookupCustomer();
            }
        });
    </script>
</body>
</html>