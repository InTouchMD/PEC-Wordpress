
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.pulse-health-form form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        let valid = true;
        const emailField = form.querySelector('[name="emailAddress"]');
        const phoneField = form.querySelector('[name="phone"]');
        const zipField = form.querySelector('[name="postalCode"]');
        const npiField = form.querySelector('[name="npi"]');
        const errorElements = form.querySelectorAll('.error');
        errorElements.forEach(el => el.remove());

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (emailField && !emailRegex.test(emailField.value)) {
            showError(emailField, "Please enter a valid email address.");
            valid = false;
        }

        if (phoneField && !/^\d{10}$/.test(phoneField.value)) {
            showError(phoneField, "Phone number must be 10 digits.");
            valid = false;
        }

        if (zipField && !/^\d{5}(-\d{4})?$/.test(zipField.value)) {
            showError(zipField, "Please enter a valid ZIP code.");
            valid = false;
        }

        if (npiField && !/^\d{10}$/.test(npiField.value)) {
            showError(npiField, "NPI must be a 10-digit number.");
            valid = false;
        }

        if (!valid) e.preventDefault();
    });

    function showError(field, message) {
        const error = document.createElement('div');
        error.className = 'error';
        error.textContent = message;
        field.parentNode.appendChild(error);
    }
});
