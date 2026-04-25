<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FollowUpRequest;
use App\Models\FollowUp;
use App\Repository\Customer\CustomerRepository;
use App\Repository\FollowUp\FollowUpRepository;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function __construct(
        private readonly FollowUpRepository $followUps,
        private readonly CustomerRepository $customers,
    ) {}

    public function index(): View
    {
        $now = CarbonImmutable::now();
        $todayStart = $now->startOfDay();

        $dueToday = FollowUp::query()
            ->whereNotNull('next_follow_up_at')
            ->whereBetween('next_follow_up_at', [$todayStart, $todayStart->endOfDay()])
            ->count();
        $overdue = FollowUp::query()
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<', $todayStart)
            ->count();

        $total = FollowUp::query()->count();
        $scheduledWeek = FollowUp::query()
            ->whereNotNull('next_follow_up_at')
            ->whereBetween('next_follow_up_at', [$todayStart, $todayStart->copy()->addDays(7)])
            ->count();
        $doneThisWeek = FollowUp::query()
            ->where('updated_at', '>=', $now->startOfWeek())
            ->where('updated_at', '>', DB::raw('created_at'))
            ->count();

        return view('admin.follow-ups.index', [
            'followUps' => $this->followUps->paginate(20),
            'cards' => [
                [
                    'label' => __('messages.total_followups'),
                    'value' => $total,
                    'class' => 'bg-secondary',
                    'icon' => 'fas fa-list',
                    'url' => route('admin.follow-ups.index'),
                ],
                [
                    'label' => __('messages.due_today'),
                    'value' => $dueToday,
                    'class' => 'bg-info',
                    'icon' => 'fas fa-calendar-day',
                    'url' => route('admin.follow-ups.index'),
                ],
                [
                    'label' => __('messages.overdue'),
                    'value' => $overdue,
                    'class' => 'bg-danger',
                    'icon' => 'fas fa-exclamation-triangle',
                    'url' => route('admin.follow-ups.index'),
                ],
                [
                    'label' => __('messages.scheduled_next_7_days'),
                    'value' => $scheduledWeek,
                    'class' => 'bg-success',
                    'icon' => 'fas fa-calendar-week',
                    'url' => route('admin.follow-ups.index'),
                ],
                [
                    'label' => __('messages.updated_this_week'),
                    'value' => $doneThisWeek,
                    'class' => 'bg-warning',
                    'icon' => 'fas fa-check-circle',
                    'url' => route('admin.follow-ups.index'),
                ],
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.follow-ups.create', [
            'customers' => $this->customers->all(),
        ]);
    }

    public function store(FollowUpRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['moderator_id'] = $request->user()->id;
        $this->followUps->create($data);

        return redirect()->route('admin.follow-ups.index')->with('status', 'Follow-up created.');
    }

    public function edit(int $follow_up): View
    {
        return view('admin.follow-ups.edit', [
            'followUp' => $this->followUps->find($follow_up),
            'customers' => $this->customers->all(),
        ]);
    }

    public function update(FollowUpRequest $request, int $follow_up): RedirectResponse
    {
        $data = $request->validated();
        $data['moderator_id'] = $request->user()->id;
        $this->followUps->update($follow_up, $data);

        return redirect()->route('admin.follow-ups.index')->with('status', 'Follow-up updated.');
    }

    public function destroy(int $follow_up): RedirectResponse
    {
        $this->followUps->delete($follow_up);

        return redirect()->route('admin.follow-ups.index')->with('status', 'Follow-up deleted.');
    }
}
