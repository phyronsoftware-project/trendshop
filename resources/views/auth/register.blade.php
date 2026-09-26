<!DOCTYPE html>
<html lang="km">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Create a TrendShop customer account">

        <title>TrendShop — Create account</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="locale-km min-h-screen bg-[#173f88] text-white antialiased">
        {{-- Keep registration standalone and visually consistent with customer login. --}}
        <main class="flex min-h-screen items-center justify-center px-5 py-8 sm:px-8">
            <section class="w-full max-w-md">
                <div class="text-center">
                    <img src="{{ asset('logo_web/image.png') }}" alt="TrendShop logo" class="mx-auto size-14 object-contain drop-shadow-xl">
                    <h1 class="mt-4 text-2xl font-bold tracking-tight text-white sm:text-3xl" data-i18n="register.title">បង្កើតគណនី TrendShop</h1>
                    <p class="mx-auto mt-2 max-w-sm text-xs leading-6 text-white/65" data-i18n="register.description">បង្កើតគណនី ដើម្បីរក្សាទុកកន្ត្រក អាសយដ្ឋាន និងតាមដានការបញ្ជាទិញ។</p>
                </div>

                {{-- Submit customer registration through Laravel validation. --}}
                <form method="POST" action="{{ route('register.store') }}" novalidate data-validate-form class="mt-6 flex flex-col gap-3.5">
                    @csrf
                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-white">
                        <span data-i18n="register.name">ឈ្មោះពេញ</span>
                        <input type="text" name="name" value="{{ old('name') }}" data-validation="required|max:150" autocomplete="name" data-i18n-placeholder="register.namePlaceholder" placeholder="បញ្ចូលឈ្មោះរបស់អ្នក" class="h-10 rounded-lg border border-white/35 bg-transparent px-3.5 text-sm font-normal text-white outline-none transition-all duration-300 placeholder:text-white/35 focus:border-white focus:ring-2 focus:ring-white/15">
                        <x-form-error name="name" :auth="true" />
                    </label>
                    <label class="flex flex-col gap-1.5 text-xs font-semibold text-white">
                        <span data-i18n="register.email">អាសយដ្ឋានអ៊ីមែល</span>
                        <input type="email" name="email" value="{{ old('email') }}" data-validation="required|email|max:255" autocomplete="email" data-i18n-placeholder="register.emailPlaceholder" placeholder="you@example.com" class="h-10 rounded-lg border border-white/35 bg-transparent px-3.5 text-sm font-normal text-white outline-none transition-all duration-300 placeholder:text-white/35 focus:border-white focus:ring-2 focus:ring-white/15">
                        <x-form-error name="email" :auth="true" />
                    </label>
                    <div class="grid gap-3.5 sm:grid-cols-2">
                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-white">
                            <span data-i18n="register.password">ពាក្យសម្ងាត់</span>
                            <input type="password" name="password" data-validation="required|min:8" autocomplete="new-password" data-i18n-placeholder="register.passwordPlaceholder" placeholder="បញ្ចូលពាក្យសម្ងាត់" class="h-10 rounded-lg border border-white/35 bg-transparent px-3.5 text-sm font-normal text-white outline-none transition-all duration-300 placeholder:text-white/35 focus:border-white focus:ring-2 focus:ring-white/15">
                            <x-form-error name="password" :auth="true" />
                        </label>
                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-white">
                            <span data-i18n="register.confirmPassword">បញ្ជាក់ពាក្យសម្ងាត់</span>
                            <input type="password" name="password_confirmation" data-validation="required|same:password" autocomplete="new-password" data-i18n-placeholder="register.confirmPasswordPlaceholder" placeholder="បញ្ចូលម្ដងទៀត" class="h-10 rounded-lg border border-white/35 bg-transparent px-3.5 text-sm font-normal text-white outline-none transition-all duration-300 placeholder:text-white/35 focus:border-white focus:ring-2 focus:ring-white/15">
                            <x-form-error name="password_confirmation" :auth="true" />
                        </label>
                    </div>
                    <div>
                        <label class="flex items-start gap-2 text-[11px] leading-5 text-white/75">
                            <input type="checkbox" name="terms" value="1" data-validation="accepted" @checked(old('terms')) class="mt-1 size-3.5 shrink-0 rounded border-white/40 bg-transparent text-white">
                            <span data-i18n="register.terms">ខ្ញុំយល់ព្រមតាមគោលការណ៍ឯកជនភាព និងលក្ខខណ្ឌប្រើប្រាស់។</span>
                        </label>
                        <x-form-error name="terms" :auth="true" />
                    </div>
                    <button type="submit" class="mt-0.5 h-10 rounded-lg bg-white text-sm font-bold text-[#173f88] shadow-lg shadow-blue-950/15 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl active:scale-[.98] disabled:cursor-wait disabled:opacity-80" data-i18n="register.submit">បង្កើតគណនី</button>
                </form>

                <div class="my-4 flex items-center gap-3">
                    <span class="h-px flex-1 bg-white/20"></span>
                    <span class="text-[11px] text-white/60" data-i18n="register.or">ឬចុះឈ្មោះជាមួយ</span>
                    <span class="h-px flex-1 bg-white/20"></span>
                </div>

                {{-- Reuse provided social assets for static account registration. --}}
                <div class="grid grid-cols-3 gap-2.5">
                    @foreach ([
                        ['google.png', 'login.google'],
                        ['telegram.png', 'login.telegram'],
                        ['communication.png', 'login.facebook'],
                    ] as [$icon, $label])
                        <button type="button" data-static-action data-alert-status="info" data-alert-message="alerts.socialDemo" class="flex h-10 items-center justify-center gap-2 rounded-lg border border-white/30 text-[11px] font-semibold text-white transition-all duration-300 hover:-translate-y-0.5 hover:border-white hover:bg-white/10 active:scale-95 disabled:cursor-wait disabled:opacity-70">
                            <img src="{{ asset('Logo-Socail/'.$icon) }}" alt="" class="size-4 object-contain">
                            <span data-i18n="{{ $label }}">Social</span>
                        </button>
                    @endforeach
                </div>

                <p class="mt-4 text-center text-xs text-white/70">
                    <span data-i18n="register.existing">មានគណនីរួចហើយ?</span>
                    <a href="{{ route('login') }}" class="ml-1 font-bold text-white transition-opacity duration-300 hover:opacity-70" data-i18n="register.login">ចូលគណនី</a>
                </p>
            </section>
        </main>
        <x-alert />
    </body>
</html>
