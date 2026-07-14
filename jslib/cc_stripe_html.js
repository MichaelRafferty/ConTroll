/* cc_stripe_html.js: javascript for stripe draw_cc_html
 */
var elements = null;
const currencyFmt = new Intl.NumberFormat(config.locale, {
    style: 'currency',
    currency: config.currency,
});

function startCCPay(amount) {
    const stripeSubmitBtn = document.getElementById('purchase');
    stripe = Stripe(pkkey);
    // set listener
    <!-- Configure the Web SDK and Card payment intent -> elements -->
    document.getElementById('payment-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        // prevent
        if (stripeSubmitBtn.disabled)
            return;

        let priorText = stripeSubmitBtn.textContent;
        stripeSubmitBtn.disabled = true;
        stripeSubmitBtn.textContent = 'Processing...';

        // Trigger form validation and wallet collection
        const {error: submitError} = await elements.submit();
        if (submitError) {
            show_message(submitError.message, 'error', 'stripe-message');
            stripeSubmitBtn.disabled = false;
            stripeSubmitBtn.textContent = priorText;
            return;
        }

        // Create the ConfirmationToken using the details collected by the Payment Element
        const {error, confirmationToken} = await stripe.createConfirmationToken({
            elements,
            params: {
                return_url: config.payRedirectURL,
            },
        });

        if (error) {
            show_message(error.message, 'error', 'stripe-message');
            stripeSubmitBtn.disabled = false;
            stripeSubmitBtn.textContent = priorText;
            return;
        }

        makePurchase(confirmationToken, "stripe-confirm");
    });

    stripeSubmitBtn.textContent = "Pay " + currencyFmt.format(Number(amount/stripeCurrenMultiplier).toFixed(2));
    const options = {
        mode: 'payment',
        amount: amount,
        currency: stripeCurrency,
        paymentMethodCreation: 'manual',
    };
    elements = stripe.elements(options);

    const paymentElementsOptions = { layout: 'accordion' };
    const paymentElement = elements.create('payment', paymentElementsOptions);
    paymentElement.mount('#payment-element');
}
