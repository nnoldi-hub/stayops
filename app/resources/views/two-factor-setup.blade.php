<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Activare autentificare în doi pași') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
                <p class="text-gray-600">
                    {{ __('Contul tău necesită autentificare în doi pași (2FA) deoarece are un rol cu privilegii ridicate.') }}
                </p>
                <p class="text-gray-600">
                    {{ __('Configurarea UI completă este în lucru — pentru a activa 2FA acum, contactează un administrator sau folosește endpoint-ul') }}
                    <code>POST /user/two-factor-authentication</code>.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
