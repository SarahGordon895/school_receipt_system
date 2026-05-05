<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 rounded-lg font-semibold text-sm text-school-primary shadow-sm hover:bg-school-surface focus:outline-none focus:ring-2 focus:ring-school-accent focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
