<?php

namespace App\DataFixtures;

use App\Entity\Asset;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;
    public function __construct(UserPasswordHasherInterface $passwordHasher) {
        $this->passwordHasher = $passwordHasher;
    }
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('user@mediatech.com');
        $user->setRoles(['ROLE_USER']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, 'mediatech');
        $user->setPassword($hashedPassword);

        $manager->persist($user);

        $asset1 = new Asset();
        $asset1->setLabel('USB Stick');
        $asset1->setCurrency('BTC');
        $asset1->setAmount(2.5);
        $asset1->setUser($user);
        $manager->persist($asset1);

        $asset2 = new Asset();
        $asset2->setLabel('Digital Wallet');
        $asset2->setCurrency('ETH');
        $asset2->setAmount(10);
        $asset2->setUser($user);
        $manager->persist($asset2);

        $manager->flush();
    }
}
