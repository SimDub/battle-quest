<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class AuthenticationTest extends ApiTestCase
{
  use ResetDatabase, Factories;

  public function testLogin(): void
  {
    // Create a users
    UserFactory::createMany(10);

    $client = self::createClient();

    UserFactory::createOne([
      'email' => 'test@example.com',
      'password' => 'test123'  // Sera automatiquement hashé
    ]);

    // retrieve a token
    $response = $client->request('POST', '/auth', [
      'headers' => ['Content-Type' => 'application/json'],
      'json' => [
        'email' => 'test@example.com',
        'password' => 'test123',
      ],
    ]);

    $json = $response->toArray();
    $this->assertResponseIsSuccessful();
    $this->assertArrayHasKey('token', $json);

    // test not authorized
    $client->request('GET', '/users');
    $this->assertResponseStatusCodeSame(401);

    // test authorized    
    $response = $client->request('GET', '/users', ['auth_bearer' => $json['token']]);
    $this->assertResponseIsSuccessful();
    $this->assertJsonContains([
      'totalItems' => 11
    ]);
  }
}
