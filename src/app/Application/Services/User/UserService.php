<?php

namespace App\Application\Services\User;

use App\Application\Contracts\In\Services\User\IUserService;
use App\Application\Contracts\Out\Managers\Hash\IHashManager;
use App\Application\Contracts\Out\Managers\Storage\IStorageManager;
use App\Application\Contracts\Out\Repositories\User\IUserRepository;
use App\Application\DTO\Collection\CursorDto;
use App\Application\DTO\In\User\ChangeUserAvatarDto;
use App\Application\DTO\In\User\ChangeUserDataDto;
use App\Application\DTO\In\User\ChangeUserPasswordDto;
use App\Application\DTO\In\User\GetUsersDto;
use App\Application\DTO\Out\Auth\UserDto;
use App\Application\Enums\Storage\StoragePaths;
use App\Application\Exceptions\User\InvalidOldPassword;

class UserService implements IUserService
{

    public function __construct(
        private readonly IStorageManager $storageManager,
        private readonly IUserRepository $userRepository,
        private readonly IHashManager $hashManager
    ) {
    }

    /**
     * @param GetUsersDto $getUsersDto
     * @return CursorDto
     */
    public function getUsers(GetUsersDto $getUsersDto): CursorDto
    {
        return $this->userRepository->getAll($getUsersDto);
    }

    /**
     * @param int $userId
     * @return UserDto
     */
    public function getUserById(int $userId): UserDto
    {
        return $this->userRepository->findById($userId);
    }

    /**
     * @param int $userId
     * @return bool
     */
    public function deleteUserById(int $userId): bool
    {
        return $this->userRepository->deleteById($userId);
    }

    /**
     * @param ChangeUserPasswordDto $changeUserPasswordDto
     * @throws InvalidOldPassword
     * @return UserDto
     */
    public function changePassword(ChangeUserPasswordDto $changeUserPasswordDto): UserDto
    {
        $user = $this->userRepository->findById($changeUserPasswordDto->userId);
        if (!$this->hashManager->check($changeUserPasswordDto->oldPassword, $user->password)) {
            throw new InvalidOldPassword();
        }

        return $this->userRepository->update(new UserDto(
            id: $changeUserPasswordDto->userId,
            password: $changeUserPasswordDto->newPassword
        ));
    }

    /**
     * @param ChangeUserAvatarDto $changeUserAvatarDto
     * @return UserDto
     */
    public function changeAvatar(ChangeUserAvatarDto $changeUserAvatarDto): UserDto
    {
        $user = $this->userRepository->findById($changeUserAvatarDto->userId);

        $path = StoragePaths::UserAvatar->value . "/$user->id.jpg";
        $this->storageManager->store($path, $changeUserAvatarDto->avatar);

        return $this->userRepository->update(new UserDto(
            id: $changeUserAvatarDto->userId,
            avatar: $changeUserAvatarDto->avatar
        ));
    }

    /**
     * @param ChangeUserDataDto $changeUserDataDto
     * @return UserDto
     */
    public function changeData(ChangeUserDataDto $changeUserDataDto): UserDto
    {
        return $this->userRepository->update(new UserDto(
            id: $changeUserDataDto->userId,
            name: $changeUserDataDto->name
        ));
    }
}
