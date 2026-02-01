<?php

declare(strict_types=1);

namespace BikeShare\Test\Integration\Controller;

use BikeShare\App\Security\UserProvider;
use BikeShare\Credit\CreditSystemInterface;
use BikeShare\Db\DbInterface;
use BikeShare\Enum\Action;
use BikeShare\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CreditHistoryControllerTest extends WebTestCase
{
    private const USER_PHONE_NUMBER = '421951111111';

    private array $originalEnv = [];
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->originalEnv = $_ENV;
        $_ENV['CREDIT_SYSTEM_ENABLED'] = '1';
        parent::setUp();
    }

    protected function tearDown(): void
    {
        if (self::$kernel !== null) {
            // Clean up credit history for user
            $user = self::getContainer()->get(UserRepository::class)
                ->findItemByPhoneNumber(self::USER_PHONE_NUMBER);

            $db = self::getContainer()->get(DbInterface::class);
            $db->query(
                'DELETE FROM history WHERE userId = :userId AND action IN (:creditAction, :creditChangeAction)',
                [
                    'userId' => $user['userId'],
                    'creditAction' => Action::CREDIT->value,
                    'creditChangeAction' => Action::CREDIT_CHANGE->value,
                ]
            );

            // Reset credit to 0
            $creditSystem = self::getContainer()->get(CreditSystemInterface::class);
            $userCredit = $creditSystem->getUserCredit($user['userId']);
            if ($userCredit > 0) {
                $creditSystem->useCredit($user['userId'], $userCredit);
            }
        }

        $_ENV = $this->originalEnv;
        parent::tearDown();
    }

    public function testCreditHistoryPageLoads(): void
    {
        $this->client = static::createClient();
        $user = $this->client->getContainer()->get(UserProvider::class)
            ->loadUserByIdentifier(self::USER_PHONE_NUMBER);
        $this->client->loginUser($user);

        $this->client->request('GET', '/credit/history');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Credit History');
    }

    public function testCreditHistoryShowsCurrentBalance(): void
    {
        $this->client = static::createClient();
        $userData = self::getContainer()->get(UserRepository::class)
            ->findItemByPhoneNumber(self::USER_PHONE_NUMBER);

        $creditSystem = self::getContainer()->get(CreditSystemInterface::class);
        $creditSystem->addCredit($userData['userId'], 50.0);

        $user = $this->client->getContainer()->get(UserProvider::class)
            ->loadUserByIdentifier(self::USER_PHONE_NUMBER);
        $this->client->loginUser($user);

        $this->client->request('GET', '/credit/history');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.card-text', '50');
    }

    public function testCreditHistoryDisplaysTransactions(): void
    {
        $this->client = static::createClient();
        $userData = self::getContainer()->get(UserRepository::class)
            ->findItemByPhoneNumber(self::USER_PHONE_NUMBER);

        $creditSystem = self::getContainer()->get(CreditSystemInterface::class);
        $creditSystem->addCredit($userData['userId'], 25.0);
        $creditSystem->addCredit($userData['userId'], 10.0);

        $user = $this->client->getContainer()->get(UserProvider::class)
            ->loadUserByIdentifier(self::USER_PHONE_NUMBER);
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/credit/history');

        $this->assertResponseIsSuccessful();
        // Should have at least 2 CREDITCHANGE entries
        $this->assertGreaterThanOrEqual(2, $crawler->filter('table tbody tr')->count());
    }

    public function testCreditHistoryShowsEmptyMessage(): void
    {
        $this->client = static::createClient();
        $user = $this->client->getContainer()->get(UserProvider::class)
            ->loadUserByIdentifier(self::USER_PHONE_NUMBER);
        $this->client->loginUser($user);

        $this->client->request('GET', '/credit/history');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-info');
    }

    public function testCreditHistoryRequiresAuthentication(): void
    {
        $this->client = static::createClient();
        $this->client->request('GET', '/credit/history');

        $this->assertResponseRedirects('/login');
    }

    public function testCreditHistoryNotFoundWhenCreditSystemDisabled(): void
    {
        $_ENV['CREDIT_SYSTEM_ENABLED'] = '0';

        $this->client = static::createClient();
        $user = $this->client->getContainer()->get(UserProvider::class)
            ->loadUserByIdentifier(self::USER_PHONE_NUMBER);
        $this->client->loginUser($user);

        $this->client->request('GET', '/credit/history');

        $this->assertResponseStatusCodeSame(404);
    }
}
