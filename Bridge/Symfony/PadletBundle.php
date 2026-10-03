<?php

namespace Padlet\Bridge\Symfony;

use Padlet\ApiKeyProviderInterface;
use Padlet\Client;
use Padlet\StaticApiKeyProvider;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * glitchr/padlet in a Symfony application: Padlet\Client as a service on the
 * key configured - or on the ApiKeyProviderInterface the application names,
 * when the key is typed in its back office.
 *
 *     padlet:
 *         api_key: '%env(PADLET_API_KEY)%'
 *         api_key_provider: App\Tools\PadletKey
 */
final class PadletBundle extends AbstractBundle
{
    protected string $extensionAlias = 'padlet';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('api_key')->defaultValue('')->end()
                ->scalarNode('api_key_provider')->defaultNull()->info('A service implementing Padlet\ApiKeyProviderInterface; null: api_key.')->end()
                ->scalarNode('base_url')->defaultValue(Client::BASE_URL)->end()
                ->scalarNode('key_header')->defaultValue(Client::KEY_HEADER)->end()
            ->end();
    }

    /** @param array{api_key: string, api_key_provider: ?string, base_url: string, key_header: string} $config */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();
        if ($config['api_key_provider']) {
            $services->alias(ApiKeyProviderInterface::class, $config['api_key_provider']);
        } else {
            $services->set(StaticApiKeyProvider::class)->args([$config['api_key']]);
            $services->alias(ApiKeyProviderInterface::class, StaticApiKeyProvider::class);
        }
        $services->set(Client::class)
            ->args([service('http_client'), service(ApiKeyProviderInterface::class), $config['base_url'], $config['key_header']])
            ->public();
    }
}
