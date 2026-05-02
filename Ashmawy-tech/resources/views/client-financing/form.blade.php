@extends('layouts.financing-public')

@section('title', 'طلب تمويل العربية')

@push('styles')
<style>
    :root {
        --accent: #c41e3a;
        --accent-hover: #9e1730;
        --surface: #ffffff;
        --text: #0f172a;
        --muted: #64748b;
        --ring: rgba(196, 30, 58, 0.25);
        --radius: 1.25rem;
    }
    *, *::before, *::after { box-sizing: border-box; }
    body {
        margin: 0;
        min-height: 100vh;
        font-family: 'Cairo', system-ui, sans-serif;
        color: var(--text);
        background:
            radial-gradient(ellipse 120% 80% at 100% -20%, rgba(196, 30, 58, 0.18), transparent 50%),
            radial-gradient(ellipse 100% 60% at 0% 100%, rgba(30, 64, 175, 0.12), transparent 45%),
            linear-gradient(165deg, #0f172a 0%, #1e293b 40%, #0f172a 100%);
        padding: clamp(1rem, 4vw, 2.5rem) 1rem 3rem;
    }
    .page {
        max-width: 32rem;
        margin: 0 auto;
    }
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 600;
        background: rgba(255,255,255,0.12);
        color: #e2e8f0;
        margin-bottom: 1rem;
    }
    .brand {
        text-align: center;
        margin-bottom: 1.5rem;
    }
    .brand h1 {
        margin: 0 0 0.35rem;
        font-size: clamp(1.5rem, 5vw, 1.85rem);
        font-weight: 700;
        color: #f8fafc;
        letter-spacing: -0.02em;
    }
    .brand p {
        margin: 0;
        color: #94a3b8;
        font-size: 0.95rem;
        line-height: 1.5;
    }
    .card {
        background: var(--surface);
        border-radius: var(--radius);
        box-shadow:
            0 4px 6px -1px rgba(0,0,0,0.08),
            0 25px 50px -12px rgba(0,0,0,0.25);
        padding: clamp(1.25rem, 4vw, 2rem);
        border: 1px solid rgba(255,255,255,0.08);
    }
    .alert {
        padding: 0.85rem 1rem;
        border-radius: 0.75rem;
        margin-bottom: 1.25rem;
        font-size: 0.9rem;
        line-height: 1.55;
    }
    .alert-success {
        background: #ecfdf5;
        border: 1px solid #6ee7b7;
        color: #065f46;
    }
    .alert-error {
        background: #fef2f2;
        border: 1px solid #fca5a5;
        color: #991b1b;
    }
    .field { margin-bottom: 1.1rem; }
    .field label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.35rem;
        font-size: 0.92rem;
    }
    .field input[type="text"],
    .field input[type="tel"],
    .field input[type="number"] {
        width: 100%;
        padding: 0.65rem 0.85rem;
        border-radius: 0.65rem;
        border: 1px solid #e2e8f0;
        font: inherit;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .field input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--ring);
    }
    .hint {
        font-size: 0.78rem;
        color: var(--muted);
        margin-top: 0.25rem;
    }
    .choices-title {
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-size: 0.92rem;
    }
    .choice-list {
        display: grid;
        gap: 0.5rem;
    }
    .choice {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        padding: 0.75rem 0.85rem;
        border-radius: 0.65rem;
        border: 1px solid #e2e8f0;
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s;
        user-select: none;
    }
    .choice:hover { background: #f8fafc; border-color: #cbd5e1; }
    .choice:has(input:focus-visible) {
        outline: 2px solid var(--accent);
        outline-offset: 2px;
    }
    .choice input {
        margin-top: 0.2rem;
        width: 1.1rem;
        height: 1.1rem;
        accent-color: var(--accent);
        flex-shrink: 0;
    }
    .choice span { flex: 1; line-height: 1.5; font-size: 0.9rem; }
    .submit-wrap { margin-top: 1.5rem; }
    .btn-submit {
        width: 100%;
        padding: 0.85rem 1.25rem;
        border: none;
        border-radius: 0.65rem;
        background: linear-gradient(180deg, var(--accent) 0%, var(--accent-hover) 100%);
        color: #fff;
        font-family: inherit;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(196, 30, 58, 0.35);
        transition: transform 0.1s, box-shadow 0.15s;
    }
    .btn-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(196, 30, 58, 0.4);
    }
    .btn-submit:active { transform: translateY(0); }
    .error-text {
        font-size: 0.8rem;
        color: #b91c1c;
        margin-top: 0.3rem;
    }
