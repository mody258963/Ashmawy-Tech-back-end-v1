<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderNoteRequest;
use App\Http\Requests\Admin\OrderRequest;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SparePart;
use App\Repository\Branch\BranchRepository;
use App\Repository\Customer\CustomerRepository;
use App\Repository\Device\DeviceRepository;
use App\Repository\Order\OrderRepository;
use App\Repository\OrderNote\OrderNoteRepository;
use App\Repository\OrderStatusHistory\OrderStatusHistoryRepository;
use App\Repository\User\UserRepository;
use App\Services\Inventory\SparePartStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly CustomerRepository $customers,
        private readonly DeviceRepository $devices,
        private readonly UserRepository $users,
        private readonly BranchRepository $branches,
        private readonly OrderNoteRepository $orderNotes,
        private readonly OrderStatusHistoryRepository $statusHistory,
        private readonly SparePartStockService $stockService,
    ) {}

    public function index(): View
    {
        $total = Order::query()->count();
        $open = Order::query()->whereNotIn('status', ['delivered', 'cancelled'])->count();
        $delivered = Order::query()->where('status', 'delivered')->count();
        $waitingApproval = Order::query()->where('status', 'waiting_approval')->count();

        return view('admin.orders.index', [
            'cards' => [
                [
                    'label' => 'Total orders',
                    'value' => $total,
                    'class' => 'bg-info',
                    'icon' => 'fas fa-clipboard-list',
                    'url' => route('admin.orders.index'),
                ],
                [
                    'label' => 'Open orders',
                    'value' => $open,
                    'class' => 'bg-warning',
                    'icon' => 'fas fa-tools',
                    'url' => route('admin.orders.index'),
                ],
                [
                    'label' => 'Waiting approval',
                    'value' => $waitingApproval,
                    'class' => 'bg-secondary',
                    'icon' => 'fas fa-clock',
                    'url' => route('admin.orders.index'),
                ],
                [
                    'label' => 'Delivered',
                    'value' => $delivered,
                    'class' => 'bg-success',
                    'icon' => 'fas fa-check',
                    'url' => route('admin.orders.index'),
                ],
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.orders.create', $this->formLookups());
    }

    public function store(OrderRequest $request): RedirectResponse
    {
        $data = $request->validatedOrderData();
        $spareParts = $request->validatedSpareParts();
        $order = DB::transaction(function () use ($data, $spareParts, $request): Order {
            $data['order_number'] = $this->uniqueOrderNumber();
            $order = $this->orders->create($data);
            $this->statusHistory->create([
                'order_id' => $order->id,
                'from_status' => '',
                'to_status' => $order->status,
                'changed_by' => $request->user()->id,
                'changed_at' => now(),
            ]);
            $this->syncOrderParts($order, $spareParts, (int) $request->user()->id);
            $this->ensureDeliveredPayment($order, (int) $request->user()->id);

            return $order;
        });

        return redirect()->route('admin.orders.show', $order)->with('status', 'Order created.');
    }

    public function show(int $order): View
    {
        $orderModel = $this->orders->find($order);
        $orderModel->load('spareParts');

        return view('admin.orders.show', [
            'order' => $orderModel,
            'notes' => $this->orderNotes->forOrder($orderModel->id),
            'histories' => $this->statusHistory->forOrder($orderModel->id),
        ]);
    }

    public function edit(int $order): View
    {
        return view('admin.orders.edit', array_merge(
            ['order' => $this->orders->find($order)],
            $this->formLookups(),
        ));
    }

    public function update(OrderRequest $request, int $order): RedirectResponse
    {
        $existing = $this->orders->find($order);
        $oldStatus = $existing->status;
        $data = $request->validatedOrderData();
        $spareParts = $request->validatedSpareParts();
        DB::transaction(function () use ($order, $data, $oldStatus, $request, $spareParts): void {
            $this->orders->update($order, $data);
            if (($data['status'] ?? $oldStatus) !== $oldStatus) {
                $this->statusHistory->create([
                    'order_id' => $order,
                    'from_status' => $oldStatus,
                    'to_status' => $data['status'],
                    'changed_by' => $request->user()->id,
                    'changed_at' => now(),
                ]);
            }
            $updated = $this->orders->find($order);
            $this->syncOrderParts($updated, $spareParts, (int) $request->user()->id);
            $this->ensureDeliveredPayment($updated, (int) $request->user()->id);
        });

        return redirect()->route('admin.orders.show', $order)->with('status', 'Order updated.');
    }

    public function pickupCalendar(Request $request): View
    {
        $month = $request->input('month');
        $start = $month ? now()->parse($month.'-01')->startOfMonth() : now()->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $orders = Order::query()
            ->with(['customer', 'device', 'branch'])
            ->where('status', 'pending_pickup')
            ->whereBetween('received_at', [$start, $end])
            ->orderBy('received_at')
            ->get();

        return view('admin.orders.calendar', [
            'orders' => $orders,
            'start' => $start,
            'end' => $end,
        ]);
    }

    public function destroy(int $order): RedirectResponse
    {
        $this->orders->delete($order);

        return redirect()->route('admin.orders.index')->with('status', 'Order deleted.');
    }

    public function storeNote(OrderNoteRequest $request, int $order): RedirectResponse
    {
        $data = $request->validated();
        $this->orderNotes->create([
            'order_id' => $order,
            'user_id' => $request->user()->id,
            'note' => $data['note'],
        ]);

        return redirect()->route('admin.orders.show', $order)->with('status', 'Note added.');
    }

    private function formLookups(): array
    {
        return [
            'customers' => $this->customers->all(),
            'devices' => $this->devices->all(),
            'users' => $this->users->all(),
            'branches' => $this->branches->all(),
            'spareParts' => SparePart::query()->orderBy('name')->get(),
        ];
    }

    private function syncOrderParts(Order $order, array $parts, int $actorId): void
    {
        $existingMovements = InventoryMovement::query()
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('movement_type', InventoryMovement::TYPE_SALE)
            ->get();
        foreach ($existingMovements as $movement) {
            SparePart::query()->whereKey($movement->spare_part_id)->increment('quantity', (int) $movement->quantity);
            InventoryMovement::query()->whereKey($movement->id)->delete();
        }
        $order->spareParts()->detach();

        foreach ($parts as $part) {
            $model = SparePart::query()->findOrFail($part['spare_part_id']);
            $qty = (int) $part['quantity'];
            $unitPrice = (string) ($part['unit_price'] ?? $model->selling_price ?? 0);

            if ($qty < 1) {
                continue;
            }

            if ((float) $model->quantity < $qty) {
                abort(422, __('messages.not_enough_stock_for_part').': '.$model->name);
            }

            $order->spareParts()->attach($model->id, [
                'quantity' => $qty,
                'unit_price' => (float) $unitPrice,
            ]);

            try {
                $this->stockService->recordSale(
                    $model,
                    $qty,
                    $unitPrice,
                    $actorId,
                    __('messages.used_in_order_creation_update'),
                    Order::class,
                    (int) $order->id,
                );
            } catch (RuntimeException $e) {
                abort(422, $e->getMessage());
            }
        }
    }

    private function ensureDeliveredPayment(Order $order, int $actorId): void
    {
        if ($order->status !== 'delivered') {
            return;
        }
        $finalCost = (float) ($order->final_cost ?? 0);
        if ($finalCost <= 0) {
            abort(422, 'Final cost is required before delivering the order.');
        }
        $paid = (float) $order->payments()->sum('amount');
        $remaining = round($finalCost - $paid, 2);
        if ($remaining <= 0) {
            return;
        }
        Payment::query()->create([
            'order_id' => $order->id,
            'amount' => $remaining,
            'method' => 'cash',
            'received_by' => $actorId,
            'paid_at' => now(),
        ]);
    }

    private function uniqueOrderNumber(): string
    {
        do {
            $number = 'AW-'.strtoupper(Str::random(10));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
