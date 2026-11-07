document.querySelectorAll('select[data-auto-submit]').forEach((select) => {
    select.addEventListener('change', () => select.form.requestSubmit());
});

const cartPopup = document.getElementById('cart-popup-overlay');

if (cartPopup) {
    const closeButton = document.getElementById('cart-popup-close');
    const continueButton = document.getElementById('cart-continue');
    const checkoutLink = document.getElementById('cart-checkout');
    const title = document.getElementById('cart-popup-title');
    const message = document.getElementById('cart-popup-message');
    const icon = document.getElementById('cart-popup-icon');
    let lastFocusedElement = null;

    const closePopup = () => {
        cartPopup.classList.remove('is-open', 'has-error');
        cartPopup.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        lastFocusedElement?.focus();
    };

    const showPopup = (productName) => {
        cartPopup.classList.remove('has-error');
        title.textContent = 'ADDED TO YOUR CART';
        message.textContent = `${productName} was added to your cart.`;
        icon.textContent = '✓';
        continueButton.textContent = 'CONTINUE SHOPPING';
        checkoutLink.hidden = false;
        cartPopup.classList.add('is-open');
        cartPopup.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        closeButton.focus();
    };

    const showError = (errorMessage) => {
        cartPopup.classList.add('has-error', 'is-open');
        cartPopup.setAttribute('aria-hidden', 'false');
        title.textContent = 'COULD NOT ADD ITEM';
        message.textContent = errorMessage;
        icon.textContent = '!';
        continueButton.textContent = 'CLOSE';
        checkoutLink.hidden = true;
        document.body.classList.add('modal-open');
        closeButton.focus();
    };

    document.querySelectorAll('form[data-cart-add]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const submitButton = form.querySelector('button[type="submit"]');
            if (!submitButton || submitButton.disabled) {
                return;
            }

            lastFocusedElement = submitButton;
            submitButton.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                let result;
                try {
                    result = await response.json();
                } catch {
                    throw new Error('The server returned an invalid response. Please try again.');
                }

                if (!response.ok) {
                    throw new Error(result.message || 'Unable to add this product to your cart.');
                }

                showPopup(result.productName || 'Your product');
            } catch (error) {
                console.error('Add to cart failed:', error);
                showError(error instanceof Error ? error.message : 'Unable to add this product to your cart.');
            } finally {
                submitButton.disabled = false;
            }
        });
    });

    closeButton.addEventListener('click', closePopup);
    continueButton.addEventListener('click', closePopup);

    cartPopup.addEventListener('click', (event) => {
        if (event.target === cartPopup) {
            closePopup();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (!cartPopup.classList.contains('is-open')) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            closePopup();
            return;
        }

        if (event.key === 'Tab') {
            const focusableElements = Array.from(
                cartPopup.querySelectorAll('button:not([disabled]), a[href]')
            ).filter((element) => !element.hidden);
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (event.shiftKey && document.activeElement === firstElement) {
                event.preventDefault();
                lastElement.focus();
            } else if (!event.shiftKey && document.activeElement === lastElement) {
                event.preventDefault();
                firstElement.focus();
            }
        }
    });
}
