<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationSwitchController extends Controller
{
    public function switch(Request $request, int $id): RedirectResponse
    {
        $org = Organization::where('is_active', true)->findOrFail($id);
        session(['current_organization_id' => $org->id]);

        if (auth()->check()) {
            auth()->user()->update(['current_organization_id' => $org->id]);
        }

        return redirect()->back()->with('success', "تم التبديل بنجاح إلى مؤسسة: {$org->name}");
    }
}
