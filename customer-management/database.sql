-- Create database
CREATE DATABASE IF NOT EXISTS customer_db;
USE customer_db;

-- Create customers table
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lastname VARCHAR(255) NOT NULL,
    firstname VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    city VARCHAR(255) NOT NULL,
    country VARCHAR(255) NOT NULL,
    image_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample data for testing
INSERT INTO customers (lastname, firstname, email, city, country, image_path) VALUES
('Doe', 'John', 'john.doe@example.com', 'New York', 'United States', NULL),
('Smith', 'Jane', 'jane.smith@example.com', 'Toronto', 'Canada', NULL),
('Tanaka', 'Hiroshi', 'hiroshi.tanaka@example.com', 'Tokyo', 'Japan', NULL);