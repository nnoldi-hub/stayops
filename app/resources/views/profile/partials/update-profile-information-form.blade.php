<section>
    <header>
        <h2 class="font-display text-lg font-semibold text-slate-900">
            Informații de profil
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Actualizează datele contului, fotografia și limba preferată.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="flex items-center gap-5" data-testid="avatar-upload">
            @if($user->avatarUrl())
                <img
                    src="{{ $user->avatarUrl() }}"
                    alt="{{ $user->name }}"
                    class="h-16 w-16 rounded-full object-cover ring-2 ring-white shadow"
                    data-testid="avatar-preview"
                />
            @else
                <span class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-brand-orange to-brand-blue font-display text-xl font-extrabold text-white shadow">
                    {{ $user->initials() }}
                </span>
            @endif

            <div class="space-y-2">
                <x-input-label for="avatar" value="Fotografie de profil" />
                <input
                    id="avatar"
                    name="avatar"
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    class="block w-full text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-brand-navy file:px-3.5 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800"
                />
                <x-input-error class="mt-1" :messages="$errors->get('avatar')" />
                @if($user->avatarUrl())
                    <label class="inline-flex items-center gap-2 text-sm text-slate-500">
                        <input type="checkbox" name="remove_avatar" value="1" class="rounded border-slate-300 text-brand-orange shadow-sm focus:ring-brand-orange">
                        Șterge fotografia actuală
                    </label>
                @endif
            </div>
        </div>

        <div>
            <x-input-label for="name" value="Nume" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-slate-700">
                        Adresa de email nu este verificată.
                        <button form="send-verification" class="underline text-sm text-slate-500 hover:text-slate-700 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-orange">
                            Trimite din nou emailul de verificare.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            Un link nou de verificare a fost trimis pe adresa ta de email.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <x-input-label for="phone" value="Telefon" />
                <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $user->phone)" autocomplete="tel" placeholder="+40 7xx xxx xxx" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>

            <div>
                <x-input-label for="locale" value="Limbă" />
                <select
                    id="locale"
                    name="locale"
                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-orange focus:ring-brand-orange"
                >
                    <option value="ro" @selected(old('locale', $user->locale) === 'ro')>Română</option>
                    <option value="en" @selected(old('locale', $user->locale) === 'en')>English</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('locale')" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Salvează</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-green-600"
                >Salvat.</p>
            @endif
        </div>
    </form>
</section>
