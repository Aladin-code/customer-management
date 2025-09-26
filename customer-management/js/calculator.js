// Calculator functionality for iframe communication
class Calculator {
    constructor() {
        this.currentExpression = '';
        this.displayFrame = null;
        this.resultField = null;

        this.initializeCalculator();
    }

    initializeCalculator() {
        // Get references to result field and iframe
        this.resultField = document.getElementById('calculator-result');

        // Listen for messages from calculator iframes
        window.addEventListener('message', (event) => {
            this.handleMessage(event);
        });

        // Wait for iframes to load
        setTimeout(() => {
            this.displayFrame = document.querySelector('iframe[src="calculator-display.html"]');
        }, 1000);
    }

    handleMessage(event) {
        switch (event.data.type) {
            case 'calculator-calculate':
                this.calculateResult(event.data.expression);
                break;
            case 'calculator-equals':
                this.handleEquals();
                break;
        }
    }

    calculateResult(expression) {
        try {
            // Security: only allow numbers, +, -, and spaces
            const sanitized = expression.replace(/[^0-9+\-\s]/g, '');

            if (!sanitized || !this.isValidExpression(sanitized)) {
                throw new Error('Invalid expression');
            }

            // Use Function constructor for safer evaluation than eval
            const result = Function('"use strict"; return (' + sanitized + ')')();

            if (!isFinite(result)) {
                throw new Error('Invalid result');
            }

            // Update result field
            if (this.resultField) {
                this.resultField.value = result;
            }

            // Send result back to display iframe
            if (this.displayFrame) {
                this.displayFrame.contentWindow.postMessage({
                    type: 'calculator-result',
                    result: result
                }, '*');
            }

        } catch (error) {
            console.error('Calculator error:', error);

            if (this.resultField) {
                this.resultField.value = 'Error';
            }

            if (this.displayFrame) {
                this.displayFrame.contentWindow.postMessage({
                    type: 'calculator-error'
                }, '*');
            }
        }
    }

    isValidExpression(expression) {
        // Basic validation for mathematical expressions
        // Check for consecutive operators or starting/ending with operators
        const trimmed = expression.trim();

        if (trimmed === '') return false;

        // Should not start or end with operators
        if (/^[+\-]/.test(trimmed) && trimmed.length === 1) return false;
        if (/[+\-]$/.test(trimmed)) return false;

        // Should not have consecutive operators
        if (/[+\-]{2,}/.test(trimmed)) return false;

        return true;
    }

    handleEquals() {
        // This method can be used for additional processing when equals is pressed
        console.log('Calculator equals pressed');
    }

    clearCalculator() {
        this.currentExpression = '';

        if (this.resultField) {
            this.resultField.value = '';
        }

        if (this.displayFrame) {
            this.displayFrame.contentWindow.postMessage({
                type: 'calculator-clear'
            }, '*');
        }
    }
}

// Initialize calculator when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('calculator-result')) {
        window.calculator = new Calculator();
    }
});