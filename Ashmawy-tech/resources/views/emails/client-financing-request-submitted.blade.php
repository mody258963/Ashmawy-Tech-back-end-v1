<h2>New Client Financing Request</h2>

<p><strong>الاسم:</strong> {{ $payload['name'] }}</p>
<p><strong>رقم التليفون:</strong> {{ $payload['phone'] }}</p>
<p><strong>نوع العربيه:</strong> {{ $payload['car_type'] }}</p>
<p><strong>سعر العربيه:</strong> {{ number_format((float) $payload['car_price'], 2) }}</p>
<p><strong>المقدم:</strong> {{ number_format((float) $payload['down_payment'], 2) }}</p>

<p><strong>اختار اثباتات دخلك:</strong></p>
<ul>
    @foreach ($payload['income_proofs'] as $proof)
        <li>{{ $proof }}</li>
    @endforeach
</ul>

