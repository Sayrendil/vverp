<?php

namespace App\Services\Telegram;

use Closure;
use Illuminate\Support\Facades\Log;

/**
 * Роутер команд Telegram бота
 *
 * Работает по аналогии с веб-роутингом Laravel.
 * Позволяет регистрировать команды и их обработчики.
 */
class CommandRouter
{
    /**
     * @var array<string, array> Зарегистрированные команды
     */
    private array $routes = [];

    /**
     * @var array<string, string> Описания команд для /help
     */
    private array $descriptions = [];

    /**
     * @var array Middleware для всех команд
     */
    private array $globalMiddleware = [];

    /**
     * Зарегистрировать команду
     *
     * @param string $command Команда (например: /start, /help)
     * @param callable|array $handler Обработчик команды
     * @return CommandRoute
     */
    public function command(string $command, callable|array $handler): CommandRoute
    {
        // Нормализуем команду (добавляем / если нет)
        if (!str_starts_with($command, '/')) {
            $command = '/' . $command;
        }

        $route = new CommandRoute($command, $handler);
        $this->routes[$command] = $route;

        return $route;
    }

    /**
     * Группа команд с общими параметрами
     *
     * @param array $attributes Атрибуты группы (middleware, prefix и т.д.)
     * @param Closure $callback Функция регистрации команд
     * @return void
     */
    public function group(array $attributes, Closure $callback): void
    {
        // TODO: Реализовать группировку команд
        $callback($this);
    }

    /**
     * Добавить глобальный middleware
     *
     * @param callable $middleware
     * @return self
     */
    public function middleware(callable $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    /**
     * Диспетчеризация команды
     *
     * @param string $command Команда
     * @param array $context Контекст (message, chatId, userId и т.д.)
     * @return mixed
     * @throws CommandNotFoundException
     */
    public function dispatch(string $command, array $context = []): mixed
    {
        // Извлекаем только команду (без аргументов)
        $commandParts = explode(' ', $command);
        $commandName = $commandParts[0];
        $arguments = array_slice($commandParts, 1);

        if (!isset($this->routes[$commandName])) {
            throw new CommandNotFoundException("Command not found: {$commandName}");
        }

        $route = $this->routes[$commandName];

        Log::info("Routing command: {$commandName}", [
            'arguments' => $arguments,
            'user_id' => $context['userId'] ?? null,
        ]);

        // Применяем middleware
        $next = fn() => $this->executeHandler($route, $context, $arguments);

        foreach (array_merge($this->globalMiddleware, $route->getMiddleware()) as $middleware) {
            $next = fn() => $middleware($context, $next);
        }

        return $next();
    }

    /**
     * Выполнить обработчик команды
     */
    private function executeHandler(CommandRoute $route, array $context, array $arguments): mixed
    {
        $handler = $route->getHandler();

        // Если handler - массив [Class, method]
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $instance = app($class);
            return $instance->$method($context, ...$arguments);
        }

        // Если handler - callable
        return $handler($context, ...$arguments);
    }

    /**
     * Проверить, зарегистрирована ли команда
     *
     * @param string $command Команда
     * @return bool
     */
    public function has(string $command): bool
    {
        return isset($this->routes[$command]);
    }

    /**
     * Получить все зарегистрированные команды
     *
     * @return array
     */
    public function getCommands(): array
    {
        return array_keys($this->routes);
    }

    /**
     * Получить описание команд для /help
     *
     * @return array
     */
    public function getHelpText(): array
    {
        $help = [];

        foreach ($this->routes as $command => $route) {
            if ($route->getDescription()) {
                $help[$command] = $route->getDescription();
            }
        }

        return $help;
    }
}

/**
 * Класс маршрута команды
 */
class CommandRoute
{
    private array $middleware = [];
    private ?string $description = null;
    private array $meta = [];

    public function __construct(
        private string $command,
        private $handler
    ) {}

    /**
     * Добавить middleware для команды
     *
     * @param callable|array $middleware
     * @return self
     */
    public function middleware(callable|array $middleware): self
    {
        $middleware = is_array($middleware) ? $middleware : [$middleware];
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    /**
     * Установить описание команды
     *
     * @param string $description
     * @return self
     */
    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Установить метаданные
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function meta(string $key, mixed $value): self
    {
        $this->meta[$key] = $value;
        return $this;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getHandler(): callable|array
    {
        return $this->handler;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getMeta(string $key): mixed
    {
        return $this->meta[$key] ?? null;
    }
}

/**
 * Исключение для ненайденной команды
 */
class CommandNotFoundException extends \Exception {}
