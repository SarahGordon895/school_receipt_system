<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'login_type' => ['required', 'in:super_admin,school_admin,parent'],
            'password' => ['required', 'string'],
        ];

        if ($this->input('login_type') === 'parent') {
            $rules['phone'] = ['required', 'string', 'max:32'];
        } else {
            $rules['email'] = ['required', 'string', 'email', 'max:255'];
        }

        return $rules;
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginType = $this->string('login_type')->toString();
        $password = $this->string('password');

        if ($loginType === 'parent') {
            $phoneInput = $this->string('phone')->toString();
            $phone = User::normalizePhone($phoneInput);
            $matchKey = User::phoneMatchKey($phoneInput);

            $candidates = User::query()
                ->where('role', 'parent')
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->get()
                ->filter(function (User $candidate) use ($phone, $phoneInput, $matchKey) {
                    $stored = (string) $candidate->phone;

                    if ($stored === $phone || $stored === $phoneInput) {
                        return true;
                    }

                    if ($matchKey !== '' && User::phoneMatchKey($stored) === $matchKey) {
                        return true;
                    }

                    return User::normalizePhone($stored) === $phone;
                })
                ->values();

            $user = null;
            foreach ($candidates as $candidate) {
                if (Hash::check($password, $candidate->password)) {
                    $user = $candidate;
                    // Keep stored phone in canonical format for future logins.
                    if ($user->phone !== $phone) {
                        $user->forceFill(['phone' => $phone])->save();
                    }
                    break;
                }
            }
        } else {
            $user = $this->resolveEmailUser($loginType);

            if ($user && ! Hash::check($password, $user->password)) {
                $user = null;
            }
        }

        if (! $user) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages($this->credentialErrors());
        }

        Auth::login($user, $this->boolean('remember'));
        RateLimiter::clear($this->throttleKey());
    }

    private function resolveEmailUser(string $loginType): ?User
    {
        $role = $loginType === 'super_admin' ? 'super_admin' : 'school_admin';

        return User::query()
            ->where('email', $this->string('email')->lower()->toString())
            ->where('role', $role)
            ->first();
    }

    /** @return array<string, string> */
    private function credentialErrors(): array
    {
        if ($this->input('login_type') === 'parent') {
            return ['phone' => trans('auth.failed')];
        }

        return ['email' => trans('auth.failed')];
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());
        $field = $this->input('login_type') === 'parent' ? 'phone' : 'email';

        throw ValidationException::withMessages([
            $field => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        $identifier = $this->input('login_type') === 'parent'
            ? User::normalizePhone($this->string('phone')->toString())
            : Str::lower($this->string('email')->toString());

        return Str::transliterate($identifier.'|'.$this->ip());
    }
}
