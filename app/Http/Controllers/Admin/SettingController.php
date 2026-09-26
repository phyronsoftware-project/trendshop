<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use App\Models\SocialLink;
use App\Support\CambodiaProvince;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::query()->pluck('value', 'setting_key');
        $provinceFees = CambodiaProvince::feesFromJson($settings['province_delivery_fees'] ?? null);

        return view('admin.settings.index', [
            'cityDeliveryFee' => $settings['phnom_penh_delivery_fee'] ?? $provinceFees['Phnom Penh'],
            'provinceDeliveryFee' => $settings['delivery_fee'] ?? $provinceFees['Siem Reap'],
            'settings' => $settings,
            'socialLinks' => SocialLink::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($data): void {
            // Keep one city rate and one province rate while preserving the existing JSON lookup contract.
            $provinceFees = collect(CambodiaProvince::names())
                ->mapWithKeys(fn (string $province): array => [
                    $province => $province === 'Phnom Penh'
                        ? (float) $data['settings']['city_delivery_fee']
                        : (float) $data['settings']['province_delivery_fee'],
                ])->all();
            $settingValues = [
                'delivery_fee' => ['delivery', 'decimal', $data['settings']['province_delivery_fee']],
                'phnom_penh_delivery_fee' => ['delivery', 'decimal', $data['settings']['city_delivery_fee']],
                'free_delivery_minimum' => ['delivery', 'decimal', $data['settings']['free_delivery_minimum']],
                'province_delivery_fees' => ['delivery', 'json', json_encode($provinceFees, JSON_THROW_ON_ERROR)],
                'support_email' => ['contact', 'string', $data['settings']['support_email']],
                'support_phone' => ['contact', 'string', $data['settings']['support_phone']],
            ];

            foreach ($settingValues as $key => [$group, $type, $value]) {
                Setting::query()->updateOrCreate(
                    ['setting_key' => $key],
                    ['group_key' => $group, 'value_type' => $type, 'value' => $value, 'is_public' => true],
                );
            }
            foreach ($data['social_links'] ?? [] as $id => $link) {
                SocialLink::query()->whereKey($id)->update(['url' => $link['url'], 'is_active' => (bool) ($link['is_active'] ?? false)]);
            }
        });

        return back()->with('success', 'System settings updated.');
    }
}
