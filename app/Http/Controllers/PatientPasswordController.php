<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PatientPasswordChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class PatientPasswordController extends Controller
{
    public function edit(): View
    {
        return view('patient-portal.password-change');
    }

    public function update(PatientPasswordChangeRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => $request->validated('password'),
            'must_change_password' => false,
        ])->save();

        $request->session()->regenerate();

        return redirect()->route('patient.dashboard')->with('status', 'رمز عبور شما با موفقیت تغییر کرد.');
    }
}
