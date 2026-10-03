<?php

namespace Padlet\Tests\Bridge;

use Padlet\ApiKeyProviderInterface;
use Padlet\Bridge\Symfony\PadletBundle;
use Padlet\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpClient\MockHttpClient;

final class SymfonyBundleTest extends TestCase
{
    public function testTheBundleRegistersTheClientOnTheConfiguredKey(): void
    {
        $container = new ContainerBuilder();
        $container->set('http_client', new MockHttpClient());
        $bundle = new PadletBundle();
        $container->registerExtension($bundle->getContainerExtension());
        $container->loadFromExtension('padlet', ['api_key' => 'k3y']);
        $container->compile();

        self::assertInstanceOf(Client::class, $container->get(Client::class));
        self::assertTrue($container->get(Client::class)->isConfigured());
    }
}