</style>
@endpush

@section('content')
<div class="page">
    <div class="brand">
        <div class="badge">نموذج إلكتروني • بدون تسجيل دخول</div>
        <h1>طلب تمويل العربية</h1>
        <p>املأ البيانات التالية، وسنتواصل معك بعد استلام الطلب.</p>
    </div>

    <div class="card">
        @if (session('status') === 'sent')
            <div class="alert alert-success">
                تم إرسال الطلب بنجاح. شكرًا لتواصلك معنا؛ سنعود إليك في أقرب وقت.
            </div>
        @endif

        @if ($errors->has('submission'))
            <div class="alert alert-error">{{ $errors->first('submission') }}</div>
        @endif
        @if ($errors->has('email_config'))
            <div class="alert alert-error">{{ $errors->first('email_config') }}</div>
        @endif

        <form action="{{ route('client-financing.submit') }}" method="post" novalidate>
            @csrf

            <div class="field">
                <label for="name">الاسم <span aria-hidden="true" style="color:var(--accent)">*</span></label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name" maxlength="255">
                @error('name')<div class="error-text">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="phone">رقم التليفون <span aria-hidden="true" style="color:var(--accent)">*</span></label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required autocomplete="tel" maxlength="30" inputmode="tel">
                @error('phone')<div class="error-text">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="car_type">نوع العربيه <span aria-hidden="true" style="color:var(--accent)">*</span></label>
                <input id="car_type" type="text" name="car_type" value="{{ old('car_type') }}" required maxlength="255">
                @error('car_type')<div class="error-text">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="car_price">سعر العربيه <span aria-hidden="true" style="color:var(--accent)">*</span></label>
                <input id="car_price" type="number" name="car_price" value="{{ old('car_price') }}" required min="0" step="0.01" inputmode="decimal">
                <div class="hint">بالجنيه المصري أو العملة التي تستخدمونها في التسعير.</div>
                @error('car_price')<div class="error-text">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="down_payment">المقدم <span aria-hidden="true" style="color:var(--accent)">*</span></label>
                <input id="down_payment" type="number" name="down_payment" value="{{ old('down_payment') }}" required min="0" step="0.01" inputmode="decimal">
                @error('down_payment')<div class="error-text">{{ $message }}</div>@enderror
            </div>

            <fieldset class="field" style="border:none;margin:0;padding:0;">
                <legend class="choices-title">اختار إثباتات دخلك <span aria-hidden="true" style="color:var(--accent)">*</span> — يمكن اختيار أكثر من خيار</legend>
                <div class="choice-list">
                    @foreach ($incomeProofOptions as $idx => $label)
                        <label class="choice">
                            <input type="checkbox" name="income_proofs[]" value="{{ $label }}"
                                   @checked(is_array(old('income_proofs')) && in_array($label, old('income_proofs', []), true))>
                            <span>{{ ($idx + 1) }}. {{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('income_proofs')<div class="error-text">{{ $message }}</div>@enderror
                @for ($i = 0; $i < count($incomeProofOptions); $i++)
                    @error('income_proofs.'.$i)<div class="error-text">{{ $message }}</div>@enderror
                @endfor
            </fieldset>

            <div class="submit-wrap">
                <button type="submit" class="btn-submit">إرسال الطلب</button>
            </div>
        </form>
    </div>
</div>
@endsection
