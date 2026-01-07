<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTaxSettingRequest;
use App\Models\TaxSetting;
use Illuminate\Http\Request;

class TaxSettingController extends Controller
{
    public function show(Request $request)
    {
        $rate = TaxSetting::getRate();

        return response()->json(['tax_rate' => $rate]);
    }

    public function update(UpdateTaxSettingRequest $request)
    {
        $validated = $request->validated();

        $instance = TaxSetting::setRate((float) $validated['tax_rate']);

        return response()->json(['tax_rate' => (float) $instance->tax_rate]);
    }
}
