<?php

namespace App\DataFixtures;

use App\Entity\Subscription;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;

class SubscriptionFixtures extends Fixture implements DependentFixtureInterface
{

    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');
        $index = 0;
        for ($i = 0; $i < 100; $i++) {
            for ($j = 0; $j <= rand(1, 6); $j++) {
                if ($j != rand(0, 2)) {
                    $subscription = new Subscription();
                    $subscription->setSubscriptionDate($faker->dateTimeThisDecade('now'));
                    $subscription->setSubscriber($this->getReference('subscriber_' . $i));
                    $subscription->setSeason($this->getReference('season_' . $j));
                    $subscription->setLicence($this->getReference('licence_' . rand(0, 5)));
                    $manager->persist($subscription);
                    $this->addReference('subscription_' . $index, $subscription);
                    $index++;
                }
            }
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [SubscriberFixtures::class, SeasonFixtures::class, LicenceFixtures::class];
    }
}