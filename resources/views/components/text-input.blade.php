@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-school-primary dark:focus:border-school-accent focus:ring-school-primary dark:focus:ring-school-accent rounded-lg shadow-sm']) }}>
