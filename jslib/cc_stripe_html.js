/* cc_stripe_html.js: javascript for stripe draw_cc_html
 */
var elements = null;
var stripe = null;
var stripeSubmitBtn = null;
const currencyFmtCC = new Intl.NumberFormat(config.locale, {
    style: 'currency',
    currency: config.currency,
});

function startCCPay(amount = 0) {
    stripeSubmitBtn = document.getElementById('purchase');
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
                //setup_future_usage: "off_session",
            },
        });

        if (error) {
            show_message(error.message, 'error', 'stripe-message');
            stripeSubmitBtn.disabled = false;
            stripeSubmitBtn.textContent = priorText;
            return;
        }

        if (config.allowedCCBrands.length > 0) {
            let brand = confirmationToken.payment_method_preview.card.brand;
            if (!config.allowedCCBrands.includes(brand)) {
                show_message("We cannot accept " + brand + " cards at this time, please try another card.", 'error', 'stripe-message');
                stripeSubmitBtn.disabled = false;
                stripeSubmitBtn.textContent = priorText;
                return;
            }
        }

        //console.log(confirmationToken);
        makePurchase(confirmationToken, "stripe-confirm");
    });

    if (amount > 0) {
        stripeSubmitBtn.textContent = "Pay " + currencyFmtCC.format(Number(amount / currentMultiplier).toFixed(2));
    } else {
        stripeSubmitBtn.textContent = "Purchase";
    }
    const options = {
        mode: 'payment',
        amount: amount,
        currency: config.ccCurrency,
        paymentMethodCreation: 'manual',
    };
    elements = stripe.elements(options);

    const paymentElementsOptions = { layout: 'accordion' };
    const paymentElement = elements.create('payment', paymentElementsOptions);
    paymentElement.mount('#payment-element');
}

async function stripe_nextActions(data) {
    const { error, paymentIntent } = await stripe.handleNextAction({
        clientSecret: data.clientSecret
    });
    if (error) {
        show_message(error.message, 'error', 'stripe-message');
        stripeSubmitBtn.disabled = false;
        stripeSubmitBtn.textContent = priorText;
        return;
    }

    clear_message('stripe-message');
    portal.payActionComplete(paymentIntent);
}
