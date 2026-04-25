<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BranchRequest;
use App\Repository\Branch\BranchRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(
        private readonly BranchRepository $branches,
    ) {}

    public function index(): View
    {
        return view('admin.branches.index', [
            'branches' => $this->branches->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.branches.create');
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        $this->branches->create($request->validated());

        return redirect()->route('admin.branches.index')->with('status', 'Branch created.');
    }

    public function edit(int $branch): View
    {
        return view('admin.branches.edit', [
            'branch' => $this->branches->find($branch),
        ]);
    }

    public function update(BranchRequest $request, int $branch): RedirectResponse
    {
        $this->branches->update($branch, $request->validated());

        return redirect()->route('admin.branches.index')->with('status', 'Branch updated.');
    }

    public function destroy(int $branch): RedirectResponse
    {
        $this->branches->delete($branch);

        return redirect()->route('admin.branches.index')->with('status', 'Branch deleted.');
    }
}
