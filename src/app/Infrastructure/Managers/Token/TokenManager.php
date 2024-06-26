<?php

namespace App\Infrastructure\Managers\Token;

use App\Application\Contracts\Out\Managers\Token\ITokenManager;
use App\Application\DTO\In\Auth\LoginDto;
use App\Application\DTO\Out\Auth\UserDto;
use App\Infrastructure\Database\Models\User;
use App\Infrastructure\Exceptions\InvalidToken;
use App\Utils\Mappers\Out\Auth\UserDtoMapper;
use Exception;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\JWTGuard;

class TokenManager implements ITokenManager
{
    /**
     * @param LoginDto $userDto
     * @return string
     */
    public function createToken(LoginDto $userDto): string
    {
        return $this->getAuth()->attempt([
            'email' => $userDto->email,
            'password' => $userDto->password
        ]);
    }

    /**
     * @return string
     */
    public function refreshToken(): string
    {
        return $this->getAuth()->refresh();
    }

    /**
     * @return void
     */
    public function destroyToken(): void
    {
        $this->getAuth()->logout();
    }

    /**
     * @return UserDto
     * @throws Exception
     */
    public function getAuthUser(): UserDto
    {
        /** @var ?User $user */
        $user = $this->getAuth()->user();

        if (!$user) {
            throw new InvalidToken();
        }

        return UserDtoMapper::fromUserModel($user);
    }

    /**
     * @param string $token
     * @return ?UserDto
     * @throws InvalidToken
     */
    public function parseToken(string $token): ?UserDto
    {
        try {
            return UserDtoMapper::fromUserModel(
                $this->getAuth()->setToken($token)->user()
            );
        } catch (Exception $e) {
            throw new InvalidToken();
        }
    }

    /**
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        /** @var ?User $user */
        $user = $this->getAuth()->user();
        if (!$user) {
            throw new InvalidToken();
        }
        return $user->hasRole($role);
    }

    /**
     * @return JWTGuard
     */
    private function getAuth(): JWTGuard
    {
        /** @var JWTGuard */
        return Auth::guard('api');
    }
}
