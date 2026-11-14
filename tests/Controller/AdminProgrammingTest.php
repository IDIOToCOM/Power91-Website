<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminProgrammingTest extends WebTestCase
{
    public function testWrongPasswordKeepsTheFormAndShowsAnError(): void
    {
        $client = static::createClient();
        $this->prepareDatabase();

        $crawler = $client->request('GET', '/admin-panel');
        $client->submit($crawler->selectButton('Sign in')->form([
            'username' => 'studio@power91.local',
            'password' => 'wrong-password',
        ]));

        self::assertResponseRedirects('/admin-panel');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.login-error', 'Username or password is not correct');
        self::assertSame('studio@power91.local', $client->getCrawler()->filter('input[name="username"]')->attr('value'));
    }

    public function testAdminCanChangeTheTeamAndSchedule(): void
    {
        $client = static::createClient();
        $this->prepareDatabase();

        $crawler = $client->request('GET', '/admin-panel');
        $client->submit($crawler->selectButton('Sign in')->form([
            'username' => 'studio@power91.local',
            'password' => 'ChangeMeAdmin91',
        ]));
        self::assertResponseRedirects('/admin-panel/dashboard');

        $crawler = $client->request('GET', '/admin-panel/team/new');
        $client->submit($crawler->selectButton('Save')->form([
            'air_host[name]' => 'Board Tester',
            'air_host[role]' => 'Fill-in',
            'air_host[showName]' => 'Test Hour',
            'air_host[initials]' => 'BT',
            'air_host[accent]' => '#d32027',
            'air_host[bio]' => 'On the board for this test only.',
        ]));
        self::assertResponseRedirects('/admin-panel/team');

        $client->request('GET', '/team');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.host-grid', 'Board Tester');

        $crawler = $client->request('GET', '/admin-panel/team');
        $card = $crawler->filter('.admin-panel')->reduce(
            static fn ($node): bool => str_contains($node->text(), 'Board Tester'),
        );
        $client->submit($card->selectButton('Remove')->form());
        self::assertResponseRedirects('/admin-panel/team');

        $client->request('GET', '/team');
        self::assertSelectorTextNotContains('.host-grid', 'Board Tester');

        $crawler = $client->request('GET', '/admin-panel/schedule/new');
        $form = $crawler->selectButton('Save')->form([
            'program_slot[title]' => 'Board Test Show',
            'program_slot[hostName]' => 'Board Tester',
            'program_slot[genre]' => 'Test',
            'program_slot[startHour]' => '1',
            'program_slot[endHour]' => '2',
        ]);
        $days = $form['program_slot[weekdays]'];
        $days[array_key_first($days)]->tick();
        $client->submit($form);
        self::assertResponseRedirects('/admin-panel/schedule');

        $client->request('GET', '/schedule');
        self::assertSelectorTextContains('.schedule-grid', 'Board Test Show');

        $crawler = $client->request('GET', '/admin-panel/schedule');
        $row = $crawler->filter('tr')->reduce(
            static fn ($node): bool => str_contains($node->text(), 'Board Test Show'),
        );
        $client->submit($row->selectButton('Remove')->form());
        self::assertResponseRedirects('/admin-panel/schedule');

        $client->request('GET', '/schedule');
        self::assertSelectorTextNotContains('.schedule-grid', 'Board Test Show');
    }

    private function prepareDatabase(): void
    {
        $application = new Application(self::getContainer()->get('kernel'));
        $application->setAutoExit(false);
        $application->run(new ArrayInput([
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction' => true,
        ]), new NullOutput());

        $users = self::getContainer()->get(UserRepository::class);
        if ($users->findOneByUsername('studio@power91.local') instanceof User) {
            return;
        }

        $user = new User();
        $user->setUsername('studio@power91.local');
        $user->setRole(User::ROLE_ADMIN);
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, 'ChangeMeAdmin91'));

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();
    }
}
