document.addEventListener("DOMContentLoaded", function () {

    // ==========================================
    // LOGIN POPUP
    // ==========================================

    const loginOverlay = document.getElementById("loginOverlay");
    const closeLogin = document.getElementById("closeLogin");
    const password = document.getElementById("password");
    const showPassword = document.getElementById("showPassword");
    const loginTriggers = document.querySelectorAll(".requires-login");
    let lastFocusedElement = null;


    function openLogin(event) {

        // IMPORTANT:
        // If there is no login popup on this page,
        // allow the normal Symfony link to work.
        if (!loginOverlay) {
            return;
        }

        event.preventDefault();
        lastFocusedElement = event.currentTarget;

        loginOverlay.classList.add("show");
        loginOverlay.setAttribute("aria-hidden", "false");
        document.body.classList.add("modal-open");

        window.requestAnimationFrame(function () {
            if (loginOverlay.classList.contains("show")) {
                document.getElementById("email")?.focus();
            }
        });
    }


    function closeLoginModal() {

        if (!loginOverlay) {
            return;
        }

        loginOverlay.classList.remove("show");
        loginOverlay.setAttribute("aria-hidden", "true");
        document.body.classList.remove("modal-open");
        lastFocusedElement?.focus();
    }


    loginTriggers.forEach(function (element) {
        element.addEventListener("click", openLogin);
    });


    if (closeLogin) {

        closeLogin.addEventListener("click", closeLoginModal);

    }


    if (loginOverlay) {

        loginOverlay.addEventListener("click", function (event) {

            if (event.target === loginOverlay) {
                closeLoginModal();
            }

        });

    }

    document.addEventListener("keydown", function (event) {

        if (!loginOverlay || !loginOverlay.classList.contains("show")) {
            return;
        }

        if (event.key === "Escape") {
            event.preventDefault();
            closeLoginModal();
            return;
        }

        if (event.key === "Tab") {
            const focusableElements = loginOverlay.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled])'
            );
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


    // ==========================================
    // SHOW / HIDE PASSWORD
    // ==========================================

    if (showPassword && password) {

        showPassword.addEventListener("click", function () {

            const isPassword = password.type === "password";

            password.type = isPassword ? "text" : "password";
            showPassword.textContent = isPassword ? "HIDE" : "SHOW";

        });

    }

});


// ==========================================
// CART
// ==========================================

document.addEventListener("DOMContentLoaded", function () {

    const cartButtons = document.querySelectorAll(".add-cart-button");
    const cartPopup = document.getElementById("cartPopup");
    const cartPopupClose = document.getElementById("cartPopupClose");
    const cartContinue = document.getElementById("cartContinue");
    const cartPopupMessage = document.getElementById("cartPopupMessage");


    cartButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const productId = this.dataset.productId;

            if (!productId) {

                alert("Invalid product.");
                return;

            }


            const formData = new FormData();

            formData.append("product_id", productId);


            fetch("actions/add-to-cart.php", {
                method: "POST",
                body: formData
            })

            .then(function (response) {

                if (!response.ok) {
                    throw new Error("Server error: " + response.status);
                }

                return response.json();

            })

            .then(function (data) {

                if (!data.success) {

                    if (data.logged_in === false) {

                        window.location.href =
                            "login.php?redirect=products.php";

                        return;
                    }

                    alert(
                        data.message ||
                        "Unable to add the product to your cart."
                    );

                    return;
                }


                const cartNumber =
                    document.querySelector(".cart-number");


                if (cartNumber) {

                    cartNumber.textContent = data.cart_count;

                }


                if (cartPopupMessage) {

                    cartPopupMessage.textContent =
                        data.product_name +
                        " was added to your cart.";

                }


                if (cartPopup) {

                    cartPopup.style.display = "flex";

                }

            })

            .catch(function (error) {

                console.error(
                    "Add to cart error:",
                    error
                );

                alert(
                    "Something went wrong while adding the product."
                );

            });

        });

    });


    if (cartPopupClose) {

        cartPopupClose.addEventListener("click", function () {

            if (cartPopup) {
                cartPopup.style.display = "none";
            }

        });

    }


    if (cartContinue) {

        cartContinue.addEventListener("click", function () {

            if (cartPopup) {
                cartPopup.style.display = "none";
            }

        });

    }


    if (cartPopup) {

        cartPopup.addEventListener("click", function (event) {

            if (event.target === cartPopup) {

                cartPopup.style.display = "none";

            }

        });

    }

});


// ==========================================
// LOGOUT POPUP
// ==========================================

document.addEventListener("DOMContentLoaded", function () {

    const logoutOverlay =
        document.getElementById("logoutOverlay");

    const logoutClose =
        document.getElementById("logoutClose");

    const logoutCancel =
        document.getElementById("logoutCancel");


    document.addEventListener("click", function (event) {

        const logoutButton =
            event.target.closest(".logout-button");


        if (!logoutButton) {
            return;
        }


        // IMPORTANT:
        // If there is no logout popup on this page,
        // allow the normal link to work.
        if (!logoutOverlay) {
            return;
        }


        event.preventDefault();


        logoutOverlay.classList.add("show");

        logoutOverlay.setAttribute(
            "aria-hidden",
            "false"
        );

    });

    document.addEventListener("DOMContentLoaded", function () {
        const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        const revealElements = document.querySelectorAll(
            ".problem .container > h2, .problem .section-intro, " +
            ".product .container > .eyebrow, .product .container > h2, " +
            ".reviews .container > .eyebrow, .reviews .container > h2, " +
            ".problem-grid article, .product-image-card, .feature, " +
            ".specs-grid > div, .review-grid .review, .footer-grid > div"
        );

        if (reducedMotion || !("IntersectionObserver" in window) || revealElements.length === 0) {
            return;
        }

        document.documentElement.classList.add("scroll-reveal-enabled");

        document.querySelectorAll(".problem-grid, .features, .specs-grid, .review-grid, .footer-grid")
            .forEach(function (group) {
                Array.from(group.children).forEach(function (element, index) {
                    element.style.setProperty(
                        "--scroll-reveal-delay",
                        Math.min(index, 3) * 80 + "ms"
                    );
                });
            });

        const observer = new IntersectionObserver(function (entries, currentObserver) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    currentObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: "0px 0px -40px 0px"
        });

        revealElements.forEach(function (element) {
            element.classList.add("scroll-reveal");
            observer.observe(element);
        });
    });


    if (logoutClose) {

        logoutClose.addEventListener("click", function () {

            logoutOverlay.classList.remove("show");

            logoutOverlay.setAttribute(
                "aria-hidden",
                "true"
            );

        });

    }


    if (logoutCancel) {

        logoutCancel.addEventListener("click", function () {

            logoutOverlay.classList.remove("show");

            logoutOverlay.setAttribute(
                "aria-hidden",
                "true"
            );

        });

    }


    if (logoutOverlay) {

        logoutOverlay.addEventListener("click", function (event) {

            if (event.target === logoutOverlay) {

                logoutOverlay.classList.remove("show");

                logoutOverlay.setAttribute(
                    "aria-hidden",
                    "true"
                );

            }

        });

    }


    document.addEventListener("keydown", function (event) {

        if (
            event.key === "Escape" &&
            logoutOverlay
        ) {

            logoutOverlay.classList.remove("show");

            logoutOverlay.setAttribute(
                "aria-hidden",
                "true"
            );

        }

    });

});