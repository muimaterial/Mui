framework:
    rate_limiter:
        public_api_strict:
            policy: 'fixed_window'
            limit: 5
            interval: '1 minute'
        public_api_relaxed:
            policy: 'fixed_window'
            limit: 60
            interval: '1 minute'

namespace App\EventListener;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
final class ApiRateLimitListener
{
    private array $routeRules = [
        '/api/public/sensitive-endpoint' => 'public_api_strict',
        '/api/public/data-endpoint'      => 'public_api_relaxed',
    ];

    public function __construct(
        #[TaggedLocator('rate_limiter.factory')]
        private ContainerInterface $limiters,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (!isset($this->routeRules[$path])) {
            return;
        }

        $limiterName = $this->routeRules[$path];

        if (!$this->limiters->has($limiterName)) {
            return;
        }

        $factory = $this->limiters->get($limiterName);
        
        // Track clients by IP address (or replace with user token/API key if available)
        $clientKey = $request->getClientIp() ?? 'anonymous';
        $limit = $factory->create($clientKey)->consume(1);

        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter()?->getTimestamp() - time();
            throw new TooManyRequestsHttpException(
                max(1, $retryAfter), 
                'Rate limit exceeded. Please try again later.'
            );
        }
    }
}

namespace App\EventListener;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
final class ApiRateLimitListener
{
    private const ROUTE_RULES = [
        'public_api_strict' => [
            '/api/public/sensitive-endpoint',
            '/api/public/auth',
        ],
        'public_api_relaxed' => [
            '/api/public/data-endpoint',
            '/api/public/posts',
        ],
    ];

    public function __construct(
        #[TaggedLocator('rate_limiter.factory')]
        private ContainerInterface $limiters,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        $limiterName = $this->resolveLimiterName($path);

        if ($limiterName === null || !$this->limiters->has($limiterName)) {
            return;
        }

        $clientKey = $event->getRequest()->getClientIp() ?? 'anonymous';
        $limit = $this->limiters->get($limiterName)->create($clientKey)->consume(1);

        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter()?->getTimestamp() - time();
            throw new TooManyRequestsHttpException(
                max(1, $retryAfter), 
                'Rate limit exceeded. Please try again later.'
            );
        }
    }

    private function resolveLimiterName(string $path): ?string
    {
        foreach (self::ROUTE_RULES as $limiterName => $routes) {
            if (in_array($path, $routes, true)) {
                return $limiterName;
            }
        }

        return null;
    }
}

