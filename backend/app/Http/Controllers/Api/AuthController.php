<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'in:customer,carrier'],
            'company_name' => ['required_if:role,carrier', 'string', 'max:255'],
            'company_inn' => ['nullable', 'string', 'max:50'],
            'company_type' => ['required_if:role,carrier', 'in:shipper,carrier'],
        ]);

        $company = null;
        $carrier = null;

        if ($request->role === 'carrier' || $request->company_name) {
            $company = Company::create([
                'name' => $request->company_name,
                'inn' => $request->company_inn,
                'type' => $request->company_type ?? ($request->role === 'carrier' ? 'carrier' : 'shipper'),
                'verified' => false, // Требуется верификация
            ]);

            // Автоматически создаем запись Carrier для перевозчика
            if ($request->role === 'carrier') {
                $carrier = Carrier::create([
                    'company_id' => $company->id,
                    'api_type' => 'manual', // По умолчанию ручной расчет
                    'supported_transport_types' => ['road'], // По умолчанию автоперевозки
                    'supported_countries' => ['KZ'], // По умолчанию Казахстан
                    'is_active' => false, // Неактивен до верификации
                ]);
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => $request->role,
            'company_id' => $company?->id,
        ]);

        event(new Registered($user));

        $token = $user->createToken('auth-token')->plainTextToken;

        // Формируем ответ с информацией о статусе верификации
        $responseData = [
            'message' => $request->role === 'carrier'
                ? 'Регистрация успешна. Для начала работы загрузите необходимые документы и пройдите верификацию.'
                : 'Registration successful. Please verify your email.',
            'user' => $user->load('company'),
            'token' => $token,
        ];

        if ($request->role === 'carrier') {
            $responseData['verification_required'] = true;
            $responseData['required_documents'] = [
                'registration_certificate' => 'Свидетельство о регистрации компании',
                'transport_license' => 'Лицензия на грузоперевозки',
                'insurance_policy' => 'Страховой полис',
            ];
        }

        return response()->json($responseData, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = User::where('email', $request->email)->first();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user->load('company'),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->load('company'),
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => __($status),
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => __($status),
        ]);
    }

    public function verifyEmail(Request $request, string $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified']);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Email verified successfully']);
    }
}
