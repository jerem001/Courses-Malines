<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PreferenceController extends Controller
{
    public function show(Request $request)
    {
        $preferences = UserPreference::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['radius_km' => 15, 'search_mode' => 'both'],
        );

	    return response()->json($preferences, 200);
    }

    public function update(Request $request)
	{
    $data = $request->validate([
        'city' => 'nullable|string|max:150',
        'postal_code' => 'nullable|string|max:12',
        'radius_km' => 'required|integer|min:1|max:100',
        'search_mode' => [
            'required',
            Rule::in(['both', 'favorites', 'radius']),
        ],
    ]);

    $preferences = UserPreference::updateOrCreate(
        ['user_id' => $request->user()->id],
        $data
    );

    return response()->json($preferences, 200);
	}
}
