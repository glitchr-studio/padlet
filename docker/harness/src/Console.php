<?php

namespace Padlet\Harness;

use Padlet\Client;
use Padlet\Exception\ApiException;
use Padlet\StaticApiKeyProvider;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;

/** The console that exercises the API with the key in .env: me, board, posts, raw. */
final class Console
{
    public static function create(): Application
    {
        $client = new Client(HttpClient::create(), new StaticApiKeyProvider((string) getenv('PADLET_API_KEY')));
        $print = static function (OutputInterface $out, callable $call): int {
            try {
                $out->writeln(json_encode($call(), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));

                return Command::SUCCESS;
            } catch (ApiException $e) {
                $out->writeln('<error>'.$e->getMessage().'</error>');
                if ($e->body) {
                    $out->writeln($e->body);
                }

                return Command::FAILURE;
            }
        };
        $app = new Application('padlet', '1.x');
        $app->addCommand(self::command('me', 'The account', [], fn ($in, $out) => $print($out, fn () => $client->me())));
        $app->addCommand(self::command('board', 'A board with its sections and posts', [new InputArgument('id', InputArgument::REQUIRED)], fn ($in, $out) => $print($out, fn () => $client->board($in->getArgument('id')))));
        $app->addCommand(self::command('posts', 'The posts of a board', [new InputArgument('id', InputArgument::REQUIRED)], fn ($in, $out) => $print($out, fn () => $client->posts($in->getArgument('id')))));
        $app->addCommand(self::command('raw', 'Any GET path, its raw answer', [new InputArgument('path', InputArgument::REQUIRED)], fn ($in, $out) => $print($out, fn () => $client->raw('GET', $in->getArgument('path')))));

        return $app;
    }

    private static function command(string $name, string $description, array $definition, callable $code): Command
    {
        return (new Command($name))->setDescription($description)->setDefinition($definition)->setCode(fn (InputInterface $in, OutputInterface $out) => $code($in, $out) ?? Command::SUCCESS);
    }
}
