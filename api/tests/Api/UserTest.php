<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class UserTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    private static string $token;

    /**
     * Méthode exécutée avant les tests
     */
    public static function setUpBeforeClass(): void
    {
        // Créer les utilisateurs de test
        UserFactory::createOne([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        UserFactory::createOne([
            'email' => 'other@example.com',
            'password' => 'password123'
        ]);

        // Obtenir le token pour l'utilisateur principal
        $response = static::createClient()->request('POST', '/auth', [
            'json' => [
                'email' => 'test@example.com',
                'password' => 'password123'
            ]
        ]);

        self::$token = $response->toArray()['token'];
    }

    /**
     * Test de création d'un utilisateur
     * @group user
     */
    public function testCreateUser(): void
    {
        $client = static::createClient();
        $client->request('POST', '/users', [
            'json' => [
                'email' => 'new@example.com',
                'plainPassword' => 'newpassword123'
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'email' => 'new@example.com'
        ]);
    }

    /**
     * Test de création d'un utilisateur invalide
     * @group user
     */
    public function testCreateInvalidUser(): void
    {
        $client = static::createClient();
        $client->request('POST', '/users', [
            'json' => [
                'email' => 'invalid-email',
                'plainPassword' => ''
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            '@type' => 'ConstraintViolationList',
            'violations' => [
                [
                    'propertyPath' => 'email',
                    'message' => 'This value is not a valid email address.'
                ],
                [
                    'propertyPath' => 'plainPassword',
                    'message' => 'This value should not be blank.'
                ]
            ]
        ]);
    }

    /**
     * Test de récupération d'un utilisateur
     * @group user
     */
    public function testGetUser(): void
    {
        $client = static::createClient();
        $userId = UserFactory::find(['email' => 'test@example.com'])->getId();
        
        $client->request('GET', '/users/' . $userId, [
            'auth_bearer' => self::$token
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'email' => 'test@example.com'
        ]);
    }

    /**
     * Test de récupération d'un utilisateur sans token
     * @group user
     */
    public function testGetUserUnauthorized(): void
    {
        $client = static::createClient();
        $userId = UserFactory::find(['email' => 'test@example.com'])->getId();
        
        $client->request('GET', '/users/' . $userId);

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'message' => 'JWT Token not found'
        ]);
    }

    /**
     * Test d'accès à un autre utilisateur sans avoir l'autorisation
     * @group user
     */
    public function testCannotAccessOtherUserData(): void
    {
        $client = static::createClient();
        $otherUserId = UserFactory::find(['email' => 'other@example.com'])->getId();
        
        $client->request('GET', '/users/' . $otherUserId, [
            'auth_bearer' => self::$token
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains([
            'description' => 'Access Denied.'
        ]);
    }

    /**
     * Test de mise à jour d'un utilisateur
     * @group user
     */
    public function testUpdateUser(): void
    {
        UserFactory::createOne([
            'email' => 'userToUpdate@example.com',
            'password' => 'password123'
        ]);

        // Obtenir le token pour l'utilisateur à mettre à jour
        $response = static::createClient()->request('POST', '/auth', [
            'json' => [
                'email' => 'userToUpdate@example.com',
                'password' => 'password123'
            ]
        ]);

        $userToken = $response->toArray()['token'];

        $client = static::createClient();
        $userId = UserFactory::find(['email' => 'userToUpdate@example.com'])->getId();
        
        $client->request('PUT', '/users/' . $userId, [
            'auth_bearer' => $userToken,
            'json' => [
                'email' => 'userToUpdate1@example.com',
                'plainPassword' => 'updatedpassword123'
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'email' => 'userToUpdate1@example.com'
        ]);
    }

    /**
     * Test de mise à jour d'un autre utilisateur sans avoir l'autorisation
     * @group user
     */
    public function testCannotUpdateOtherUserData(): void
    {
        $client = static::createClient();
        $otherUserId = UserFactory::find(['email' => 'other@example.com'])->getId();
        
        $client->request('PUT', '/users/' . $otherUserId, [
            'auth_bearer' => self::$token,
            'json' => [
                'email' => 'other@example.com',
                'plainPassword' => 'hackedpassword123'
            ]
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains([
            'description' => 'Access Denied.'
        ]);
    }

    /**
     * Test de mise à jour partielle d'un autre utilisateur sans avoir l'autorisation
     * @group user
     */
    public function testCannotPatchOtherUserData(): void
    {
        $client = static::createClient();
        $otherUserId = UserFactory::find(['email' => 'other@example.com'])->getId();
        
        $client->request('PATCH', '/users/' . $otherUserId, [
            'auth_bearer' => self::$token,
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'plainPassword' => 'hackedpassword123'
            ]
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains([
            'description' => 'Access Denied.'
        ]);
    }

    /**
     * Test de suppression d'un utilisateur
     * @group user
     */
    public function testDeleteUser(): void
    {

        UserFactory::createOne([
            'email' => 'userToDelete@example.com',
            'password' => 'password123'
        ]);

        // Obtenir le token pour l'utilisateur à supprimer
        $response = static::createClient()->request('POST', '/auth', [
            'json' => [
                'email' => 'userToDelete@example.com',
                'password' => 'password123'
            ]
        ]);

        $userToken = $response->toArray()['token'];


        $client = static::createClient();
        $userId = UserFactory::find(['email' => 'userToDelete@example.com'])->getId();
        
        $client->request('DELETE', '/users/' . $userId, [
            'auth_bearer' => $userToken
        ]);

        $this->assertResponseStatusCodeSame(204);

        // Vérifier que l'utilisateur n'existe plus
        $client->request('GET', '/users/' . $userId, [
            'auth_bearer' => self::$token
        ]);
        
        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains([
            'description' => 'Not Found'
        ]);
    }

    /**
     * Test de suppression d'un autre utilisateur sans avoir l'autorisation
     * @group user
     */
    public function testCannotDeleteOtherUser(): void
    {
        $client = static::createClient();
        $otherUserId = UserFactory::find(['email' => 'other@example.com'])->getId();
        
        $client->request('DELETE', '/users/' . $otherUserId, [
            'auth_bearer' => self::$token
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains([
            'description' => 'Access Denied.'
        ]);
    }

    /**
     * Test de récupération de la collection d'utilisateurs
     * @group user
     */
    public function testGetCollection(): void
    {
        // Créer quelques utilisateurs supplémentaires
        UserFactory::createMany(3);

        $client = static::createClient();
        $client->request('GET', '/users', [
            'auth_bearer' => self::$token
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@type' => 'Collection',
            'totalItems' => 7 
        ]);
    }

    /**
     * Test de récupération de la collection d'utilisateurs sans token
     * @group user
     */
    public function testGetCollectionUnauthorized(): void
    {
        $client = static::createClient();
        $client->request('GET', '/users');

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'message' => 'JWT Token not found'
        ]);
    }
}
