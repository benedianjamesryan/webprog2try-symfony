const paymentOptions = document.querySelectorAll('input[name="payment_method"]');
const gcashDetails = document.getElementById('gcashDetails');
const cardDetails = document.getElementById('cardDetails');
const codDetails = document.getElementById('codDetails');
const gcashNumber = document.getElementById('gcash_number');
const cardNumber = document.getElementById('card_number');
const cardName = document.getElementById('card_name');
const cardExpiry = document.getElementById('card_expiry');
const cardCvv = document.getElementById('card_cvv');

function updatePaymentDetails() {
    const selected = document.querySelector('input[name="payment_method"]:checked')?.value;
    gcashDetails.hidden = selected !== 'GCash';
    cardDetails.hidden = selected !== 'Card';
    codDetails.hidden = selected !== 'COD';

    gcashNumber.required = selected === 'GCash';
    cardNumber.required = selected === 'Card';
    cardName.required = selected === 'Card';
    cardExpiry.required = selected === 'Card';
    cardCvv.required = selected === 'Card';
}

paymentOptions.forEach((option) => option.addEventListener('change', updatePaymentDetails));

gcashNumber.addEventListener('input', () => {
    gcashNumber.value = gcashNumber.value.replace(/\D/g, '').slice(0, 11);
});

cardNumber.addEventListener('input', () => {
    const digits = cardNumber.value.replace(/\D/g, '').slice(0, 16);
    cardNumber.value = digits.replace(/(\d{4})(?=\d)/g, '$1 ');
});

cardCvv.addEventListener('input', () => {
    cardCvv.value = cardCvv.value.replace(/\D/g, '').slice(0, 4);
});

const currentDate = new Date();
cardExpiry.min = `${currentDate.getFullYear()}-${String(currentDate.getMonth() + 1).padStart(2, '0')}`;
updatePaymentDetails();
