@if (setting('payment_iyzico_status') == 1)
    <x-plugins-payment::payment-method
        :name="IYZICO_PAYMENT_METHOD_NAME"
        paymentName="Iyzico"
        :supportedCurrencies="EleganceCMS\Iyzico\Facades\Iyzico::supportedCurrencyCodes()"
    >
    <img src="{{asset('vendor/core/plugins/iyzico/images/checkout/tr.png')}}" class="img-fluid mb-2" style="height:40px" alt="">
        <div class="iyzico-card-checkout border-rounded">
            <div class="form-group mb-2">
                <div class="input-wrapper">
                    <input
                        class="form-control"
                        id="iyzico-card-number"
                        name="card[card_number]"
                        type="text"
                        placeholder="{{ trans('plugins/iyzico::iyzico.card.card_number') }}"
                        value="5400637034748206"
                        maxlength="19"
                        autocomplete="off"
                        required
                    >
                    <div class="card-brands"></div>
                </div>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <div class="form-group">
                        <input
                            class="form-control"
                            id="iyzico-expiry"
                            name="card[expiration_date]"
                            type="text"
                            placeholder="{{ trans('plugins/iyzico::iyzico.card.expiration_date') }}"
                            maxlength="5"
                            value="09/29"
                            autocomplete="off"
                            required
                        >
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <div class="input-wrapper">
                            <input
                                class="form-control"
                                id="iyzico-cvc"
                                name="card[security_code]"
                                type="text"
                                placeholder="{{ trans('plugins/iyzico::iyzico.card.security_code') }}"
                                value="458"
                                maxlength="4"
                                autocomplete="off"
                                required
                            >
                            <div class="cvc-icon"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group mb-2">
                <input
                    class="form-control"
                    id="iyzico-card-name"
                    name="card[name]"
                    type="text"
                    placeholder="{{ trans('plugins/iyzico::iyzico.card.placeholder_name') }}"
                    autocomplete="off"
                    required
                >
            </div>
            <div class="form-group mt-3 d-none">
                <label class="form-label">{{ trans('plugins/iyzico::iyzico.installments.title') }}</label>
                <div id="installment-bank-info" class="mb-3"></div>
                <div id="installment-options" class="installment-grid"></div>
            </div>
        </div>
    </x-plugins-payment::payment-method>
@endif