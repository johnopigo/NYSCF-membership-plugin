jQuery(document).ready(function($) {
    // Checking stetcode for registration
    $('#membership-state-code-checkup').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let errorHolder = $('.state-error');

        $.ajax({
            url: statefragment.ajaxurl,
            type: 'POST',
            data: $(this).serialize(),
            beforeSend: () => {
                form.find('input[type="submit"]').prop("disabled", true).val("Checking...");
                errorHolder.html('');
            },
            success: function(response) {
                form.find('input[type="submit"]').prop("disabled", false);

                if(response.success){
                    if (response.data.status === 'valid') {
                        errorHolder.html(`<div class="form-success">${response.data.msg}</div>`);
                        setTimeout(() => {
                            form.replaceWith(response.data.form);
                        }, 1000);
                    }else{
                        form.find('input[type="submit"]').prop("disabled", false).val("Check State Code");
                        errorHolder.html(`<div class="form-error">${response.data.msg}</div>`);
                    }
                }else{
                    form.find('input[type="submit"]').prop("disabled", false).val("Check State Code");
                    errorHolder.html(`<div class="form-error">${response.data}</div>`);
                }
            },
            error: function() {
                form.find('input[type="submit"]').prop("disabled", false).val("Check State Code");
                errorHolder.html(`<div class="form-error">Invalid state code</div>`);
            }
        });
    });

    // Signup form submission
    $(document).on('submit', "form#membership-signup-form", function(e) {
        e.preventDefault();
        let form = $(this);
        let errorHolder = $('.state-error');

        $.ajax({
            url: statefragment.ajaxurl,
            type: 'POST',
            data: $(this).serialize(),
            beforeSend: () => {
                form.find('input[type="submit"]').prop("disabled", true).val("Requesting...");
                errorHolder.html('');
            },
            success: function(response) {
                if(response.success){
                    form.find('input[type="submit"]').prop("disabled", false);
                    if (response.data.status === 'payment_due') {
                        const formData = response.data.formData;
                        form.html(`<div class="successBox">
                            <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" width="100px" height="100px"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M2 16C2 8.26801 8.26801 2 16 2C23.732 2 30 8.26801 30 16C30 23.732 23.732 30 16 30C8.26801 30 2 23.732 2 16ZM20.9502 14.2929C21.3407 13.9024 21.3407 13.2692 20.9502 12.8787C20.5597 12.4882 19.9265 12.4882 19.536 12.8787L14.5862 17.8285L12.4649 15.7071C12.0744 15.3166 11.4412 15.3166 11.0507 15.7071C10.6602 16.0977 10.6602 16.7308 11.0507 17.1213L13.8791 19.9498C14.2697 20.3403 14.9028 20.3403 15.2933 19.9498L20.9502 14.2929Z" fill="#0b7c26"></path> </g></svg>
                            <h3>${response.data.msg}</h3>
                            <div class="paymentInfo" style="text-align: center;">
                                <p>Payment due: NGN ${formData.payableAmount}</p>
                                <h4>${formData.state_code}</h4>
                            </div>
                            <button data-state="${formData.state_code}" class="paymentProceed">Proceed to Payment</button>
                            </div>`);
                    }else{
                        form.find('input[type="submit"]').prop("disabled", false).val("Request Membership");
                        errorHolder.html(`<div class="form-error">${response.data.msg}</div>`);
                    }
                }else{
                    form.find('input[type="submit"]').prop("disabled", false).val("Request Membership");
                    errorHolder.html(`<div class="form-error">${response.data}</div>`);

                    if(response.status && response.status === 'login'){ // If user already exist - redirect to login
                        setTimeout(() => {
                            window.location.href = response.redirect;
                        }, 1000);
                    }
                }
            },
            error: function(error) {
                form.find('input[type="submit"]').prop("disabled", false).val("Request Membership");
                errorHolder.html(`<div class="form-error">${error.data}</div>`);
            }
        });
    });

    // show paystack payment form
    function showPaystackForm(stateCode) {
        // Call your server-side code to create a transaction
        fetch(statefragment.ajaxurl + '?action=paystack_payment', {
            method: 'POST',
            body: new URLSearchParams({
                stateCode: stateCode,
                referrer: location.href,
            }),
        })
        .then(data => data.json())
        .then(response => {
            if (response.success && response.data.authorization_url) {
                // Open Paystack's payment page in iframe
                const handler = PaystackPop.setup({
                    key: statefragment.pkey,
                    email: response.email,
                    amount: response.payableAmount,
                    currency: 'NGN',
                    ref: response.data.reference,
                    callback: function(response) {
                        // Payment successful
                        $(".membership-form").removeClass("error").html(`<div class="successBox">
                            <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" width="100px" height="100px"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M2 16C2 8.26801 8.26801 2 16 2C23.732 2 30 8.26801 30 16C30 23.732 23.732 30 16 30C8.26801 30 2 23.732 2 16ZM20.9502 14.2929C21.3407 13.9024 21.3407 13.2692 20.9502 12.8787C20.5597 12.4882 19.9265 12.4882 19.536 12.8787L14.5862 17.8285L12.4649 15.7071C12.0744 15.3166 11.4412 15.3166 11.0507 15.7071C10.6602 16.0977 10.6602 16.7308 11.0507 17.1213L13.8791 19.9498C14.2697 20.3403 14.9028 20.3403 15.2933 19.9498L20.9502 14.2929Z" fill="#0b7c26"></path> </g></svg>
                            <h3>Congratulation!</h3>
                            <p>Your payment was successful.</p>
                            <p style="color: red;">Don't reload the page! You will be redirected to the dashboard page after verifying your payment.</p>
                            </div>`);
                        // Checking payment status
                        fetch(statefragment.ajaxurl + '?action=verify_payment', {
                            method: 'POST',
                            body: new URLSearchParams({
                                reference: response.reference,
                                nonce: statefragment.nonce
                            }),
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.href = statefragment.redirectUrl;
                            } else {
                                alert('Payment verification failed.');
                                $(document).find('input[type="submit"]').prop("disabled", false).val("Request Membership");
                            }
                        })
                    },
                    onClose: function() {
                        alert('Transaction was cancelled');
                        $(document).find('input[type="submit"]').prop("disabled", false).val("Request Membership");
                    }
                });
                handler.openIframe();
            } else {
                alert('Payment initialization failed.');
                $(document).find('input[type="submit"]').prop("disabled", false).val("Request Membership");
            }
        })
        .catch(error => {
            $(".membership-form").html('<p>An error occurred. Please login and try again.</p>');
        });
    }

    // Proceed to payment
    $(document).off('click', '.paymentProceed').on('click', '.paymentProceed', function() {
        const stateCode = $(this).data('state');
        $(this).prop("disabled", true).text("Processing...");
        showPaystackForm(stateCode);
    });

    // show paystack loan payment form
    function showPaystackLoanForm() {
        // Call your server-side code to create a transaction
        fetch(statefragment.ajaxurl + '?action=loan_payment', {
            method: 'POST',
            body: new URLSearchParams({
                referrer: location.href,
            }),
        })
        .then(data => data.json())
        .then(response => {
            if (response.success && response.data.authorization_url) {
                // Open Paystack's payment page in iframe
                const handler = PaystackPop.setup({
                    key: statefragment.pkey,
                    email: response.email,
                    amount: response.payableAmount,
                    currency: 'NGN',
                    ref: response.data.reference,
                    callback: function(response) {
                        // Checking payment status
                        fetch(statefragment.ajaxurl + '?action=verify_payment', {
                            method: 'POST',
                            body: new URLSearchParams({
                                reference: response.reference,
                                loan_payment: true
                            }),
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                $(".membership-form").removeClass("error").html(`<div class="successBox">
                                    <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" width="100px" height="100px"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M2 16C2 8.26801 8.26801 2 16 2C23.732 2 30 8.26801 30 16C30 23.732 23.732 30 16 30C8.26801 30 2 23.732 2 16ZM20.9502 14.2929C21.3407 13.9024 21.3407 13.2692 20.9502 12.8787C20.5597 12.4882 19.9265 12.4882 19.536 12.8787L14.5862 17.8285L12.4649 15.7071C12.0744 15.3166 11.4412 15.3166 11.0507 15.7071C10.6602 16.0977 10.6602 16.7308 11.0507 17.1213L13.8791 19.9498C14.2697 20.3403 14.9028 20.3403 15.2933 19.9498L20.9502 14.2929Z" fill="#0b7c26"></path> </g></svg>
                                    <h3>Congratulation!</h3>
                                    <p>Your payment was successful.</p>
                                    </div>`);
                            } else {
                                alert('Payment verification failed.');
                                $(document).find('input[type="submit"]').prop("disabled", false).val("Pay Now");
                            }
                        })
                    },
                    onClose: function() {
                        alert('Transaction was cancelled');
                        $(document).find('input[type="submit"]').prop("disabled", false).val("Pay Now");
                    }
                });
                handler.openIframe();
            } else {
                alert('Payment initialization failed.');
                $(document).find('input[type="submit"]').prop("disabled", false).val("Pay Now");
            }
        })
        .catch(error => {
            $(".membership-form").html('<p>An error occurred. Please login and try again.</p>');
        });
    }

    // Proceed to loan payment
    $(document).off('click', '.loanform-action').on('click', '.loanform-action', function() {
        $(this).prop("disabled", true).val("Processing...");
        showPaystackLoanForm();
    });

    const idCardElement = document.querySelector(".user-id-card");
    if(idCardElement){
        html2canvas(idCardElement, {
            scale: 2, // Increase the scale factor (2x resolution)
            useCORS: true // Enable cross-origin resource sharing if external assets are used
        }).then(canvas => {
            // Convert the canvas to an image
            const imgData = canvas.toDataURL("image/png");
            $(".idcard_preview").append(`<img width="100%" src="${imgData}">`);
            $(".downloadcard-btn").removeClass("hidden-view");
        });
    }

    $('#download-image-card').on('click', function() {
        let imgURL = $(".idcard_preview").find("img").attr("src");

        if(imgURL){
            var a = document.createElement('a');
            a.href = imgURL;
            a.download = "user-id-card.png";
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    });
    $('#download-pdf-card').on('click', function() {
        let imgURL = $(".idcard_preview").find("img").attr("src");

        if(imgURL){
            const options = {
                margin: 1,
                filename: 'user-id-card.pdf',
                html2canvas: { scale: 3 }, // Improves resolution
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(options).from(document.querySelector(".idcard_preview")).save();
        }
    });

    // Changing avatar
    const fileInput = $('#user-avatar-file');
    const previewImage = $('#user-avatar-preview');
    
    fileInput.on('change', function() {
        const file = this.files[0];

        if (file) {
            // Preview the uploaded image
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.attr('src', e.target.result);
                $('.udb-avatar img').attr("src", e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });
});
