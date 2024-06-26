<?php

namespace App\Application\Services\Auth;

use App\Application\Contracts\In\Services\Auth\IAuthService;
use App\Application\Contracts\Out\Managers\Cache\ICacheManager;
use App\Application\Contracts\Out\Managers\Hash\IHashManager;
use App\Application\Contracts\Out\Managers\Mail\IMailManager;
use App\Application\Contracts\Out\Managers\Token\ITokenManager;
use App\Application\Contracts\Out\Repositories\User\IUserRepository;
use App\Application\DTO\In\Auth\LoginDto;
use App\Application\DTO\In\Auth\Password\ForgotPasswordDto;
use App\Application\DTO\In\Auth\Password\ResetPasswordDto;
use App\Application\DTO\In\Auth\Password\VerifyPasswordCodeDto;
use App\Application\DTO\In\Auth\RegisterDto;
use App\Application\DTO\Out\Auth\UserDto;
use App\Application\Exceptions\Auth\InvalidPassword;
use App\Application\Exceptions\Auth\Password\ExpiredResetPasswordToken;
use App\Application\Exceptions\Auth\Password\ExpiredVerifyPasswordCode;
use App\Application\Exceptions\Auth\Password\InvalidResetPasswordToken;
use App\Application\Exceptions\Auth\Password\InvalidVerifyPasswordCode;
use App\Application\Exceptions\Auth\RegistrationConflict;
use App\Application\Exceptions\User\UserNotFound;
use App\Infrastructure\Database\Models\User;
use Exception;

class AuthService implements IAuthService
{

    public function __construct(
        private readonly IUserRepository $userRepository,
        private readonly ITokenManager $tokenManager,
        private readonly ICacheManager $cacheManager,
        private readonly IMailManager $mailManager,
        private readonly IHashManager $hashManager
    ) {
    }

    /**
     * @param LoginDto $loginDto
     * @return string
     * @throws UserNotFound
     * @throws InvalidPassword
     */
    public function login(LoginDto $loginDto): string
    {
        $user = $this->userRepository->findByEmail($loginDto->email);

        if (!$user) {
            throw new UserNotFound();
        }

        if (!$this->hashManager->check($loginDto->password, $user->password)) {
            throw new InvalidPassword();
        }

        return $this->tokenManager->createToken($loginDto);
    }

    /**
     * @param RegisterDto $registerDto
     * @return void
     * @throws RegistrationConflict
     */
    public function register(RegisterDto $registerDto): void
    {
        if ($this->userRepository->findByEmail($registerDto->email)) {
            throw new RegistrationConflict();
        }

        $this->userRepository->create(new UserDto(
            email: $registerDto-> email,
            name: $registerDto->name,
            password: $registerDto->password
        ));
    }

    /**
     * @return UserDto
     * @throws Exception
     */
    public function getAuthUser(): UserDto
    {
        return $this->tokenManager->getAuthUser();
    }

    /**
     * @return void
     */
    public function logout(): void
    {
        $this->tokenManager->destroyToken();
    }

    /**
     * @return string
     */
    public function refresh(): string
    {
        return $this->tokenManager->refreshToken();
    }

    /**
     * @param ForgotPasswordDto $forgotPasswordDto
     * @return void
     * @throws UserNotFound
     */
    public function forgotPassword(ForgotPasswordDto $forgotPasswordDto): void
    {
        $user = $this->userRepository->findByEmail($forgotPasswordDto->email);

        if (!$user) {
            throw new UserNotFound();
        }

        $resetCode = str_pad(mt_rand(0, 9999), 4, 0, STR_PAD_LEFT);

        $this->cacheManager->put(
            'password_reset_' . $forgotPasswordDto->email,
            $resetCode
        );

        $this->mailManager->sendResetCode($forgotPasswordDto->email, $resetCode);
    }

    /**
     * @param VerifyPasswordCodeDto $verifyPasswordCodeDto
     * @return string
     * @throws UserNotFound
     * @throws ExpiredVerifyPasswordCode
     * @throws InvalidVerifyPasswordCode
     */
    public function verifyPasswordCode(VerifyPasswordCodeDto $verifyPasswordCodeDto): string
    {
        /** @var ?User $user */
        $user = $this->userRepository->findByEmail($verifyPasswordCodeDto->email);

        if (!$user) {
            throw new UserNotFound();
        }

        $resetCode = $this->cacheManager->get('password_reset_' . $verifyPasswordCodeDto->email);

        if (!$resetCode) {
            throw new ExpiredVerifyPasswordCode();
        }

        if ($resetCode !== $verifyPasswordCodeDto->code) {
            throw new InvalidVerifyPasswordCode();
        }

        $resetToken = $this->hashManager->make($verifyPasswordCodeDto->code);
        $this->cacheManager->put(
            'password_reset_' . $verifyPasswordCodeDto->email,
            $resetToken
        );
        return $resetToken;
    }

    /**
     * @param ResetPasswordDto $resetPasswordDto
     * @return void
     * @throws UserNotFound
     * @throws InvalidResetPasswordToken
     * @throws ExpiredResetPasswordToken
     */
    public function resetPassword(ResetPasswordDto $resetPasswordDto): void
    {
        /** @var ?User $user */
        $user = $this->userRepository->findByEmail($resetPasswordDto->email);

        if (!$user) {
            throw new UserNotFound();
        }

        $resetToken = $this->cacheManager->get('password_reset_' . $resetPasswordDto->email);

        if (!$resetToken) {
            throw new ExpiredResetPasswordToken();
        }
        if ($resetToken !== $resetPasswordDto->token) {
            throw new InvalidResetPasswordToken();
        }

        $this->userRepository->update(new UserDto(
            id: $user->id,
            password: $resetPasswordDto->password
        ));
    }
}
