<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\V1\StoreClientFinancingRequest;
use App\Services\ClientFinancing\ClientFinancingRequestSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class ClientFinancingFormController extends Controller
{
    public function create(): View
    {
        return view('client-financing.form', [
            'incomeProofOptions' => StoreClientFinancingRequest::INCOME_PROOF_OPTIONS,
        ]);
    }

    public function store(
        StoreClientFinancingRequest $request,
        ClientFinancingRequestSender $sender,
    ): RedirectResponse {
        if ($sender->resolveRecipients() === []) {
            Log::error('Client financing recipient email is not configured.');

            return redirect()
                ->route('client-financing.form')
                ->withInput()
                ->withErrors(['email_config' => 'لم يتم إعداد عنوان البريد بعد. اتصل بالدعم.']);
        }

        try {
            $sender->send($request->validated());
        } catch (Throwable $e) {
            Log::error('Failed to send client financing request email.', [
                'exception' => $e->getMessage(),
            ]);

            return redirect()
                ->route('client-financing.form')
                ->withInput()
                ->withErrors(['submission' => 'تعذر إرسال الطلب حاليًا. حاول مرة أخرى بعد قليل.']);
        }

        return redirect()->route('client-financing.form')->with('status', 'sent');
    }
}
