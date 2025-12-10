document.addEventListener('DOMContentLoaded', function() {
    // Hamburger Menu Toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const nav = document.querySelector('.main-navigation');

    if (menuToggle && nav) {
        menuToggle.addEventListener('click', function() {
            nav.classList.toggle('active');
            menuToggle.classList.toggle('active');

            const expanded = menuToggle.getAttribute('aria-expanded') === 'true' || false;
            menuToggle.setAttribute('aria-expanded', !expanded);
        });
    }

    // Lead Capture Form
    const leadForm = document.getElementById('hero-lead-form');
    if (leadForm) {
        leadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const emailInput = leadForm.querySelector('input[type="email"]');
            const submitBtn = leadForm.querySelector('button');
            const messageDiv = document.getElementById('form-message');

            if (!emailInput.value) return;

            // Loading state
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';
            messageDiv.innerHTML = '';
            messageDiv.className = '';

            const data = {
                email: emailInput.value,
                firstName: '', // Optional
                lastName: '',  // Optional
                message: 'Lead captured via Homepage Hero'
            };

            fetch(apertureTheme.api_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': apertureTheme.nonce
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.id || data.hash || data.message === 'Success') {
                    messageDiv.textContent = 'Thanks! We will be in touch shortly.';
                    messageDiv.classList.add('success');
                    emailInput.value = '';
                } else {
                    throw new Error(data.message || 'Error occurred');
                }
            })
            .catch(error => {
                messageDiv.textContent = 'Error: ' + error.message;
                messageDiv.classList.add('error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Get Started';
            });
        });
    }
});
