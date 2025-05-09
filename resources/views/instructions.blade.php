<div class="iyzico-setup-guide container-fluid py-4">
    <!-- Başlık ve Kayıt -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-4">
                <h3 class="mb-0 text-primary">{{ trans('plugins/iyzico::iyzico.setup_instructions.title') }}</h3>
                <a href="https://merchant.iyzipay.com" target="_blank" class="btn btn-primary">
                    {{ trans('plugins/iyzico::iyzico.service_registration') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Kurulum Adımları -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="setup-section">
                <h4 class="section-title border-start border-primary border-4 ps-3 mb-4">Entegrasyon Adımları</h4>
                <ol class="setup-steps">
                    <li class="setup-step p-3 mb-3 bg-light rounded">
                        <span class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-3">1</span>
                        merchant.iyzipay.com adresinden Iyzico hesabınızı oluşturun
                    </li>
                    <li class="setup-step p-3 mb-3 bg-light rounded">
                        <span class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-3">2</span>
                        Hesap oluşturulduktan sonra API ve Gizli anahtarınızı panelden alın
                    </li>
                    <li class="setup-step p-3 mb-3 bg-light rounded">
                        <span class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-3">3</span>
                        API ve Gizli anahtarınızı aşağıdaki alanlara girin
                    </li>
                </ol>
            </div>
        </div>
    </div>
    <!-- Para Birimleri -->
    <div class="row">
        <div class="col-12">
            <div class="setup-section">
                <h4 class="section-title border-start border-primary border-4 ps-3 mb-4">{{ trans('plugins/iyzico::iyzico.currencies.title') }}</h4>
                <div class="currency-list bg-light p-3 rounded">
                    <p class="mb-0">{{ trans('plugins/iyzico::iyzico.currencies.supported') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.setup-steps {
    list-style: none;
    padding: 0;
    margin: 0;
}

.setup-step {
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
}

.setup-step:hover {
    transform: translateX(10px);
    background-color: #f8f9fa !important;
}

.step-number {
    width: 28px;
    height: 28px;
    font-size: 14px;
    min-width: 28px;
}

.section-title {
    font-size: 1.25rem;
    color: #333;
}

code {
    color: #333;
    background-color: #fff;
    border: 1px solid #dee2e6;
}
</style>