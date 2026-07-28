/* cc_stripe_html.js: javascript for stripe draw_cc_html
 */
var elements = null;
var stripe = null;
var stripeSubmitBtn = null;
var paymentElement = null;
var stripePaystripePayPriorText = '';
var stripeCCForm = null;
const currencyFmtCC = new Intl.NumberFormat(config.locale, {
    style: 'currency',
    currency: config.currency,
});

function startCC(amount = 0) {
    startCCPay(amount);
}

async function stripeCardFormSubmit(e = null){
    if (e)
        e.preventDefault();
    // prevent double click
    if (stripeSubmitBtn.disabled)
        return;

    stripePayPriorText = stripeSubmitBtn.textContent;
    stripeSubmitBtn.disabled = true;
    stripeSubmitBtn.textContent = 'Processing...';

    // Trigger form validation and wallet collection
    const {error: submitError} = await elements.submit();
    if (submitError) {
        show_message(submitError.message, 'error', 'ccPayMessageDiv');
        stripeSubmitBtn.disabled = false;
        stripeSubmitBtn.textContent = stripePayPriorText;
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
        show_message(error.message, 'error', 'ccPayMessageDiv');
        stripeSubmitBtn.disabled = false;
        stripeSubmitBtn.textContent = stripePayPriorText;
        return;
    }

    if (config.allowedCCBrands.length > 0) {
        let brand = confirmationToken.payment_method_preview.card.brand;
        if (!config.allowedCCBrands.includes(brand)) {
            show_message("We cannot accept " + brand + " cards at this time, please try another card.", 'error', 'ccPayMessageDiv');
            stripeSubmitBtn.disabled = false;
            stripeSubmitBtn.textContent = stripePayPriorText;
            return;
        }
    }

    //console.log(confirmationToken);
    makePurchase(confirmationToken, "stripe-confirm");
}

function stripeRestoreBtnTxt() {
    stripeSubmitBtn.textContent = stripePayPriorText;
}

function startCCPay(amount = 0, formName = 'payment-form') {
    stripeSubmitBtn = document.getElementById('card-button');
    if (!stripe)
        stripe = Stripe(pkkey);
    if (paymentElement) {
        paymentElement.unmount();
        paymentElement.destroy();
        paymentElement = null;
    }
    // set listener
    stripeCCForm = document.getElementById('card-button');
    if (stripeCCForm)
        stripeCCForm.addEventListener('click', stripeCardFormSubmit);
    else
        show_message("Internal Credit Card Processing error -seek assistance", 'error', 'ccPayMessageDiv');

    if (amount > 0) {
        stripeSubmitBtn.textContent = "Pay " + currencyFmtCC.format(Number(amount / currencyMultiplier).toFixed(2));
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
    paymentElement = elements.create('payment', paymentElementsOptions);
    paymentElement.mount('#payment-element');
}

async function stripe_nextActions(data) {
    const { error, paymentIntent } = await stripe.handleNextAction({
        clientSecret: data.clientSecret
    });
    if (error) {
        show_message(error.message, 'error', 'ccPayMessageDiv');
        stripeSubmitBtn.disabled = false;
        stripeSubmitBtn.textContent = stripePayPriorText;
        return;
    }

    clear_message('ccPayMessageDiv');
    payActionComplete(paymentIntent, data.post, data.payParams);
}

function resetCCPay(div) {
    if (paymentElement) {
        paymentElement.unmount();
        paymentElement.destroy();
        paymentElement = null;
    }
    // set clear element from screen
    if (paymentElement) {
        paymentElement.unmount();
        paymentElement.destroy();
        paymentElement = null;
    }
    // set listener
    if (stripeCCForm) {
        stripeCCForm.removeEventListener('click', stripeCardFormSubmit);
    }
    // clear the HTML area
    if (div)
        div.innerHTML = '';

    // clear the message field
    clear_message('ccPayMessageDiv');
}
