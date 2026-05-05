<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-school-primary border border-transparent rounded-lg font-semibold text-sm text-white tracking-wide hover:bg-school-primary-hover focus:bg-school-primary-hover active:bg-school-primary-hover focus:outline-none focus:ring-2 focus:ring-school-accent focus:ring-offset-2 focus:ring-offset-white transition ease-in-out duration-150 shadow-sm']) }}>
    {{ $slot }}
</button>
