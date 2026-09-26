@props(['name', 'auth' => false, 'message' => null])

@php($validationMessage = $message ?? $errors->first($name))

{{-- Keep server and browser-side validation feedback in one compact field-level line. --}}
<p data-validation-error="{{ $name }}" role="alert" @class([
    'min-h-4 text-[11px] font-medium leading-4',
    'text-red-200' => $auth,
    'text-red-500 dark:text-red-400' => ! $auth,
    'hidden' => blank($validationMessage),
])>{{ $validationMessage }}</p>
