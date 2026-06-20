<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSchoolProfileRequest;
use App\Models\SchoolProfile;
use Illuminate\Http\Request;

class SchoolProfileController extends Controller
{
    public function index()
    {
        $profile = SchoolProfile::firstOrFail();

        return view('admin.school-profile.index', compact('profile'));
    }

    public function update(UpdateSchoolProfileRequest $request)
    {
        $profile = SchoolProfile::firstOrFail();
        $profile->update($request->validated());

        return redirect()->route('admin.school-profile.index')
            ->with('success', 'Profil sekolah berhasil diperbarui.');
    }
}
