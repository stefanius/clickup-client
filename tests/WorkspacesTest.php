<?php

namespace TestMonitor\Clickup\Tests;

use Mockery;
use PHPUnit\Framework\TestCase;
use TestMonitor\Clickup\Client;
use TestMonitor\Clickup\Resources\Workspace;
use TestMonitor\Clickup\Exceptions\Exception;
use TestMonitor\Clickup\Exceptions\NotFoundException;
use TestMonitor\Clickup\Exceptions\ValidationException;
use TestMonitor\Clickup\Exceptions\FailedActionException;
use TestMonitor\Clickup\Exceptions\UnauthorizedException;

class WorkspacesTest extends TestCase
{
    protected $workspace;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->token = Mockery::mock('\TestMonitor\Clickup\AccessToken');
        $this->token->shouldReceive('expired')->andReturnFalse();

        $this->workspace = [
            'id' => 1,
            'name' => 'Into Space',
            'color' =>'123456',
            'avatar' => 'avatar.jpg',
        ];
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    /** @test */
    public function it_should_return_a_list_of_workspaces()
    {
        // Given
        $clickup = new Client(['clientId' => 1, 'clientSecret' => 'secret', 'redirectUrl' => 'none'], $this->token);

        $clickup->setClient($service = Mockery::mock('GuzzleHttp\Client'));

        $service->shouldReceive('request')->andReturn($response = Mockery::mock('Psr\Http\Message\ResponseInterface'));
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $response->shouldReceive('getBody')->andReturn(\GuzzleHttp\Psr7\Utils::streamFor(json_encode([
            'teams' => [$this->workspace],
        ])));

        // When
        $workspaces = $clickup->workspaces();

        // Then
        $this->assertIsArray($workspaces);
        $this->assertCount(1, $workspaces);
        $this->assertInstanceOf(Workspace::class, $workspaces[0]);
        $this->assertEquals($this->workspace['id'], $workspaces[0]->id);
        $this->assertIsArray($workspaces[0]->toArray());
    }

    /** @test */
    public function it_should_throw_an_failed_action_exception_when_client_receives_bad_request_while_getting_a_list_of_workspaces()
    {
        // Given
        $clickup = new Client(['clientId' => 1, 'clientSecret' => 'secret', 'redirectUrl' => 'none'], $this->token);

        $clickup->setClient($service = Mockery::mock('\GuzzleHttp\Client'));

        $service->shouldReceive('request')->once()->andReturn($response = Mockery::mock('Psr\Http\Message\ResponseInterface'));
        $response->shouldReceive('getStatusCode')->andReturn(400);
        $response->shouldReceive('getBody')->andReturn(\GuzzleHttp\Psr7\Utils::streamFor());

        $this->expectException(FailedActionException::class);

        // When
        $clickup->workspaces();
    }

    /** @test */
    public function it_should_throw_a_notfound_exception_when_client_receives_not_found_while_getting_a_list_of_workspaces()
    {
        // Given
        $clickup = new Client(['clientId' => 1, 'clientSecret' => 'secret', 'redirectUrl' => 'none'], $this->token);

        $clickup->setClient($service = Mockery::mock('\GuzzleHttp\Client'));

        $service->shouldReceive('request')->once()->andReturn($response = Mockery::mock('Psr\Http\Message\ResponseInterface'));
        $response->shouldReceive('getStatusCode')->andReturn(404);
        $response->shouldReceive('getBody')->andReturnNull();

        $this->expectException(NotFoundException::class);

        // When
        $clickup->workspaces();
    }

    /** @test */
    public function it_should_throw_a_unauthorized_exception_when_client_lacks_authorization_for_getting_a_list_of_workspaces()
    {
        // Given
        $clickup = new Client(['clientId' => 1, 'clientSecret' => 'secret', 'redirectUrl' => 'none'], $this->token);

        $clickup->setClient($service = Mockery::mock('\GuzzleHttp\Client'));

        $service->shouldReceive('request')->once()->andReturn($response = Mockery::mock('Psr\Http\Message\ResponseInterface'));
        $response->shouldReceive('getStatusCode')->andReturn(401);
        $response->shouldReceive('getBody')->andReturn(\GuzzleHttp\Psr7\Utils::streamFor());

        $this->expectException(UnauthorizedException::class);

        // When
        $clickup->workspaces();
    }

    /** @test */
    public function it_should_throw_a_validation_exception_when_client_provides_invalid_data_while_a_getting_list_of_workspaces()
    {
        // Given
        $clickup = new Client(['clientId' => 1, 'clientSecret' => 'secret', 'redirectUrl' => 'none'], $this->token);

        $clickup->setClient($service = Mockery::mock('\GuzzleHttp\Client'));

        $service->shouldReceive('request')->once()->andReturn($response = Mockery::mock('Psr\Http\Message\ResponseInterface'));
        $response->shouldReceive('getStatusCode')->andReturn(422);
        $response->shouldReceive('getBody')->andReturn(\GuzzleHttp\Psr7\Utils::streamFor(json_encode(['message' => 'invalid'])));

        $this->expectException(ValidationException::class);

        // When
        $clickup->workspaces();
    }

    /** @test */
    public function it_should_return_an_error_message_when_client_provides_invalid_data_while_a_getting_list_of_workspaces()
    {
        // Given
        $clickup = new Client(['clientId' => 1, 'clientSecret' => 'secret', 'redirectUrl' => 'none'], $this->token);

        $clickup->setClient($service = Mockery::mock('\GuzzleHttp\Client'));

        $service->shouldReceive('request')->once()->andReturn($response = Mockery::mock('Psr\Http\Message\ResponseInterface'));
        $response->shouldReceive('getStatusCode')->andReturn(422);
        $response->shouldReceive('getBody')->andReturn(\GuzzleHttp\Psr7\Utils::streamFor(json_encode(['errors' => ['invalid']])));

        // When
        try {
            $clickup->workspaces();
        } catch (ValidationException $exception) {
            // Then
            $this->assertIsArray($exception->errors());
            $this->assertEquals('invalid', $exception->errors()['errors'][0]);
        }
    }

    /** @test */
    public function it_should_throw_a_generic_exception_when_client_suddenly_becomes_a_teapot_while_a_getting_list_of_workspaces()
    {
        // Given
        $clickup = new Client(['clientId' => 1, 'clientSecret' => 'secret', 'redirectUrl' => 'none'], $this->token);

        $clickup->setClient($service = Mockery::mock('\GuzzleHttp\Client'));

        $service->shouldReceive('request')->once()->andReturn($response = Mockery::mock('Psr\Http\Message\ResponseInterface'));
        $response->shouldReceive('getStatusCode')->andReturn(418);
        $response->shouldReceive('getBody')->andReturn(\GuzzleHttp\Psr7\Utils::streamFor(json_encode(['rooibos' => 'anyone?'])));

        $this->expectException(Exception::class);

        // When
        $clickup->workspaces();
    }
}
