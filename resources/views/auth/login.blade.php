<!DOCTYPE html>
<html lang="km">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="TrendShop customer sign in">

        <title>TrendShop — Sign in</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="locale-km min-h-screen bg-[#173f88] text-white antialiased">
        {{-- Display customer login as a standalone page without the storefront header or footer. --}}
        <main class="flex min-h-screen items-center justify-center px-5 py-12 sm:px-8">
            <section class="w-full max-w-md">
                <div class="text-center">
                    <img src="{{ asset('logo_web/image.png') }}" alt="TrendShop logo" class="mx-auto size-16 object-contain drop-shadow-xl">
                    <h1 class="mt-5 text-2xl font-bold tracking-tight text-white sm:text-3xl" data-i18n="login.title">ចូលគណនី TrendShop</h1>
                    <p class="mx-auto mt-2 max-w-sm text-xs leading-6 text-white/65" data-i18n="login.description">ចូលប្រើកន្ត្រក អាសយដ្ឋាន និងប្រវត្តិការបញ្ជាទិញរបស់អ្នក។</p>
                </div>

                {{-- Submit credentials through Laravel's customer authentication flow. --}}
                <form method="POST" action="{{ route('login.store') }}" novalidate data-validate-form class="mt-7 flex flex-col gap-4">
                    @csrf
                    <label class="flex flex-col gap-2 text-xs font-semibold text-white">
                        <span data-i18n="login.email">អាសយដ្ឋានអ៊ីមែល</span>
                        <input type="email" name="email" value="{{ old('email') }}" data-validation="required|email" autocomplete="email" data-i18n-placeholder="login.emailPlaceholder" placeholder="you@example.com" class="h-11 rounded-lg border border-white/35 bg-transparent px-3.5 text-sm font-normal text-white outline-none transition-all duration-300 placeholder:text-white/35 focus:border-white focus:ring-2 focus:ring-white/15">
                        <x-form-error name="email" :auth="true" />
                    </label>
                    <label class="flex flex-col gap-2 text-xs font-semibold text-white">
                        <span data-i18n="login.password">ពាក្យសម្ងាត់</span>
                        <input type="password" name="password" data-validation="required" autocomplete="current-password" data-i18n-placeholder="login.passwordPlaceholder" placeholder="បញ្ចូលពាក្យសម្ងាត់" class="h-11 rounded-lg border border-white/35 bg-transparent px-3.5 text-sm font-normal text-white outline-none transition-all duration-300 placeholder:text-white/35 focus:border-white focus:ring-2 focus:ring-white/15">
                        <x-form-error name="password" :auth="true" />
                    </label>
                    <div class="flex items-center justify-between gap-3 text-xs text-white/80">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="remember" value="1" class="size-3.5 rounded border-white/40 bg-transparent text-white">
                            <span data-i18n="login.remember">ចងចាំខ្ញុំ</span>
                        </label>
                        <button type="button" data-static-action data-alert-status="warning" data-alert-message="alerts.comingSoon" class="font-semibold text-white transition-opacity duration-300 hover:opacity-70 disabled:cursor-wait disabled:opacity-70" data-i18n="login.forgot">ភ្លេចពាក្យសម្ងាត់?</button>
                    </div>
                    <button type="submit" class="mt-1 h-11 rounded-lg bg-white text-sm font-bold text-[#173f88] shadow-lg shadow-blue-950/15 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl active:scale-[.98] disabled:cursor-wait disabled:opacity-80" data-i18n="login.submit">ចូលគណនី</button>
                </form>

                <div class="my-5 flex items-center gap-3">
                    <span class="h-px flex-1 bg-white/20"></span>
                    <span class="text-[11px] text-white/60" data-i18n="login.or">ឬបន្តជាមួយ</span>
                    <span class="h-px flex-1 bg-white/20"></span>
                </div>

                {{-- Use live Google and Telegram flows while retaining the planned Facebook action. --}}
                <div class="grid grid-cols-3 gap-2.5">
                    @foreach ([
                        'google' => ['google.png', 'login.google'],
                        'telegram' => ['telegram.png', 'login.telegram'],
                    ] as $provider => [$icon, $label])
                        @php($configured = filled(config("services.{$provider}.client_id")) && filled(config("services.{$provider}.client_secret")))
                        @if ($socialProviders->has($provider) && $configured)
                            <a href="{{ route('social.redirect', $provider) }}" class="flex h-10 items-center justify-center gap-2 rounded-lg border border-white/30 text-[11px] font-semibold text-white transition-all duration-300 hover:-translate-y-0.5 hover:border-white hover:bg-white/10 active:scale-95">
                                <img src="{{ asset('Logo-Socail/'.$icon) }}" alt="" class="size-4 object-contain">
                                <span data-i18n="{{ $label }}">{{ ucfirst($provider) }}</span>
                            </a>
                        @else
                            <button type="button" disabled title="{{ ucfirst($provider) }} login is not configured" class="flex h-10 cursor-not-allowed items-center justify-center gap-2 rounded-lg border border-white/20 text-[11px] font-semibold text-white/45">
                                <img src="{{ asset('Logo-Socail/'.$icon) }}" alt="" class="size-4 object-contain grayscale">
                                <span data-i18n="{{ $label }}">{{ ucfirst($provider) }}</span>
                            </button>
                        @endif
                    @endforeach
                    <button type="button" data-static-action data-alert-status="info" data-alert-message="alerts.socialDemo" class="flex h-10 items-center justify-center gap-2 rounded-lg border border-white/30 text-[11px] font-semibold text-white transition-all duration-300 hover:-translate-y-0.5 hover:border-white hover:bg-white/10 active:scale-95 disabled:cursor-wait disabled:opacity-70">
                        <img src="{{ asset('Logo-Socail/communication.png') }}" alt="" class="size-4 object-contain">
                        <span data-i18n="login.facebook">Facebook</span>
                    </button>
                </div>

                <p class="mt-5 text-center text-xs text-white/70">
                    <span data-i18n="login.new">មិនទាន់មានគណនី?</span>
                    <a href="{{ route('register') }}" class="ml-1 font-bold text-white transition-opacity duration-300 hover:opacity-70" data-i18n="login.create">បង្កើតគណនី</a>
                </p>
            </section>
        </main>
        <x-alert />
    </body>
</html>
