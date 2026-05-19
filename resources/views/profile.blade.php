<x-layouts.app>
    <x-slot name="title">Perfil</x-slot>

    <div class="max-w-2xl space-y-6">

        <div>
            <h1 class="text-xl font-semibold text-gray-900">Perfil</h1>
            <p class="text-sm text-gray-500 mt-0.5">Actualizá tus datos personales y contraseña</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <livewire:profile.update-profile-information-form />
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <livewire:profile.update-password-form />
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <livewire:profile.delete-user-form />
        </div>

    </div>
</x-layouts.app>
