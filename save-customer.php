<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate required fields
$requiredFields = ['lastname', 'firstname', 'email', 'city', 'country'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
        exit;
    }
}

// Sanitize input data
$lastname = sanitizeInput($_POST['lastname']);
$firstname = sanitizeInput($_POST['firstname']);
$email = sanitizeInput($_POST['email']);
$city = sanitizeInput($_POST['city']);
$country = sanitizeInput($_POST['country']);
$imagePath = isset($_POST['image_path']) ? sanitizeInput($_POST['image_path']) : null;

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Validate country selection
$allowedCountries = ['United States', 'Canada', 'Japan', 'United Kingdom', 'France', 'Germany'];
if (!in_array($country, $allowedCountries)) {
    echo json_encode(['success' => false, 'message' => 'Invalid country selection']);
    exit;
}

try {
    $conn = getDBConnection();

    // Check if customer exists (update) or new (insert)
    $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing customer
        $customer = $result->fetch_assoc();
        $stmt->close();

        $updateStmt = $conn->prepare("UPDATE customers SET lastname = ?, firstname = ?, city = ?, country = ?, image_path = ?, updated_at = CURRENT_TIMESTAMP WHERE email = ?");
        $updateStmt->bind_param("ssssss", $lastname, $firstname, $city, $country, $imagePath, $email);

        if ($updateStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Customer updated successfully', 'action' => 'update']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update customer']);
        }

        $updateStmt->close();
    } else {
        // Insert new customer
        $stmt->close();

        $insertStmt = $conn->prepare("INSERT INTO customers (lastname, firstname, email, city, country, image_path) VALUES (?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("ssssss", $lastname, $firstname, $email, $city, $country, $imagePath);

        if ($insertStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Customer created successfully', 'action' => 'insert', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create customer']);
        }

        $insertStmt->close();
    }

    $conn->close();

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>