<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentRequest;
use App\Models\Payment;
use App\Repository\Order\OrderRepository;
use App\Repository\Payment\PaymentRepository;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly OrderRepository $orders,
    ) {}

    public function index(): View
    {
        $cards = [];
        if (! auth()->user()?->isModerator()) {
            $now = CarbonImmutable::now();
            $todayStart = $now->startOfDay();
            $weekStart = $now->startOfWeek();
            $monthStart = $now->startOfMonth();

            $sumToday = Payment::query()->where('paid_at', '>=', $todayStart)->sum('amount');
            $sumWeek = Payment::query()->where('paid_at', '>=', $weekStart)->sum('amount');
            $sumMonth = Payment::query()->where('paid_at', '>=', $monthStart)->sum('amount');

            $cards = [
                [
                    'label' => __('messages.payments_today'),
                    'value' => number_format((float) $sumToday, 2),
                    'class' => 'bg-success',
                    'icon' => 'fas fa-money-bill-wave',
                    'url' => route('admin.payments.index'),
                ],
                [
                    'label' => __('messages.payments_this_week'),
                    'value' => number_format((float) $sumWeek, 2),
                    'class' => 'bg-info',
                    'icon' => 'fas fa-calendar-week',
                    'url' => route('admin.payments.index'),
                ],
                [
                    'label' => __('messages.payments_this_month'),
                    'value' => number_format((float) $sumMonth, 2),
                    'class' => 'bg-warning',
                    'icon' => 'fas fa-calendar-alt',
                    'url' => route('admin.payments.index'),
                ],
            ];
        }

        return view('admin.payments.index', [
            'payments' => $this->payments->paginate(20),
            'cards' => $cards,
        ]);
    }

    public function create(): View
    {
        return view('admin.payments.create', [
            'orders' => $this->orders->all(),
        ]);
    }

    public function store(PaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['received_by'] = $request->user()->id;
        $this->payments->create($data);

        return redirect()->route('admin.payments.index')->with('status', __('messages.payment_recorded'));
    }

    public function edit(int $payment): View
    {
        return view('admin.payments.edit', [
            'payment' => $this->payments->find($payment),
            'orders' => $this->orders->all(),
        ]);
    }

    public function update(PaymentRequest $request, int $payment): RedirectResponse
    {
        $data = $request->validated();
        $data['received_by'] = $request->user()->id;
        $this->payments->update($payment, $data);

        return redirect()->route('admin.payments.index')->with('status', __('messages.payment_updated'));
    }

    public function destroy(int $payment): RedirectResponse
    {
        $this->payments->delete($payment);

        return redirect()->route('admin.payments.index')->with('status', __('messages.payment_deleted'));
    }
}
