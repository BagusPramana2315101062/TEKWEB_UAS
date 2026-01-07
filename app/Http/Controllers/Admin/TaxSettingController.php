<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TaxSetting;

class TaxSettingController extends Controller
{
    public function edit()
    {
        $rate = TaxSetting::getRate();
        return view('admin.settings.tax', compact('rate'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'tax_rate' => 'required|numeric|min:0|max:100',
        ]);

        TaxSetting::setRate((float) $data['tax_rate']);

        return redirect()->route('admin.settings.tax')->with('success', 'Tax rate updated.');
    }
}
