<?php

declare(strict_types=1);

namespace BikeShare\User;

use BikeShare\App\Entity\User;
use BikeShare\App\Security\UserProvider;
use BikeShare\Event\UserRegistrationEvent;
use BikeShare\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class UserRegistration
{
    public function __construct(
        private readonly UserProvider $userProvider,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function register(
        string $number,
        string $email,
        string $plainPassword,
        string $city,
        string $userName,
        int $privileges
    ): User {
        $registrationDate = new \DateTimeImmutable();
        $newUser = new User(
            0,
            $number,
            $email,
            '',
            $city,
            $userName,
            $privileges,
            false,
            $registrationDate,
        );
        $hashedPassword = $this->passwordHasher->hashPassword($newUser, $plainPassword);

        $user = $this->userProvider->addUser(
            $number,
            $email,
            $hashedPassword,
            $city,
            $userName,
            $privileges
        );
        $this->userRepository->updateUserLimit($user->getUserId(), 0);

        $this->eventDispatcher->dispatch(new UserRegistrationEvent($user));

        return $user;
    }
}
