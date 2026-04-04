<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvitationTokenRequest;
use App\Models\AdminUser;
use App\Models\Vendor;
use App\Services\Invite\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class InvitationTokenController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitationService,
    ) {}

    public function index(): View
    {
        $tokens = $this->invitationService->paginateForAdmin(20);

        return view('admin.invitation_tokens.index', ['tokens' => $tokens]);
    }

    public function create(): View
    {
        $adminUsers = AdminUser::query()->where('is_active', true)->orderBy('name')->get();
        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.invitation_tokens.create', [
            'adminUsers' => $adminUsers,
            'vendors' => $vendors,
        ]);
    }

    public function store(StoreInvitationTokenRequest $request): RedirectResponse
    {
        /** @var AdminUser $issuer */
        $issuer = Auth::guard('admin')->user();

        $role = $request->validated('role');
        $adminUserId = $request->validated('admin_user_id');
        $vendorId = $request->validated('vendor_id');

        $token = $this->invitationService->issue(
            (int) $issuer->id,
            $role,
            $adminUserId !== null ? (int) $adminUserId : null,
            $vendorId !== null ? (int) $vendorId : null,
        );

        $url = $this->invitationService->publicInviteUrl($token->token);

        return redirect()
            ->route('admin.invitation-tokens.index')
            ->with('status', __('招待URLを発行しました。').' '.$url);
    }
}
