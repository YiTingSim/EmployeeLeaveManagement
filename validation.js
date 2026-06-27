// NAME VALIDATION: Max 18 chars, only letters and spaces
function validateName(input) {
    const errorEl = input.parentElement.querySelector('.validation-error');
    const successEl = input.parentElement.querySelector('.field-success');
    const value = input.value.trim();

    // Clear previous states
    if (errorEl) errorEl.classList.remove('show');
    if (successEl) successEl.classList.remove('show');

    if (value.length === 0) {
        if (errorEl) {
            errorEl.textContent = 'Name is required.';
            errorEl.classList.add('show');
        }
        return false;
    }

    if (value.length > 18) {
        if (errorEl) {
            errorEl.textContent = 'Name must be 18 characters or less.';
            errorEl.classList.add('show');
        }
        return false;
    }

    // Check for numbers or special characters (only letters, spaces, and hyphens allowed)
    const nameRegex = /^[A-Za-z\s\-']+$/;
    if (!nameRegex.test(value)) {
        if (errorEl) {
            errorEl.textContent = 'Name can only contain letters, spaces, hyphens, and apostrophes.';
            errorEl.classList.add('show');
        }
        return false;
    }

    if (successEl) {
        successEl.textContent = '✓ Valid name.';
        successEl.classList.add('show');
    }
    return true;
}

// LEAVES VALIDATION (Must be >= 0)
document.querySelectorAll('input[name="leaves"]').forEach(input => {
    // Add feedback elements if missing
    if (!input.parentElement.querySelector('.validation-error')) {
        const error = document.createElement('div');
        error.className = 'validation-error';
        input.parentElement.appendChild(error);
    }
    if (!input.parentElement.querySelector('.field-success')) {
        const success = document.createElement('div');
        success.className = 'field-success';
        input.parentElement.appendChild(success);
    }

    input.addEventListener('input', function() {
        validateLeaves(this);
    });
    input.addEventListener('blur', function() {
        validateLeaves(this);
    });
});

// TEXT LENGTH VALIDATION: Max 250 characters
function validateTextLength(input, maxLength = 250) {
    const errorEl = input.parentElement.querySelector('.validation-error');
    const successEl = input.parentElement.querySelector('.field-success');
    const value = input.value;

    if (errorEl) errorEl.classList.remove('show');
    if (successEl) successEl.classList.remove('show');

    if (value.length > maxLength) {
        if (errorEl) {
            errorEl.textContent = `Text must be ${maxLength} characters or less. (${value.length}/${maxLength})`;
            errorEl.classList.add('show');
        }
        return false;
    }

    if (value.length > 0 && successEl) {
        successEl.textContent = `✓ ${value.length}/${maxLength} characters`;
        successEl.classList.add('show');
    }
    return true;
}

// AUTO-ATTACH VALIDATIONS
document.addEventListener('DOMContentLoaded', function() {
    
    // NAME VALIDATION
    document.querySelectorAll('input[name="name"]').forEach(input => {
        // Add feedback elements if missing
        if (!input.parentElement.querySelector('.validation-error')) {
            const error = document.createElement('div');
            error.className = 'validation-error';
            input.parentElement.appendChild(error);
        }
        if (!input.parentElement.querySelector('.field-success')) {
            const success = document.createElement('div');
            success.className = 'field-success';
            input.parentElement.appendChild(success);
        }
        
        input.addEventListener('input', function() {
            validateName(this);
        });
        input.addEventListener('blur', function() {
            validateName(this);
        });
    });

    // TEXT LENGTH VALIDATION (Max 250)
    document.querySelectorAll('textarea[name="reason"]').forEach(input => {
        // Add feedback elements if missing
        if (!input.parentElement.querySelector('.validation-error')) {
            const error = document.createElement('div');
            error.className = 'validation-error';
            input.parentElement.appendChild(error);
        }
        if (!input.parentElement.querySelector('.field-success')) {
            const success = document.createElement('div');
            success.className = 'field-success';
            input.parentElement.appendChild(success);
        }
        
        input.addEventListener('input', function() {
            validateTextLength(this, 250);
        });
        input.addEventListener('blur', function() {
            validateTextLength(this, 250);
        });
    });

    // Block form submission if name invalid
    const employeeForm = document.querySelector('form[action="employees.php"]');
    if (employeeForm) {
        employeeForm.addEventListener('submit', function(e) {
            const nameInput = this.querySelector('input[name="name"]');
            if (nameInput && !validateName(nameInput)) {
                e.preventDefault();
                nameInput.focus();
                return;
            }
            const leavesInput = this.querySelector('input[name="leaves"]');
            if (leavesInput && !validateLeaves(leavesInput)) {
                e.preventDefault();
                leavesInput.focus();
                return;
            }
        });
    }    

    // LEAVES VALIDATION: Must be >= 0
    function validateLeaves(input) {
        const errorEl = input.parentElement.querySelector('.validation-error');
        const successEl = input.parentElement.querySelector('.field-success');
        const value = parseInt(input.value, 10);

        // Clear previous states
        if (errorEl) errorEl.classList.remove('show');
        if (successEl) successEl.classList.remove('show');

        if (isNaN(value) || value < 0) {
            if (errorEl) {
                errorEl.textContent = 'Leave allocation cannot be negative. Please enter 0 or more.';
                errorEl.classList.add('show');
            }
            return false;
        }

        if (successEl) {
            successEl.textContent = '✓ Valid quota.';
            successEl.classList.add('show');
        }
        return true;
    }
});