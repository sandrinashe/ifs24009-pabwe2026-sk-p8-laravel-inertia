<?php

namespace Tests\Unit\Middleware;

use App\Features\Post\Models\Post;
use App\Http\Middleware\EnsurePostOwnership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsurePostOwnershipMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a request authenticated as $user with a bound route parameter.
     */
    protected function makeRequest(?User $user, string $method, string $param, mixed $value): Request
    {
        $request = Request::create('/test', $method);

        if ($user) {
            $request->setUserResolver(fn () => $user);
        }

        $route = new Route($method, "/test/{{$param}}", []);
        $route->bind($request);
        $route->setParameter($param, $value);
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    protected function assertForbidden(EnsurePostOwnership $middleware, Request $request): void
    {
        try {
            $middleware->handle($request, fn () => new Response('OK'));
            $this->fail('Expected HttpException 403');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_blocks_unauthenticated_request(): void
    {
        $middleware = new EnsurePostOwnership();

        try {
            $middleware->handle(Request::create('/test', 'GET'), fn () => new Response('OK'));
            $this->fail('Expected HttpException 401');
        } catch (HttpException $e) {
            $this->assertSame(401, $e->getStatusCode());
        }
    }

    public function test_passes_when_route_has_no_parameters(): void
    {
        $middleware = new EnsurePostOwnership();
        $member = User::factory()->member()->create();

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $member);
        $route = new Route('GET', '/test', []);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $this->assertSame(200, $middleware->handle($request, fn () => new Response('OK'))->getStatusCode());
    }

    public function test_route_user_parameter_ownership(): void
    {
        $middleware = new EnsurePostOwnership();
        $member1 = User::factory()->member()->create();
        $member2 = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();

        $own = $middleware->handle($this->makeRequest($member1, 'GET', 'user', $member1), fn () => new Response('OK'));
        $this->assertSame(200, $own->getStatusCode());

        // Raw id (not a model instance)
        $ownById = $middleware->handle($this->makeRequest($member1, 'GET', 'user', (string) $member1->id), fn () => new Response('OK'));
        $this->assertSame(200, $ownById->getStatusCode());

        $this->assertForbidden($middleware, $this->makeRequest($member1, 'GET', 'user', $member2));

        $asAdmin = $middleware->handle($this->makeRequest($admin, 'GET', 'user', $member2), fn () => new Response('OK'));
        $this->assertSame(200, $asAdmin->getStatusCode());
    }

    public function test_route_post_parameter_ownership(): void
    {
        $middleware = new EnsurePostOwnership();
        $member1 = User::factory()->member()->create();
        $member2 = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create(['user_id' => $member1->id]);

        // Owner reads
        $this->assertSame(200, $middleware->handle($this->makeRequest($member1, 'GET', 'post', $post), fn () => new Response('OK'))->getStatusCode());

        // Raw id is resolved through the database
        $this->assertSame(200, $middleware->handle($this->makeRequest($member1, 'GET', 'post', (string) $post->id), fn () => new Response('OK'))->getStatusCode());

        // Other member is blocked
        $this->assertForbidden($middleware, $this->makeRequest($member2, 'GET', 'post', $post));

        // Admin may read...
        $this->assertSame(200, $middleware->handle($this->makeRequest($admin, 'GET', 'post', $post), fn () => new Response('OK'))->getStatusCode());

        // ...but never modify somebody else's post
        $this->assertForbidden($middleware, $this->makeRequest($admin, 'DELETE', 'post', $post));
        $this->assertForbidden($middleware, $this->makeRequest($admin, 'PUT', 'post', $post));

        // Owner may modify
        $this->assertSame(200, $middleware->handle($this->makeRequest($member1, 'DELETE', 'post', $post), fn () => new Response('OK'))->getStatusCode());
    }

    public function test_unknown_post_id_is_passed_through_for_route_binding_to_handle(): void
    {
        $middleware = new EnsurePostOwnership();
        $member = User::factory()->member()->create();

        $response = $middleware->handle($this->makeRequest($member, 'GET', 'post', '999999'), fn () => new Response('OK'));

        $this->assertSame(200, $response->getStatusCode());
    }
}
