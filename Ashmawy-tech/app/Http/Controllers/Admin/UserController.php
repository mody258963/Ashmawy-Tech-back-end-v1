<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Repository\Branch\BranchRepository;
use App\Repository\User\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly BranchRepository $branches,
    ) {}

    public function index(): View
    {
        return view('admin.users.index', [
            'users' => $this->users->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'branches' => $this->branches->all(),
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $this->users->create($data);

        return redirect()->route('admin.users.index')->with('status', 'User created.');
    }

    public function edit(int $user): View
    {
        return view('admin.users.edit', [
            'user' => $this->users->find($user),
            'branches' => $this->branches->all(),
        ]);
    }

    public function update(UserUpdateRequest $request, int $user): RedirectResponse
    {
        $data = $request->validated();
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $this->users->update($user, $data);

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function destroy(Request $request, int $user): RedirectResponse
    {
        if ((int) $user === (int) $request->user()->id) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own account.');
        }
        $this->users->delete($user);

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }
}
