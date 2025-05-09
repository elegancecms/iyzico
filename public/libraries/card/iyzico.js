'use strict';

const IyzicoPayment = {
    cardIcons: {
        visa: '/vendor/core/plugins/iyzico/images/visa.svg',
        mastercard: '/vendor/core/plugins/iyzico/images/mastercard.svg',
        amex: '/vendor/core/plugins/iyzico/images/amex.svg'
    },

    cardPatterns: {
        visa: /^4/,
        mastercard: /^(5[1-5]|2[2-7])/,
        amex: /^3[47]/
    },

    cvcIcons: {
        valid: `<svg class="p-CardCvcIcons-svg" width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor" role="img"><path opacity=".2" fill-rule="evenodd" clip-rule="evenodd" d="M15.337 4A5.493 5.493 0 0013 8.5c0 1.33.472 2.55 1.257 3.5H4a1 1 0 00-1 1v1a1 1 0 001 1h16a1 1 0 001-1v-.6a5.526 5.526 0 002-1.737V18a2 2 0 01-2 2H3a2 2 0 01-2-2V6a2 2 0 012-2h12.337zm6.707.293c.239.202.46.424.662.663a2.01 2.01 0 00-.662-.663z"></path><path opacity=".4" fill-rule="evenodd" clip-rule="evenodd" d="M13.6 6a5.477 5.477 0 00-.578 3H1V6h12.6z"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M18.5 14a5.5 5.5 0 110-11 5.5 5.5 0 010 11zm-2.184-7.779h-.621l-1.516.77v.786l1.202-.628v3.63h.943V6.22h-.008zm1.807.629c.448 0 .762.251.762.613 0 .393-.37.668-.904.668h-.235v.668h.283c.565 0 .95.282.95.691 0 .393-.377.66-.911.66-.393 0-.786-.126-1.194-.37v.786c.44.189.88.291 1.312.291 1.029 0 1.736-.526 1.736-1.288 0-.535-.33-.967-.88-1.14.472-.157.778-.573.778-1.045 0-.738-.652-1.241-1.595-1.241a3.143 3.143 0 00-1.234.267v.77c.378-.212.763-.33 1.132-.33zm3.394 1.713c.574 0 .974.338.974.778 0 .463-.4.785-.974.785-.346 0-.707-.11-1.076-.337v.809c.385.173.778.26 1.163.26.204 0 .392-.032.573-.08a4.313 4.313 0 00.644-2.262l-.015-.33a1.807 1.807 0 00-.967-.252 3 3 0 00-.448.032V6.944h1.132a4.423 4.423 0 00-.362-.723h-1.587v2.475a3.9 3.9 0 01.943-.133z"></path></svg>`,
        error: `<svg class="p-CardCvcIcons-svg" width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor" role="img"><path opacity=".4" fill-rule="evenodd" clip-rule="evenodd" d="M15.337 4A5.493 5.493 0 0013 8.5c0 1.33.472 2.55 1.257 3.5H4a1 1 0 00-1 1v1a1 1 0 001 1h16a1 1 0 001-1v-.6a5.526 5.526 0 002-1.737V18a2 2 0 01-2 2H3a2 2 0 01-2-2V6a2 2 0 012-2h12.337zm6.707.293c.239.202.46.424.662.663a2.01 2.01 0 00-.662-.663z"></path><path opacity=".6" fill-rule="evenodd" clip-rule="evenodd" d="M13.6 6a5.477 5.477 0 00-.578 3H1V6h12.6z"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M18.5 14a5.5 5.5 0 110-11 5.5 5.5 0 010 11zm0-1.719a1.031 1.031 0 100-2.062 1.031 1.031 0 000 2.062zm0-7.906a.687.687 0 00-.688.688V8.5a.687.687 0 101.375 0V5.062a.687.687 0 00-.687-.687z"></path></svg>`
    },

    init() {
        if ($('.iyzico-card-checkout').length > 0) {
            this.initCardBrands();
            this.initInputFormatting();
            this.initValidation();
            this.initCardTypeDetection();
            this.initCvcIcon();
            this.initInstallment();
        }
    },

    initCvcIcon() {
        const $cvcInput = $('input[name="card[security_code]"]');
        const $cvcIcon = $('.cvc-icon');

        // Set initial icon
        $cvcIcon.html(this.cvcIcons.valid);

        $cvcInput.on('input', function () {
            const value = $(this).val();
            const isValid = /^[0-9]{3,4}$/.test(value);

            $cvcIcon.html(isValid ? IyzicoPayment.cvcIcons.valid : IyzicoPayment.cvcIcons.error);
            $cvcIcon.toggleClass('error', !isValid);
        });
    },

    initCardBrands() {
        const $cardBrandsDiv = $('.card-brands');
        $cardBrandsDiv.empty();

        Object.entries(this.cardIcons).forEach(([type, url]) => {
            $cardBrandsDiv.append(`<img src="${url}" alt="${type}">`);
        });
    },

    initInputFormatting() {
        $('input[name="card[card_number]"]').on('input', function () {
            let value = $(this).val().replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formatted = value.match(/.{1,4}/g)?.join(' ') || '';
            $(this).val(formatted);
        });

        $('input[name="card[expiration_date]"]').on('input', function () {
            let value = $(this).val().replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            $(this).val(value);
        });

        $('input[name="card[security_code]"]').on('input', function () {
            let value = $(this).val().replace(/[^0-9]/g, '');
            $(this).val(value.substring(0, 4));
        });
    },

    initCardTypeDetection() {
        const $cardInput = $('input[name="card[card_number]"]');
        const $cardBrands = $('.card-brands img');

        $cardInput.on('input', function () {
            const cardNumber = $(this).val().replace(/\s+/g, '');
            let detectedType = null;

            $cardBrands.hide();

            for (const [type, pattern] of Object.entries(IyzicoPayment.cardPatterns)) {
                if (pattern.test(cardNumber)) {
                    detectedType = type;
                    break;
                }
            }

            if (detectedType) {
                $(`.card-brands img[alt="${detectedType}"]`).show();
            } else {
                $cardBrands.show();
            }
        });
    },

    initValidation() {
        $.validator.addMethod('cardNumber', function (value, element) {
            return this.optional(element) || /^[0-9]{16}$/.test(value.replace(/\s/g, ''));
        }, 'Please enter a valid card number');

        $.validator.addMethod('cardExpiry', function (value, element) {
            if (this.optional(element)) return true;
            const currentYear = new Date().getFullYear() % 100;
            const currentMonth = new Date().getMonth() + 1;

            const [month, year] = value.split('/').map(num => parseInt(num, 10));

            if (!/^(0[1-9]|1[0-2])\/([0-9]{2})$/.test(value)) return false;

            if (year < currentYear || (year === currentYear && month < currentMonth)) {
                return false;
            }

            return true;
        }, 'Please enter a valid expiration date');

        $.validator.addMethod('cardCVC', function (value, element) {
            return this.optional(element) || /^[0-9]{3,4}$/.test(value);
        }, 'Please enter a valid CVC');

        $('.payment-checkout-form').validate({
            rules: {
                'card[card_number]': {
                    required: true,
                    cardNumber: true
                },
                'card[name]': {
                    required: true,
                    minlength: 3
                },
                'card[expiration_date]': {
                    required: true,
                    cardExpiry: true
                },
                'card[security_code]': {
                    required: true,
                    cardCVC: true
                }
            },
            errorElement: 'span',
            errorClass: 'invalid-feedback',
            highlight: function (element) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function (element) {
                $(element).removeClass('is-invalid');
            },
            errorPlacement: function (error, element) {
                error.insertAfter(element);
            }
        });
    },

    initInstallment() {
        document.getElementById('iyzico-card-number').addEventListener('blur', function () {
            const cardNumber = this.value.replace(/\s+/g, '');
            const binNumber = cardNumber.substring(0, 6);
            const price = 10000;
            const conversationId = '{{ uniqid() }}';
            const locale = 'tr';

            if (binNumber.length === 6) {
                const installmentOptions = document.getElementById('installment-options');
                const bankInfo = document.getElementById('installment-bank-info');

                installmentOptions.innerHTML = '<div class="loading">Taksit seçenekleri yükleniyor...</div>';
                bankInfo.innerHTML = '';

                fetch('{{ route("payments.iyzico.installments") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ binNumber, price, locale, conversationId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data?.[0]) {
                            const cardInfo = data.data[0];

                            bankInfo.innerHTML = `
            <div class="bank-info">
                <span class="bank-name">${cardInfo.bankName}</span>
            </div>
        `;
                            const installmentOptionsArray = cardInfo.installmentPrices.filter(option => option.installmentNumber > 1);

                            if (installmentOptionsArray.length > 0) {
                                let singlePaymentHTML = '';
                                let installmentGridHTML = '';

                                cardInfo.installmentPrices.forEach(option => {
                                    const totalPrice = (option.totalPrice / 100).toLocaleString('tr-TR', {
                                        style: 'currency',
                                        currency: 'TRY'
                                    });

                                    const monthlyPayment = option.installmentNumber > 1
                                        ? (option.totalPrice / 100 / option.installmentNumber).toLocaleString('tr-TR', {
                                            style: 'currency',
                                            currency: 'TRY'
                                        })
                                        : null;

                                    const optionHTML = `
                    <label class="installment-option  ${option.installmentNumber === 1 ? 'col-span-full' : 'col-span-2'}">
                        <input type="radio" name="card[installment]" value="${option.installmentNumber}" 
                               ${option.installmentNumber === 1 ? 'checked' : ''}>
                        <div class="installment-card">
                            <div class="installment-header">
                                <span class="installment-count">${option.installmentNumber === 1 ? 'Tek Çekim' : `${option.installmentNumber} Taksit`}</span>
                            </div>
                            <div class="installment-body">
                                <div class="total-price">${totalPrice}</div>
                                ${monthlyPayment ? `
                                    <div class="monthly-payment">
                                        <span class="amount">${monthlyPayment}</span>
                                        <span class="label">x ${option.installmentNumber} Ay</span>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </label>
                `;

                                    if (option.installmentNumber === 1) {
                                        singlePaymentHTML = optionHTML;
                                    } else {
                                        installmentGridHTML += optionHTML;
                                    }
                                });

                                installmentOptions.innerHTML = `
                <div class="installment-grid">
                    ${singlePaymentHTML}
                    ${installmentGridHTML}
                </div>
            `;
                            }
                        }
                    })
                    .catch(err => {
                        installmentOptions.innerHTML = '<div class="alert alert-danger">Taksit bilgileri alınamadı</div>';
                    });
            }
        });
    }

};

$(document).ready(function () {
    IyzicoPayment.init();
});

document.addEventListener('payment-form-reloaded', function () {
    IyzicoPayment.init();
});