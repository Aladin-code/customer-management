// Form validation and utility functions
class FormValidator {
    static validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    static showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorDiv = document.getElementById(fieldId + '-error');

        field.classList.add('error');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
    }

    static hideError(fieldId) {
        const field = document.getElementById(fieldId);
        const errorDiv = document.getElementById(fieldId + '-error');

        field.classList.remove('error');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }

    static showSuccess(fieldId, message) {
        const successDiv = document.getElementById(fieldId + '-success');
        if (successDiv) {
            successDiv.textContent = message;
            successDiv.style.display = 'block';
        }
    }

    static hideSuccess(fieldId) {
        const successDiv = document.getElementById(fieldId + '-success');
        if (successDiv) {
            successDiv.style.display = 'none';
        }
    }

    static validateForm() {
        let isValid = true;

        // Validate required fields
        const requiredFields = ['lastname', 'firstname', 'email', 'city'];
        requiredFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (!field.value.trim()) {
                this.showError(fieldId, 'This field is required');
                isValid = false;
            } else {
                this.hideError(fieldId);
            }
        });

        // Validate email
        const emailField = document.getElementById('email');
        if (emailField.value.trim() && !this.validateEmail(emailField.value)) {
            this.showError('email', 'Please enter a valid email address');
            isValid = false;
        }

        return isValid;
    }
}

// File upload handling
class FileUpload {
    static handleFileSelect(event) {
        const file = event.target.files[0];
        const previewContainer = document.getElementById('image-preview');

        if (!file) return;

        // Validate file type
        if (!file.type.startsWith('image/jpeg')) {
            alert('Please select a JPEG image file');
            event.target.value = '';
            return;
        }

        // Validate file size (5MB limit)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            event.target.value = '';
            return;
        }

        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            previewContainer.innerHTML = `
                <img src="${e.target.result}" alt="Preview" class="preview-image">
                <p style="margin-top: 10px; color: #666;">File selected: ${file.name}</p>
            `;
        };
        reader.readAsDataURL(file);
    }

    static uploadFile() {
        const fileInput = document.getElementById('image');
        const file = fileInput.files[0];

        if (!file) {
            alert('Please select a file first');
            return;
        }

        const formData = new FormData();
        formData.append('image', file);

        // Show loading state
        const uploadBtn = document.getElementById('upload-btn');
        const originalText = uploadBtn.textContent;
        uploadBtn.textContent = 'Uploading...';
        uploadBtn.disabled = true;

        fetch('upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                FormValidator.showSuccess('image', 'Image uploaded successfully');
                document.getElementById('image-path').value = data.imagePath;
            } else {
                alert('Upload failed: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Upload failed. Please try again.');
        })
        .finally(() => {
            uploadBtn.textContent = originalText;
            uploadBtn.disabled = false;
        });
    }
}

// Form submission handling
function saveCustomer() {
    if (!FormValidator.validateForm()) {
        return;
    }

    const formData = new FormData(document.getElementById('customer-form'));

    // Show loading state
    const saveBtn = document.querySelector('.btn-primary');
    const originalText = saveBtn.textContent;
    saveBtn.textContent = 'Saving...';
    saveBtn.disabled = true;

    fetch('save-customer.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Customer saved successfully!');
            // Optionally redirect or clear form
        } else {
            alert('Save failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Save failed. Please try again.');
    })
    .finally(() => {
        saveBtn.textContent = originalText;
        saveBtn.disabled = false;
    });
}

function cancelChanges() {
    // Reset form except image
    const form = document.getElementById('customer-form');
    const imageInput = document.getElementById('image');
    const imagePath = document.getElementById('image-path').value;
    const imagePreview = document.getElementById('image-preview').innerHTML;

    form.reset();

    // Restore image data
    document.getElementById('image-path').value = imagePath;
    document.getElementById('image-preview').innerHTML = imagePreview;

    // Clear all error messages
    document.querySelectorAll('.error-message, .success-message').forEach(el => {
        el.style.display = 'none';
    });

    // Remove error classes
    document.querySelectorAll('.error').forEach(el => {
        el.classList.remove('error');
    });
}

// Calculator functionality for iframe communication
window.calculatorResult = '';

function receiveCalculatorInput(value) {
    // This function will be called from the calculator iframe
    const resultField = document.getElementById('calculator-result');
    if (resultField) {
        if (value === '=') {
            // Evaluate the expression safely
            try {
                // Simple validation to only allow numbers, +, -, and spaces
                const expression = window.calculatorResult.replace(/[^0-9+\-\s]/g, '');
                if (expression) {
                    const result = eval(expression);
                    resultField.value = result;
                    window.calculatorResult = '';
                }
            } catch (error) {
                resultField.value = 'Error';
                window.calculatorResult = '';
            }
        } else {
            window.calculatorResult += value;
        }
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Email validation on blur
    const emailField = document.getElementById('email');
    if (emailField) {
        emailField.addEventListener('blur', function() {
            if (this.value.trim() && !FormValidator.validateEmail(this.value)) {
                FormValidator.showError('email', 'Please enter a valid email address');
            } else {
                FormValidator.hideError('email');
            }
        });
    }

    // File input change handler
    const fileInput = document.getElementById('image');
    if (fileInput) {
        fileInput.addEventListener('change', FileUpload.handleFileSelect);
    }
});