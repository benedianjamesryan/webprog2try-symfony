const logoutDialog = document.getElementById('logout-confirmation');
const confirmLogoutButton = document.getElementById('confirm-logout');
let pendingLogoutForm = null;

document.querySelectorAll('form[data-logout-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        pendingLogoutForm = form;
        logoutDialog.showModal();
    });
});

confirmLogoutButton.addEventListener('click', () => {
    if (!pendingLogoutForm) {
        return;
    }

    const form = pendingLogoutForm;
    pendingLogoutForm = null;
    logoutDialog.close();
    form.submit();
});

logoutDialog.addEventListener('close', () => {
    pendingLogoutForm = null;
});
