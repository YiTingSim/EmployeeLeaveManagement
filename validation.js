// ============================================================
// 1. NAME VALIDATION: Max 18 chars, only letters and spaces
// ============================================================
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

// ============================================================
// 3. TEXT LENGTH VALIDATION: Max 250 characters
// ============================================================
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

// ============================================================
// 6. AUTO-ATTACH VALIDATIONS (Run on DOM ready)
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    
    // ---------- NAME VALIDATION ----------
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

    // ---------- TEXT LENGTH VALIDATION (Max 250) ----------
    document.querySelectorAll('textarea[name="reason"], textarea[name="report"]').forEach(input => {
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
        }
    });
}
});